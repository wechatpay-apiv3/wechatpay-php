<?php declare(strict_types=1);

namespace WeChatPay\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

if (!\defined('WECHATPAY_CERTIFICATE_DOWNLOADER_NO_MAIN')) {
    \define('WECHATPAY_CERTIFICATE_DOWNLOADER_NO_MAIN', true);
}
require_once __DIR__ . '/../bin/CertificateDownloader.php';

class CertificateDownloaderTest extends TestCase
{
    /** @var bool Whether the filesystem honors the POSIX permission bits */
    private const POSIX = '\\' !== \DIRECTORY_SEPARATOR;

    /**
     * Whether the downloader can determine the current user, ie. names the private directory by that uid.
     *
     * @return bool
     */
    private static function hasUid(): bool
    {
        return \function_exists('posix_geteuid');
    }

    /** @var string */
    private $workDir = '';

    protected function setUp(): void
    {
        $this->workDir = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'wechatpay-test-' . \bin2hex(\random_bytes(8));
        \mkdir($this->workDir, 0700);
    }

    protected function tearDown(): void
    {
        if ('' === $this->workDir || !\is_dir($this->workDir)) {
            return;
        }
        @\chmod($this->workDir, 0700);
        /** @var string[] $entries */
        $entries = \glob($this->workDir . \DIRECTORY_SEPARATOR . '*') ?: [];
        foreach ($entries as $entry) {
            \is_link($entry) || \is_file($entry) ? @\unlink($entry) : @\rmdir($entry);
        }
        @\rmdir($this->workDir);
    }

