<?php

namespace WechatPay\GuzzleMiddleware\Crypto;

use function openssl_encrypt;
use function base64_encode;
use function openssl_decrypt;
use function base64_decode;

use const OPENSSL_RAW_DATA;

/**
 * Aes encrypt/decrypt using `aes-256-ecb` algorithm with pkcs7padding.
 */
class AesEcb implements AesInterface
{
    /**
     * @inheritDoc
     */
    static public function encrypt(string $plaintext, string $key, string $iv = ''): string
    {
        $ciphertext = openssl_encrypt($plaintext, static::ALGO_AES_256_ECB, $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($ciphertext);
    }

    /**
     * @inheritDoc
     */
    static public function decrypt(string $ciphertext, string $key, string $iv = ''): string
    {
        return openssl_decrypt(base64_decode($ciphertext), static::ALGO_AES_256_ECB, $key, OPENSSL_RAW_DATA, $iv);
    }
}
