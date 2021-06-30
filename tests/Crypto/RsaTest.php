<?php

namespace WeChatPay\Tests\Crypto;

use function openssl_pkey_new;
use function openssl_pkey_get_details;
use function openssl_error_string;
use function fwrite;
use function dirname;

use const PHP_EOL;
use const PHP_SAPI;
use const STDERR;
use const OPENSSL_KEYTYPE_RSA;
use const DIRECTORY_SEPARATOR as DS;

use WeChatPay\Crypto\Rsa;
use PHPUnit\Framework\TestCase;


const BASE64_EXPRESSION = '#[a-zA-Z0-9\+\/]+#';

class RsaTest extends TestCase
{
    /**
     * @return array<string,array{string,string|resource|mixed,resource|mixed}>
     */
    public function keysProvider(): array
    {
        $privateKey = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'default_bits' => 2048,
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => dirname(__DIR__) . DS . 'fixtures' . DS . 'openssl.conf',
        ]);

        while ($msg = openssl_error_string()) {
            'cli' === PHP_SAPI && fwrite(STDERR, 'OpenSSL ' . $msg . PHP_EOL);
        }

        ['key' => $publicKey] = $privateKey ? openssl_pkey_get_details($privateKey) : [];

        return [
            'plaintext, publicKey and privateKey' => ['hello wechatpay 你好 微信支付', $publicKey, $privateKey]
        ];
    }

    /**
     * @dataProvider keysProvider
     * @param string $plaintext
     * @param object|resource|mixed $publicKey
     */
    public function testEncrypt(string $plaintext, $publicKey): void
    {
        $ciphertext = Rsa::encrypt($plaintext, $publicKey);
        self::assertIsString($ciphertext);
        self::assertNotEquals($plaintext, $ciphertext);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(BASE64_EXPRESSION, $ciphertext);
        }
    }

    /**
     * @dataProvider keysProvider
     * @param string $plaintext
     * @param object|resource|mixed $publicKey
     * @param object|resource|mixed $privateKey
     */
    public function testDecrypt(string $plaintext, $publicKey, $privateKey): void
    {
        $ciphertext = Rsa::encrypt($plaintext, $publicKey);
        self::assertIsString($ciphertext);
        self::assertNotEquals($plaintext, $ciphertext);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(BASE64_EXPRESSION, $ciphertext);
        }

        $mytext = Rsa::decrypt($ciphertext, $privateKey);
        self::assertIsString($mytext);
        self::assertEquals($plaintext, $mytext);
    }

    /**
     * @dataProvider keysProvider
     * @param string $plaintext
     * @param object|resource|mixed $publicKey
     * @param object|resource|mixed $privateKey
     */
    public function testSign(string $plaintext, $publicKey, $privateKey): void
    {
        $signature = Rsa::sign($plaintext, $privateKey);

        self::assertIsString($signature);
        self::assertNotEquals($plaintext, $signature);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(BASE64_EXPRESSION, $signature);
        } else {
            self::assertRegExp(BASE64_EXPRESSION, $signature);
        }
    }

    /**
     * @dataProvider keysProvider
     * @param string $plaintext
     * @param object|resource|mixed $publicKey
     * @param object|resource|mixed $privateKey
     */
    public function testVerify(string $plaintext, $publicKey, $privateKey): void
    {
        $signature = Rsa::sign($plaintext, $privateKey);

        self::assertIsString($signature);
        self::assertNotEquals($plaintext, $signature);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(BASE64_EXPRESSION, $signature);
        } else {
            self::assertRegExp(BASE64_EXPRESSION, $signature);
        }

        self::assertTrue(Rsa::verify($plaintext, $signature, $publicKey));
    }
}
