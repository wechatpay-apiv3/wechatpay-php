#!/usr/bin/env php
<?php declare(strict_types=1);

// load autoload.php
$possibleFiles = [__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php', __DIR__.'/../../autoload.php'];
$file = null;
foreach ($possibleFiles as $possibleFile) {
    if (\file_exists($possibleFile)) {
        $file = $possibleFile;
        break;
    }
}
if (null === $file) {
    throw new \RuntimeException('Unable to locate autoload.php file.');
}

require_once $file;
unset($possibleFiles, $possibleFile, $file);

use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Formatter;

 /**
  * CertificateDownloader class
  */
class CertificateDownloader
{
    private const DEFAULT_BASE_URI = 'https://api.mch.weixin.qq.com/';

    public function run(): void
    {
        $opts = $this->parseOpts();

        if (isset($opts['help'])) {
            $this->printHelp();
            return;
        }
        if (isset($opts['version'])) {
            self::prompt(ClientDecoratorInterface::VERSION);
            return;
        }
        if (!$opts) {
            $this->printHelp();
            exit(1);
        }
        if (!$this->job($opts)) {
            exit(1);
        }
    }

    /**
     * Before `verifier` executing, decrypt and put the platform certificate(s) into the `$certs` reference.
     *
     * @param string $apiv3Key
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsInjector(string $apiv3Key, array &$certs): callable {
        return static function(ResponseInterface $response) use ($apiv3Key, &$certs): ResponseInterface {
            $body = (string) $response->getBody();
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_map(static function($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
            }, $data);

            return $response;
        };
    }

    /**
     * @param array<string,string|true> $opts
     *
     * @return bool - `true` while all of the certificate(s) were saved onto disk
     */
    private function job(array $opts): bool
    {
        static $certs = ['any' => null];

        $outputDir = isset($opts['output']) ? (string) $opts['output'] : self::createPrivateDir();
        if (null === $outputDir) {
            return false;
        }
        $apiv3Key = (string) $opts['key'];

        $instance = Builder::factory([
            'mchid'      => $opts['mchid'],
            'serial'     => $opts['serialno'],
            'privateKey' => \file_get_contents((string)$opts['privatekey']),
            'certs'      => &$certs,
            'base_uri'   => (string)($opts['baseuri'] ?? self::DEFAULT_BASE_URI),
        ]);

        $failed = false;

        /** @var \GuzzleHttp\HandlerStack $stack */
        $stack = $instance->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
        // The response middle stacks were executed one by one on `FILO` order.
        $stack->after('verifier', Middleware::mapResponse(self::certsInjector($apiv3Key, $certs)), 'injector');
        $stack->before('verifier', Middleware::mapResponse(self::certsRecorder($outputDir, $certs, $failed)), 'recorder');

        $instance->chain('v3/certificates')->getAsync(
            ['debug' => true]
        )->otherwise(static function($exception) use (&$failed) {
            $failed = true;
            self::prompt($exception->getMessage());
            if ($exception instanceof RequestException && $exception->hasResponse()) {
                /** @var ResponseInterface $response */
                $response = $exception->getResponse();
                self::prompt((string) $response->getBody(), '', '');
            }
            self::prompt($exception->getTraceAsString());
        })->wait();

        return !$failed;
    }

    /**
     * Create, or reuse, the directory which is not writable by the others, underneath the system's temporary directory.
     *
     * @return ?string - The directory holding the certificate(s), or `null` while it is unavailable
     */
    private static function createPrivateDir(): ?string
    {
        $uid = \function_exists('posix_geteuid') ? \posix_geteuid() : null;
        $dir = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'wechatpay-' . ($uid ?? Formatter::nonce(16));

        \error_clear_last();
        if (!@\mkdir($dir, 0755)) {
            if (null === $uid) {
                self::prompt(\sprintf(
                    'Failed to create the private directory `%s`: %s, please assign the `-o` option.',
                    $dir,
                    self::lastErrorMessage()
                ));
                return null;
            }

            \error_clear_last();
            $stat = @\lstat($dir);
            if (false === $stat) {
                self::prompt(\sprintf(
                    'Failed to stat the existing `%s`: %s, please assign the `-o` option.',
                    $dir,
                    self::lastErrorMessage()
                ));
                return null;
            }

            $mode = (int) $stat['mode'];

            if (0040000 !== ($mode & 0170000) || 0 !== ($mode & 0022) || $uid !== (int) $stat['uid']) {
                self::prompt(\sprintf(
                    'Refused to reuse `%s`, it shall be a directory owned by the current user and not writable by the others, please assign the `-o` option.',
                    $dir
                ));
                return null;
            }
        }

        self::prompt('The certificate(s) will be saved into the private directory: ' . self::highlight($dir));

        return $dir;
    }

    /**
     * After `verifier` executed, wrote the platform certificate(s) onto disk.
     *
     * @param string $outputDir
     * @param array<string,?string> $certs
     * @param bool $failed - Flipped to `true` while any of the certificate(s) cannot be saved
     *
     * @return callable(ResponseInterface)
     */
    private static function certsRecorder(string $outputDir, array &$certs, bool &$failed): callable {
        return static function(ResponseInterface $response) use ($outputDir, &$certs, &$failed): ResponseInterface {
            $body = (string) $response->getBody();
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];

            if (0 === \count($data)) {
                $failed = true;
                self::prompt('There\'s no certificate onto the response, nothing was saved.');

                return $response;
            }

            \array_walk($data, static function($row, $index, $certs) use ($outputDir, &$failed) {
                $serialNo = (string) $row->serial_no;
                $content = (string) ($certs[$serialNo] ?? '');

                if (!\preg_match('#^[0-9A-Fa-f]{1,64}$#D', $serialNo) || '' === $content) {
                    $failed = true;
                    self::prompt(\sprintf('Skipped the certificate #%s: unexpected serial number or empty content.', $index));

                    return;
                }

                $outpath = $outputDir . \DIRECTORY_SEPARATOR . 'wechatpay_' . $serialNo . '.pem';
                $saved = self::atomicDump($outpath, $content);
                $failed = $failed || !$saved;

                self::prompt(
                    'Certificate #' . $index . ' {',
                    '    Serial Number: ' . self::highlight($serialNo),
                    '    Not Before: ' . (new \DateTime($row->effective_time))->format(\DateTime::W3C),
                    '    Not After: ' . (new \DateTime($row->expire_time))->format(\DateTime::W3C),
                    '    Saved to: ' . ($saved ? self::highlight($outpath) : 'FAILED, see the message(s) above'),
                    '    You may confirm the above infos again even if this library already did(by Crypto\Rsa::verify):',
                    '      ' . self::highlight(\sprintf('openssl x509 -in %s -noout -serial -dates', $outpath)),
                    '    Content: ', '', $content, '',
                    '}'
                );
            }, $certs);

            return $response;
        };
    }

    /**
     * Atomically persist the `$content` onto `$path` without ever following a symbolic link.
     *
     * @param string $path - The destination file path
     * @param string $content - The content to write
     *
     * @return bool - `true` on success, `false` when the write was refused or failed
     */
    private static function atomicDump(string $path, string $content): bool
    {
        $temp = $path . '.' . Formatter::nonce(16) . '.tmp';

        \error_clear_last();
        $handle = @\fopen($temp, 'xb');
        if (false === $handle) {
            self::prompt(\sprintf(
                'Failed to exclusively create the temporary file `%s`: %s.',
                $temp,
                self::lastErrorMessage()
            ));
            return false;
        }

        $length  = \strlen($content);
        \error_clear_last();
        $written = @\fwrite($handle, $content);
        $flushed = @\fflush($handle);
        if (\function_exists('fsync')) {
            @\fsync($handle);
        }
        $closed = @\fclose($handle);
        // Grab it before the `sizeOf` and `unlink` below, either of them may overwrite the last error.
        $reason = self::lastErrorMessage();

        if (!$flushed || !$closed || $written !== $length || $length !== self::sizeOf($temp)) {
            @\unlink($temp);
            self::prompt(\sprintf(
                'Failed to write the whole content onto `%s`: %d of %d bytes were written, %s.',
                $temp,
                (int) $written,
                $length,
                $reason
            ));
            return false;
        }

        \error_clear_last();
        if (!@\rename($temp, $path)) {
            $reason = self::lastErrorMessage();
            @\unlink($temp);
            self::prompt(\sprintf('Failed to place the certificate onto `%s`: %s.', $path, $reason));
            return false;
        }

        return true;
    }

    /**
     * The byte size of the `$path`, without ever following a symbolic link.
     *
     * @param string $path - The file path to measure
     *
     * @return int - The size in bytes, or `-1` while the `$path` is unavailable
     */
    private static function sizeOf(string $path): int
    {
        \clearstatcache(true, $path);
        $stat = @\lstat($path);

        return false === $stat ? -1 : (int) $stat['size'];
    }

    /**
     * The message of the last error, which was suppressed by the `@` operator.
     *
     * Pair it with a preceding `error_clear_last()` call, otherwise a stale message may be returned.
     *
     * @return string - The message, or `unknown error` while there's none
     */
    private static function lastErrorMessage(): string
    {
        return \error_get_last()['message'] ?? 'unknown error';
    }

    /**
     * @param string $thing
     */
    private static function highlight(string $thing): string
    {
        return \sprintf("\x1B[1;32m%s\x1B[0m", $thing);
    }

    /**
     * @param string $messages
     */
    private static function prompt(...$messages): void
    {
        \array_walk($messages, static function (string $message): void { \printf('%s%s', $message, \PHP_EOL); });
    }

    /**
     * @return ?array<string,string|true>
     */
    private function parseOpts(): ?array
    {
        $opts = [
            [ 'key', 'k', true ],
            [ 'mchid', 'm', true ],
            [ 'privatekey', 'f', true ],
            [ 'serialno', 's', true ],
            [ 'output', 'o', false ],
            // baseuri can be one of 'https://api2.mch.weixin.qq.com/', 'https://apihk.mch.weixin.qq.com/'
            [ 'baseuri', 'u', false ],
        ];

        $shortopts = 'hV';
        $longopts = [ 'help', 'version' ];
        foreach ($opts as $opt) {
            [$key, $alias] = $opt;
            $shortopts .= $alias . ':';
            $longopts[] = $key . ':';
        }
        $parsed = \getopt($shortopts, $longopts);

        if (!$parsed) {
            return null;
        }

        // Both of the `help` and `version` are prior to the mandatory option(s) checking below.
        if (isset($parsed['h']) || isset($parsed['help'])) {
            return ['help' => true];
        }
        if (isset($parsed['V']) || isset($parsed['version'])) {
            return ['version' => true];
        }

        $args = [];
        foreach ($opts as $opt) {
            [$key, $alias, $mandatory] = $opt;
            if (isset($parsed[$key]) || isset($parsed[$alias])) {
                /** @var string|string[] $possible */
                $possible = $parsed[$key] ?? $parsed[$alias] ?? '';
                $args[$key] = \is_array($possible) ? $possible[0] : $possible;
            } elseif ($mandatory) {
                return null;
            }
        }

        return $args;
    }

    private function printHelp(): void
    {
        self::prompt(
            'Usage: 微信支付平台证书下载工具 [-hV]',
            '                    -f=<privateKeyFilePath> -k=<apiv3Key> -m=<merchantId>',
            '                    -s=<serialNo> -o=[outputFilePath] -u=[baseUri]',
            'Options:',
            '  -m, --mchid=<merchantId>   商户号',
            '  -s, --serialno=<serialNo>  商户证书的序列号',
            '  -f, --privatekey=<privateKeyFilePath>',
            '                             商户的私钥文件',
            '  -k, --key=<apiv3Key>       APIv3密钥',
            '  -o, --output=[outputFilePath]',
            '                             下载成功后保存证书的路径，可选，默认为临时文件目录夹下新建的专属目录，运行时打印其完整路径',
            '  -u, --baseuri=[baseUri]    接入点，可选，默认为 ' . self::DEFAULT_BASE_URI,
            '  -V, --version              Print version information and exit.',
            '  -h, --help                 Show this help message and exit.', ''
        );
    }
}

// main
(new CertificateDownloader())->run();
