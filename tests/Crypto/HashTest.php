<?php declare(strict_types=1);

namespace WeChatPay\Tests\Crypto;

use function md5;
use function hash_hmac;
use function strlen;
use function is_null;
use function random_bytes;
use function method_exists;

use WeChatPay\Formatter;
use WeChatPay\Crypto\Hash;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    public function testClassConstants(): void
    {
        self::assertIsString(Hash::ALGO_MD5);
        self::assertIsString(Hash::ALGO_HMAC_SHA256);
    }

    /**
     * @return array<string,array{string,string,string|bool|int,string,string,int}>
     */
    public function md5DataProvider(): array
    {
        return [
            'without key equals to normal md5' => [
                $txt = Formatter::nonce(30),
                '',
                '',
                md5($txt),
                'assertEquals',
                32,
            ],
            'input agency, but without key equals to normal md5' => [
                $txt = Formatter::nonce(60),
                '',
                true,
                md5($txt),
                'assertEquals',
                32,
            ],
            'random key without agency' => [
                $txt = Formatter::nonce(90),
                Formatter::nonce(),
                '',
                md5($txt),
                'assertNotEquals',
                32,
            ],
            'random key with agency:true' => [
                $txt = Formatter::nonce(200),
                Formatter::nonce(),
                true,
                md5($txt),
                'assertNotEquals',
                32,
            ],
        ];
    }

    /**
     * @dataProvider md5DataProvider
     * @param string $thing
     * @param string $key
     * @param string $agency
     * @param string $excepted
     * @param string $action
     * @param int $length
     */
    public function testMd5(string $thing, string $key, $agency, string $excepted, string $action, int $length): void
    {
        $digest = Hash::md5($thing, $key, $agency);

        self::assertIsString($digest);
        self::assertNotEmpty($digest);
        self::assertNotEquals($thing, $digest);
        self::assertEquals(strlen($digest), $length);
        self::{$action}($digest, $excepted);
    }

    /**
     * @return array<string,array{string,string,string|bool|int,string,string,int}>
     */
    public function hmacDataProvider(): array
    {
        return [
            'not equals to normal hash_hmac:md5' => [
                $txt = Formatter::nonce(900),
                $key = Formatter::nonce(),
                $algo = 'md5',
                hash_hmac($algo, $txt, $key),
                'assertNotEquals',
                32,
            ],
            'not equals to normal hash_hmac:sha256' => [
                $txt = Formatter::nonce(600),
                $key = Formatter::nonce(),
                $algo = 'sha256',
                hash_hmac($algo, $txt, $key),
                'assertNotEquals',
                64,
            ],
            'not equals to normal hash_hmac:sha384' => [
                $txt = Formatter::nonce(300),
                $key = Formatter::nonce(),
                $algo = 'sha384',
                hash_hmac($algo, $txt, $key),
                'assertNotEquals',
                96,
            ],
        ];
    }

    /**
     * @dataProvider hmacDataProvider
     * @param string $thing
     * @param string $key
     * @param string $algorithm
     * @param string $excepted
     * @param string $action
     * @param int $length
     */
    public function testHmac(string $thing, string $key, string $algorithm, string $excepted, string $action, int $length): void
    {
        $digest = Hash::hmac($thing, $key, $algorithm);

        self::assertIsString($digest);
        self::assertNotEmpty($digest);
        self::assertNotEquals($thing, $digest);
        self::assertEquals(strlen($digest), $length);
        self::{$action}($digest, $excepted);
    }

    /**
     * @return array<string,array{string,string|null,bool}>
     */
    public function equalsDataProvider(): array
    {
        return [
            'empty string equals to empty string' => ['', '', true],
            'empty string not equals to null'     => ['', null, false],
            'random_bytes(16) not equals to null' => [random_bytes(16), null, false],
        ];
    }

    /**
     * @dataProvider equalsDataProvider
     *
     * @param string $known
     * @param ?string $user
     * @param bool $excepted
     */
    public function testEquals(string $known, ?string $user = null, bool $excepted = false): void
    {
        $result = Hash::equals($known, $user);
        self::assertIsBool($result);
        self::assertThat($result, $excepted ? self::isTrue() : self::isFalse());
    }

    /**
     * @return array<string,array{string,string,string|bool|int,string,string,int|null}>
     */
    public function signDataProvider(): array
    {
        return [
            'not equals to normal Hash::md5' => [
                $txt = Formatter::nonce(900),
                $key = Formatter::nonce(),
                Hash::ALGO_MD5,
                Hash::md5($txt, $key),
                'assertNotEquals',
                32,
            ],
            'not equals to normal Hash::hmac' => [
                $txt = Formatter::nonce(600),
                $key = Formatter::nonce(),
                Hash::ALGO_HMAC_SHA256,
                Hash::hmac($txt, $key),
                'assertNotEquals',
                64,
            ],
            'not support algo sha256' => [
                $txt = Formatter::nonce(300),
                $key = Formatter::nonce(),
                'sha256',
                '',
                'assertNull',
                null,
            ],
        ];
    }

    /**
     * @dataProvider signDataProvider
     * @param string $type
     * @param string $thing
     * @param string $key
     * @param string $excepted
     * @param string $action
     * @param ?int $length
     */
    public function testSign(string $thing, string $key, string $type, string $excepted, string $action, ?int $length = null): void
    {
        $digest = Hash::sign($type, $thing, $key);
        if (is_null($length)) {
            self::{$action}($digest);
        } else {
            self::assertNotNull($digest);
            self::assertIsString($digest);
            self::assertNotEmpty($digest);
            self::assertNotEquals($thing, $digest);
            self::assertEquals(is_null($digest) ? 0 : strlen($digest), $length);
            self::{$action}($digest, $excepted);
            if (method_exists($this, 'assertMatchesRegularExpression')) {
                $this->assertMatchesRegularExpression('#[A-Z]+#', is_null($digest) ? '' : $digest);
            } else {
                self::assertRegExp('#[A-Z]+#', is_null($digest) ? '' : $digest);
            }
        }
    }
}
