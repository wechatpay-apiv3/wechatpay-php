<?php

namespace WeChatPay\Tests\Crypto;

use function openssl_pkey_new;
use function openssl_pkey_get_details;

use const OPENSSL_KEYTYPE_RSA;

use WeChatPay\Crypto\Rsa;
use PHPUnit\Framework\TestCase;

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
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);
        ['key' => $publicKey] = $privateKey ? openssl_pkey_get_details($privateKey) : [];

        return [
            'plaintext, publicKey and privateKey' => ['hello wechatpay', $publicKey, $privateKey]
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
        $mytext = Rsa::decrypt($ciphertext, $privateKey);
        self::assertIsString($mytext);
        self::assertEquals($plaintext, $mytext);
    }
}
