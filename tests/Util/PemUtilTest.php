<?php declare(strict_types=1);

namespace WeChatPay\Tests\Util;

use const PHP_EOL;
use const PHP_SAPI;
use const PHP_VERSION_ID;
use const STDERR;
use const PHP_MAJOR_VERSION;
use const OPENSSL_KEYTYPE_RSA;
use const OPENSSL_VERSION_TEXT;
use const DIRECTORY_SEPARATOR;

use function is_resource;
use function is_object;
use function putenv;
use function fwrite;
use function dirname;
use function sprintf;
use function openssl_pkey_new;
use function openssl_csr_new;
use function openssl_csr_sign;
use function openssl_x509_export_to_file;
use function openssl_x509_export;
use function openssl_pkey_export_to_file;
use function openssl_pkey_export;
use function openssl_x509_parse;

use WeChatPay\Util\PemUtil;
use PHPUnit\Framework\TestCase;

class PemUtilTest extends TestCase
{
    private const SUBJECT_CN = 'WeChatPay Community CI';
    private const SUBJECT_O  = 'WeChatPay Community';
    private const SUBJECT_ST = 'Shanghai';
    private const SUBJECT_C  = 'CN';

    /** @var array<string,string> */
    private static $certSubject = [
        'commonName'          => self::SUBJECT_CN,
        'organizationName'    => self::SUBJECT_O,
        'stateOrProvinceName' => self::SUBJECT_ST,
        'countryName'         => self::SUBJECT_C,
    ];

    /** @var ?array{string,string,string,string,string} */
    private static $environment;

    public static function setUpBeforeClass(): void
    {
        $baseDir  = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
        $baseAlgo = ['digest_alg' => 'sha256'];

        /** openssl v1.1.1 won't need anymore RANDFILE config {@link https://github.com/openssl/openssl/issues/7754} */
        'cli' === PHP_SAPI && fwrite(STDERR, PHP_EOL . 'OpenSSL Version: ' . OPENSSL_VERSION_TEXT . PHP_EOL);
        'cli' === PHP_SAPI && 70205 < PHP_VERSION_ID && PHP_VERSION_ID < 70400 && putenv("OPENSSL_CONF={$baseDir}openssl.conf");

        $privateKey = openssl_pkey_new($baseAlgo + [
            'default_bits'     => 2048,
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config'           => $baseDir . 'openssl.conf',
        ]);

        $serial     = mt_rand(1000, 9999);
        $certFile   = sprintf('%s%s%d%s', $baseDir, 'ci_', $serial, '.pem');
        $privFile   = sprintf('%s%s%d%s', $baseDir, 'ci_', $serial, '.key');
        $certString = '';
        $privString = '';

        $csr  = false !== $privateKey ? openssl_csr_new(self::$certSubject, $privateKey, $baseAlgo) : false;
        $cert = false !== $csr ? openssl_csr_sign($csr, null, $privateKey, 1, $baseAlgo, $serial) : false;
        'cli' === PHP_SAPI && fwrite(STDERR, sprintf('%.10s %.10s %s', $csr ? 'CSR-OK' : $csr, $cert ? 'CERT-OK' : $cert, PHP_EOL));

        false !== $cert && openssl_x509_export_to_file($cert, $certFile);
        false !== $cert && openssl_x509_export($cert, $certString);
        'cli' === PHP_SAPI && fwrite(STDERR, sprintf('%s%.27s%s', 'Certificate: ', $certString, PHP_EOL));

        false !== $privateKey && openssl_pkey_export_to_file($privateKey, $privFile);
        false !== $privateKey && openssl_pkey_export($privateKey, $privString);
        'cli' === PHP_SAPI && fwrite(STDERR, sprintf('%s%.27s%s', 'PrivateKey: ', $privString, PHP_EOL));

        self::$environment = [sprintf('%04X', $serial), $certFile, $certString, $privFile, $privString];
    }

    public static function tearDownAfterClass(): void
    {
        try {
            [, $certFile, , $privFile] = self::$environment;
            unlink($certFile);
            unlink($privFile);
        } finally {
            self::$environment = null;
        }
    }

    public function testLoadCertificate(): void
    {
        [, $certFile] = self::$environment;
        $cert = PemUtil::loadCertificate($certFile);
        if (8 === PHP_MAJOR_VERSION) {
            self::assertIsObject($cert);
        } else {
            self::assertIsResource($cert);
        }

        /** @var resource|string|\OpenSSLCertificate|mixed $cert */
        ['subject' => $subject, 'issuer' => $issuer] = openssl_x509_parse($cert, false) ?: [];
        self::assertEquals(self::$certSubject, $subject);
        self::assertEquals(self::$certSubject, $issuer);
    }

    public function testLoadCertificateFromString(): void
    {
        [, , $certString] = self::$environment;
        $cert = PemUtil::loadCertificateFromString($certString);
        if (8 === PHP_MAJOR_VERSION) {
            self::assertIsObject($cert);
        } else {
            self::assertIsResource($cert);
        }

        /** @var resource|\OpenSSLCertificate|mixed $cert */
        ['subject' => $subject, 'issuer' => $issuer] = openssl_x509_parse($cert, false) ?: [];
        self::assertEquals(self::$certSubject, $subject);
        self::assertEquals(self::$certSubject, $issuer);
    }

    public function testLoadPrivateKey(): void
    {
        [, , , $privateKeyFile] = self::$environment;
        $privateKey = PemUtil::loadPrivateKey($privateKeyFile);
        if (8 === PHP_MAJOR_VERSION) {
            self::assertIsObject($privateKey);
        } else {
            self::assertIsResource($privateKey);
        }
    }

    public function testLoadPrivateKeyFromString(): void
    {
        [, , , , $privateKeyString] = self::$environment;
        $privateKey = PemUtil::loadPrivateKeyFromString($privateKeyString);
        if (8 === PHP_MAJOR_VERSION) {
            self::assertIsObject($privateKey);
        } else {
            self::assertIsResource($privateKey);
        }
    }

    public function testParseCertificateSerialNo(): void
    {
        [$serialNo, $certFile, $certString] = self::$environment;
        $serialNoFromPemUtilFile = PemUtil::parseCertificateSerialNo(PemUtil::loadCertificate($certFile));
        $serialNoFromPemUtilString = PemUtil::parseCertificateSerialNo(PemUtil::loadCertificateFromString($certString));
        $serialNoFromCertString = PemUtil::parseCertificateSerialNo($certString);
        self::assertEquals($serialNo, $serialNoFromPemUtilFile);
        self::assertEquals($serialNo, $serialNoFromPemUtilString);
        self::assertEquals($serialNo, $serialNoFromCertString);
    }
}
