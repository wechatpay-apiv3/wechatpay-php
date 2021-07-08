<?php declare(strict_types=1);

namespace WeChatPay\Crypto;

use function openssl_encrypt;
use function base64_encode;
use function openssl_decrypt;
use function base64_decode;

use const OPENSSL_RAW_DATA;

use UnexpectedValueException;

/**
 * Aes encrypt/decrypt using `aes-256-ecb` algorithm with pkcs7padding.
 */
class AesEcb implements AesInterface
{
    /**
     * @inheritDoc
     */
    public static function encrypt(string $plaintext, string $key, string $iv = ''): string
    {
        $ciphertext = openssl_encrypt($plaintext, static::ALGO_AES_256_ECB, $key, OPENSSL_RAW_DATA, $iv = '');

        if (false === $ciphertext) {
            throw new UnexpectedValueException('Encrypting the input $plaintext failed, please checking your $key and $iv whether or nor correct.');
        }

        return base64_encode($ciphertext);
    }

    /**
     * @inheritDoc
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = ''): string
    {
        $plaintext = openssl_decrypt(base64_decode($ciphertext), static::ALGO_AES_256_ECB, $key, OPENSSL_RAW_DATA, $iv = '');

        if (false === $plaintext) {
            throw new UnexpectedValueException('Decrypting the input $ciphertext failed, please checking your $key and $iv whether or nor correct.');
        }

        return $plaintext;
    }
}
