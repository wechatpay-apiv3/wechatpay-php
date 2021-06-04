<?php

namespace WechatPay\GuzzleMiddleware;

/**
 * Provides easy used methods using in this project.
 */
class Formatter
{
    /**
     * Generate a random ASCII string aka `nonce`, similar as `random_bytes`.
     *
     * @param int [$size = 32] - Nonce string length, default is 32.
     *
     * @return string - base62 random string.
     */
    public static function nonce(int $size = 32): string
    {
        static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return \preg_replace_callback('/0/', function() use (&$chars) { return $chars[\rand(0, 61)]; }, \str_repeat('0', $size));
    }

    /**
     * Retrieve the current `Unix` timestamp.
     *
     * @return int - Epoch timestamp.
     */
    public static function timestamp(): int
    {
        return \time();
    }

    /**
     * Formatting for the heading `Authorization` value.
     *
     * @param string|int $mchid - The merchant ID.
     * @param string $nonce - The Nonce string.
     * @param string $signature - The base64-encoded `Rsa::sign` ciphertext.
     * @param string|int $timestamp - The `Unix` timestamp.
     * @param string $serial - The serial number of the merchant public certification.
     *
     * @return string - The APIv3 Authorization `header` value
     */
    public static function authorization(string $mchid, string $nonce, string $signature, string $timestamp, string $serial): string
    {
        return \sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%s",serial_no="%s"',
            $mchid, $nonce, $signature, $timestamp, $serial
        );
    }

    /**
     * Formatting this `HTTP::request` for `Rsa::sign` input.
     *
     * @param string $method - The HTTP verb, must be the uppercase sting.
     * @param string $uri - Combined string with `URL::pathname` and `URL::search`.
     * @param string|int $timestamp - The `Unix` timestamp, should be the one used in `authorization`.
     * @param string $nonce - The `Nonce` string, should be the one used in `authorization`.
     * @param string $body - The playload string, HTTP `GET` should be an empty string.
     *
     * @return string - The content for `Rsa::sign`
     */
    public static function request(string $method, string $uri, string $timestamp, string $nonce, string $body = ''): string
    {
        return static::joinedByLineFeed($method, $uri, $timestamp, $nonce, $body);
    }

    /**
     * Formatting this `HTTP::response` for `Rsa::verify` input.
     *
     * @param string|int $timestamp - The `Unix` timestamp, should be the one from `response::headers[Wechatpay-Timestamp]`.
     * @param string $nonce - The `Nonce` string, should be the one from `response::headers[Wechatpay-Nonce]`.
     * @param string $body - The response payload string, HTTP status(`201`, `204`) should be an empty string.
     *
     * @return string - The content for `Rsa::verify`
     */
    public static function response(string $timestamp, string $nonce, string $body = ''): string
    {
        return static::joinedByLineFeed($timestamp, $nonce, $body);
    }

    /**
     * Joined this inputs by for `Line Feed`(LF) char.
     *
     * @param string[] ...$pieces - The string(s) joined by line feed.
     *
     * @return string - The joined string.
     */
    public static function joinedByLineFeed(...$pieces): string
    {
        return \implode("\n", \array_merge($pieces, ['']));
    }
}
