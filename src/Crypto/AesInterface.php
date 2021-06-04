<?php

namespace WechatPay\GuzzleMiddleware\Crypto;

/**
 * Advanced Encryption Standard Interface
 */
interface AesInterface
{
    /**
     * Bytes Length of the AES block
     */
    const BLOCK_SIZE = 16;

    /**
     * Bytes length of the AES secret key.
     */
    const KEY_LENGTH_BYTE = 32;

    /**
     * Bytes Length of the authentication tag in AEAD cipher mode
     * @deprecated
     */
    const AUTH_TAG_LENGTH_BYTE = 16;

    /**
     * The `aes-256-gcm` algorithm string
     */
    const ALGO_AES_256_GCM = 'aes-256-gcm';

    /**
     * Encrypts given data with given method and key, returns a base64 encoded string.
     *
     * @param string $plaintext - Text to encode.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     *
     * @return string - The base64-encoded ciphertext.
     */
    public static function encrypt(string $plaintext, string $key, string $iv = ''): string;

    /**
     * Takes a base64 encoded string and decrypts it using a given method and key.
     *
     * @param string $ciphertext - The base64-encoded ciphertext.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     *
     * @return string - The utf-8 plaintext.
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = ''): string;
}
