<?php declare(strict_types=1);

namespace WeChatPay;

use function array_reduce;
use function mt_rand;
use function range;
use function time;
use function sprintf;
use function implode;
use function array_merge;
use function ksort;
use function is_null;

use const SORT_FLAG_CASE;
use const SORT_NATURAL;

/**
 * Provides easy used methods using in this project.
 */
class Formatter
{
    /**
     * Generate a random ASCII string aka `nonce`, similar as `random_bytes`.
     *
     * @param int $size - Nonce string length, default is 32.
     *
     * @return string - base62 random string.
     */
    public static function nonce(int $size = 32): string
    {
        return array_reduce(range(1, $size), static function(string $char) {
            return $char .= 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[mt_rand(0, 61)];
        }, '');
    }

    /**
     * Retrieve the current `Unix` timestamp.
     *
     * @return int - Epoch timestamp.
     */
    public static function timestamp(): int
    {
        return time();
    }

    /**
     * Formatting for the heading `Authorization` value.
     *
     * @param string $mchid - The merchant ID.
     * @param string $nonce - The Nonce string.
     * @param string $signature - The base64-encoded `Rsa::sign` ciphertext.
     * @param string $timestamp - The `Unix` timestamp.
     * @param string $serial - The serial number of the merchant public certification.
     *
     * @return string - The APIv3 Authorization `header` value
     */
    public static function authorization(string $mchid, string $nonce, string $signature, string $timestamp, string $serial): string
    {
        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",serial_no="%s",timestamp="%s",nonce_str="%s",signature="%s"',
            $mchid, $serial, $timestamp, $nonce, $signature
        );
    }

    /**
     * Formatting this `HTTP::request` for `Rsa::sign` input.
     *
     * @param string $method - The HTTP verb, must be the uppercase sting.
     * @param string $uri - Combined string with `URL::pathname` and `URL::search`.
     * @param string $timestamp - The `Unix` timestamp, should be the one used in `authorization`.
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
     * @param string $timestamp - The `Unix` timestamp, should be the one from `response::headers[Wechatpay-Timestamp]`.
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
        return implode("\n", array_merge($pieces, ['']));
    }

    /**
     * Sort an array by key with `SORT_FLAG_CASE | SORT_NATURAL` flag.
     *
     * @param array<string, string|int> $thing - The input array.
     *
     * @return array<string, string|int> - The sorted array.
     */
    public static function ksort(array $thing = []): array
    {
        ksort($thing, SORT_FLAG_CASE | SORT_NATURAL);

        return $thing;
    }

    /**
     * Like `queryString` does but without the `sign` and `empty value` entities.
     *
     * @param array<string, string|int|null> $thing - The input array.
     *
     * @return string - The `key=value` pair string whose joined by `&` char.
     */
    public static function queryStringLike(array $thing = []): string
    {
        $data = [];

        foreach ($thing as $key => $value) {
            if ($key === 'sign' || is_null($value) || $value === '') {
                continue;
            }
            $data[] = implode('=', [$key, $value]);
        }

        return implode('&', $data);
    }
}
