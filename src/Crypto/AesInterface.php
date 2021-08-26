<?php declare(strict_types=1);

namespace WeChatPay\Crypto;

/**
 * Advanced Encryption Standard Interface
 */
interface AesInterface
{
    /**
     * Bytes Length of the AES block
     */
    public const BLOCK_SIZE = 16;

    /**
     * Bytes length of the AES secret key.
     */
    public const KEY_LENGTH_BYTE = 32;

    /**
     * Bytes Length of the authentication tag in AEAD cipher mode
     * @deprecated 1.0 - As of the OpenSSL described, the `auth_tag` length may be one of 16, 15, 14, 13, 12, 8 or 4.
     *                   Keep it only compatible for the samples on the official documentation.
     */
    public const AUTH_TAG_LENGTH_BYTE = 16;

    /**
     * The `aes-256-gcm` algorithm string
     */
    public const ALGO_AES_256_GCM = 'aes-256-gcm';

    /**
     * The `aes-256-ecb` algorithm string
     */
    public const ALGO_AES_256_ECB = 'aes-256-ecb';

    /**
     * Encrypts given data with given key and iv, returns a base64 encoded string.
     *
     * @param string $plaintext - Text to encode.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     *
     * @return string - The base64-encoded ciphertext.
     */
    public static function encrypt(string $plaintext, string $key, string $iv = ''): string;

    /**
     * Takes a base64 encoded string and decrypts it using a given key and iv.
     *
     * @param string $ciphertext - The base64-encoded ciphertext.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     *
     * @return string - The utf-8 plaintext.
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = ''): string;
}
