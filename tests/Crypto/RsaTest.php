<?php declare(strict_types=1);

namespace WeChatPay\Tests\Crypto;

use WeChatPay\Crypto\Rsa;
use PHPUnit\Framework\TestCase;

class RsaTest extends TestCase
{
    private const BASE64_EXPRESSION = '#^[a-zA-Z0-9\+/]+={0,2}$#';

    private const FIXTURES = __DIR__ . '/../fixtures/mock.%s.%s';

    /**
     * @return array<string,array{string,string|\OpenSSLAsymmetricKey|resource|mixed,string|\OpenSSLAsymmetricKey|resource|mixed}>
     */
    public function keysProvider(): array
    {
        $privateKey = openssl_pkey_get_private('file://' . sprintf(static::FIXTURES, 'pkcs8', 'key'));
        $publicKey  = openssl_pkey_get_public('file://' . sprintf(static::FIXTURES, 'sha256', 'crt'));

        if (false === $privateKey || false === $publicKey) {
            throw new \Exception('Loading the pkey failed.');
        }

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
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
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
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
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
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $signature);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $signature);
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
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $signature);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $signature);
        }

        self::assertTrue(Rsa::verify($plaintext, $signature, $publicKey));
    }
}
