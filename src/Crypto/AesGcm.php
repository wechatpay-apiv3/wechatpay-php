<?php
/**
 * AesUtil
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChatPay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Crypto;

use WechatPay\GuzzleMiddleware\Crypto\AesInterface;

/**
 * Util for AEAD_AES_256_GCM.
 *
 * @package  WechatPay
 * @author   WeChatPay Team
 */
class AesGcm implements AesInterface
{
    /**
     * Encrypts given data with given method and key, returns a base64 encoded string.
     *
     * @param string $plaintext - Text to encode.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     * @param string $aad - The additional authenticated data, maybe empty string.
     *
     * @return string - The base64-encoded ciphertext.
     */
    public static function encrypt(string $plaintext, string $key, string $iv = '', string $aad = ''): string
    {
        if (!in_array(static::ALGO_AES_256_GCM, \openssl_get_cipher_methods())) {
            throw new \RuntimeException('It looks like the ext-openssl extension missing the `aes-256-gcm` cipher method.');
        }

        $ciphertext = openssl_encrypt($plaintext, static::ALGO_AES_256_GCM, $key, \OPENSSL_RAW_DATA, $iv, $tag, $aad, static::BLOCK_SIZE);

        return \base64_encode($ciphertext . $tag);
    }

    /**
     * Takes a base64 encoded string and decrypts it using a given method and key.
     *
     * @param string $ciphertext - The base64-encoded ciphertext.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     * @param string $aad - The additional authenticated data, maybe empty string.
     *
     * @return string - The utf-8 plaintext.
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = '', string $aad = ''): string
    {
        if (!in_array(static::ALGO_AES_256_GCM, \openssl_get_cipher_methods())) {
            throw new \RuntimeException('It looks like the ext-openssl extension missing the `aes-256-gcm` cipher method.');
        }

        $ciphertext = \base64_decode($ciphertext);
        $authTag = \substr($ciphertext, -static::BLOCK_SIZE);
        $tagLength = \strlen($authTag);

        /* Manually checking the length of the tag, because the `openssl_decrypt` was mentioned there, it's the caller's responsibility. */
        if ($tagLength > static::BLOCK_SIZE || ($tagLength < 12 && $tagLength !== 8 && $tagLength !== 4)) {
            throw new \RuntimeException('The inputs `$ciphertext` incomplete, the bytes length must be one of 16, 15, 14, 13, 12, 8 or 4.');
        }

        return \openssl_decrypt(\substr($ciphertext, 0, -static::BLOCK_SIZE), static::ALGO_AES_256_GCM, $key, \OPENSSL_RAW_DATA, $iv, $authTag, $aad);
    }
}