    /**
     * @param string $name
     * @param array<int,mixed> $args
     * @return mixed
     */
    private static function invoke(string $name, array $args)
    {
        $method = new ReflectionMethod('CertificateDownloader', $name);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $args);
    }

    /**
     * @param string $path
     * @return int
     */
    private static function permissionOf(string $path): int
    {
        \clearstatcache(true, $path);
        $stat = \lstat($path);

        return false === $stat ? -1 : ((int) $stat['mode'] & 0777);
    }

    public function testAtomicDumpKeepsTheDefaultPermissionForANewOne(): void
    {
        $path = $this->workDir . \DIRECTORY_SEPARATOR . 'wechatpay_ABC.pem';

        self::assertTrue(self::invoke('atomicDump', [$path, 'CERT']));
        self::assertSame('CERT', \file_get_contents($path));

        if (self::POSIX) {
            $mask = \umask();
            self::assertSame(0666 & ~$mask, self::permissionOf($path), 'a brand new certificate shall be created as the `umask` says');
        }
    }

    public function testAtomicDumpReplacesThePreviousOne(): void
    {
        $path = $this->workDir . \DIRECTORY_SEPARATOR . 'wechatpay_ABC.pem';
        \file_put_contents($path, 'OLD');

        self::assertTrue(self::invoke('atomicDump', [$path, 'NEW']));
        self::assertSame('NEW', \file_get_contents($path));

        if (self::POSIX) {
            $mask = \umask();
            self::assertSame(0666 & ~$mask, self::permissionOf($path), 'the replacement is created as the `umask` says');
        }
    }

    public function testAtomicDumpDoesNotFollowTheSymlink(): void
    {
        if (!self::POSIX) {
            self::markTestSkipped('Creating a symlink requires the privilege on Windows.');
        }

        $victim = $this->workDir . \DIRECTORY_SEPARATOR . 'victim';
        $path   = $this->workDir . \DIRECTORY_SEPARATOR . 'wechatpay_ABC.pem';
        \file_put_contents($victim, 'UNTOUCHED');
        \symlink($victim, $path);

        self::assertTrue(self::invoke('atomicDump', [$path, 'CERT']));

        self::assertSame('UNTOUCHED', \file_get_contents($victim), 'the symlink target shall never be written');
        self::assertFalse(\is_link($path), 'the symlink itself shall be replaced by the certificate');
        self::assertSame('CERT', \file_get_contents($path));
    }

    public function testAtomicDumpFailsOnUnwritableDirectory(): void
    {
        if (!self::POSIX || (self::hasUid() && 0 === \posix_geteuid())) {
            self::markTestSkipped('The `root` user bypasses the permission check.');
        }

        $path = $this->workDir . \DIRECTORY_SEPARATOR . 'wechatpay_ABC.pem';
        \chmod($this->workDir, 0500);

        \ob_start();
        $saved = self::invoke('atomicDump', [$path, 'CERT']);
        $message = (string) \ob_get_clean();

        self::assertFalse($saved);
        self::assertStringContainsString('Failed to exclusively create the temporary file', $message);
    }

    public function testAtomicDumpLeavesNoTemporaryFileBehind(): void
    {
        $path = $this->workDir . \DIRECTORY_SEPARATOR . 'wechatpay_ABC.pem';

        self::assertTrue(self::invoke('atomicDump', [$path, 'CERT']));

        /** @var string[] $leftovers */
        $leftovers = \glob($this->workDir . \DIRECTORY_SEPARATOR . '*.tmp') ?: [];
        self::assertSame([], $leftovers);
    }

    public function testSizeOfReportsTheAbsentPath(): void
    {
        $absent = $this->workDir . \DIRECTORY_SEPARATOR . 'absent.pem';
        self::assertSame(-1, self::invoke('sizeOf', [$absent]));

        $path = $this->workDir . \DIRECTORY_SEPARATOR . 'sized';
        \file_put_contents($path, '12345');
        self::assertSame(5, self::invoke('sizeOf', [$path]));
    }

    /**
     * Run the `createPrivateDir` within an isolated temporary directory, so that the
     * real one of the current user is never touched.
     *
     * @param string $tmpdir
     * @param callable(string):void $prepare
     * @param ?string $umask - The octal `umask` to apply onto the isolated process
     * @return array{string,string}
     */
    private static function createPrivateDirWithin(string $tmpdir, callable $prepare, ?string $umask = null): array
    {
        $prepare($tmpdir);

        $script = <<<'PHP'
<?php
define('WECHATPAY_CERTIFICATE_DOWNLOADER_NO_MAIN', true);
require $argv[1];
if (false !== ($mask = getenv('TEST_UMASK'))) {
    umask((int) octdec($mask));
}
$method = new ReflectionMethod('CertificateDownloader', 'createPrivateDir');
$method->setAccessible(true);
$dir = $method->invoke(null);
fwrite(STDERR, null === $dir ? '' : $dir);
PHP;
        $runner = $tmpdir . \DIRECTORY_SEPARATOR . 'runner.php';
        \file_put_contents($runner, $script);

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env = ['TMPDIR' => $tmpdir, 'TMP' => $tmpdir, 'TEMP' => $tmpdir, 'PATH' => (string) \getenv('PATH')];
        if (null !== $umask) {
            $env['TEST_UMASK'] = $umask;
        }
        $command = \escapeshellarg(\PHP_BINARY) . ' ' . \escapeshellarg($runner)
            . ' ' . \escapeshellarg(\dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'bin' . \DIRECTORY_SEPARATOR . 'CertificateDownloader.php');

        $process = \proc_open($command, $descriptors, $pipes, $tmpdir, $env);
        if (!\is_resource($process)) {
            self::fail('Cannot spawn the isolated PHP process.');
        }
        $stdout = (string) \stream_get_contents($pipes[1]);
        $stderr = (string) \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);
        \proc_close($process);

        return [$stderr, $stdout];
    }

    public function testCreatePrivateDirIsOwnedAndNotWritableByOthers(): void
    {
        $tmpdir = $this->workDir;
        [$dir, $prompt] = self::createPrivateDirWithin($tmpdir, static function (): void {});

        self::assertNotSame('', $dir, 'the private directory shall be created');
        self::assertDirectoryExists($dir);
        self::assertStringContainsString($dir, $prompt, 'the resolved path shall be prompted out');

        if (self::POSIX) {
            $mode = self::permissionOf($dir);
            self::assertSame(0, $mode & 0022, 'the directory shall never be writable by the group nor the others');
            self::assertSame(0700, $mode & 0700, 'the owner shall fully access the directory');
        }

        self::assertSame(
            1,
            self::hasUid()
                ? \preg_match('#^wechatpay-' . \posix_geteuid() . '$#', \basename($dir))
                : \preg_match('#^wechatpay-[0-9a-f]{16}$#', \basename($dir)),
            'the directory shall be named by the uid, or randomly while that is unavailable'
        );
    }

    public function testCreatePrivateDirStaysUnwritableByOthersUnderALooseUmask(): void
    {
        if (!self::POSIX) {
            self::markTestSkipped('The POSIX permission is not applicable on Windows.');
        }

        [$dir] = self::createPrivateDirWithin($this->workDir, static function (): void {}, '0000');

        self::assertNotSame('', $dir);
        self::assertSame(
            0,
            self::permissionOf($dir) & 0022,
            'even the most permissive `umask` shall not open the directory up for the group nor the others'
        );
    }

    public function testCreatePrivateDirRefusesToReuseARegularFile(): void
    {
        if (!self::hasUid()) {
            self::markTestSkipped('The directory name is unpredictable without a reliable uid, hence nothing to preempt.');
        }

        $tmpdir = $this->workDir;
        $taken  = 'wechatpay-' . \posix_geteuid();

        [$dir, $prompt] = self::createPrivateDirWithin($tmpdir, static function (string $base) use ($taken): void {
            \file_put_contents($base . \DIRECTORY_SEPARATOR . $taken, 'preempted');
        });

        self::assertSame('', $dir, 'a preempted path shall be refused');
        self::assertStringContainsString('Refused to reuse', $prompt);
    }

    public function testCreatePrivateDirRefusesTheLoosePermission(): void
    {
        if (!self::POSIX || !self::hasUid()) {
            self::markTestSkipped('The POSIX permission is not applicable on Windows.');
        }

        $tmpdir = $this->workDir;
        $taken  = 'wechatpay-' . \posix_geteuid();

        [$dir, $prompt] = self::createPrivateDirWithin($tmpdir, static function (string $base) use ($taken): void {
            \mkdir($base . \DIRECTORY_SEPARATOR . $taken, 0777);
            \chmod($base . \DIRECTORY_SEPARATOR . $taken, 0777);
        });

        self::assertSame('', $dir, 'a group/world writable directory shall be refused');
        self::assertStringContainsString('Refused to reuse', $prompt);
    }

    public function testCreatePrivateDirReusesTheCompliantOne(): void
    {
        if (!self::POSIX || !self::hasUid()) {
            self::markTestSkipped('A randomly named directory is never reused on the platform without a reliable uid.');
        }

        $tmpdir = $this->workDir;
        $taken  = 'wechatpay-' . \posix_geteuid();

        [$dir] = self::createPrivateDirWithin($tmpdir, static function (string $base) use ($taken): void {
            \mkdir($base . \DIRECTORY_SEPARATOR . $taken, 0755);
            \chmod($base . \DIRECTORY_SEPARATOR . $taken, 0755);
        });

        self::assertSame($tmpdir . \DIRECTORY_SEPARATOR . $taken, $dir, 'an own directory which is readable by the others shall still be reused');
    }
}
