<?php declare(strict_types=1);

namespace WeChatPay\Crypto;

use function is_null;
use function hash_equals;
use function hash_init;
use function hash_update;
use function hash_final;
use function array_key_exists;
use function strtoupper;

use const HASH_HMAC;

const ALGO_MD5 = 'MD5';
const ALGO_HMAC_SHA256 = 'HMAC-SHA256';
const ALGO_DICTONARIES = [ALGO_HMAC_SHA256 => 'hmac', ALGO_MD5 => 'md5'];

/**
 * Crypto hash functions utils.
 * [Specification]{@link https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_3}
 */
class Hash
{
    /** @var string - hashing `MD5` algorithm */
    public const ALGO_MD5 = ALGO_MD5;

    /** @var string - hashing `HMAC-SHA256` algorithm */
    public const ALGO_HMAC_SHA256 = ALGO_HMAC_SHA256;

    /**
     * Calculate the input string with an optional secret `key` in MD5,
     * when the `key` is Falsey, this method works as normal `MD5`.
     *
     * @param string $thing - The input string.
     * @param string $key - The secret key string.
     * @param boolean|int|string $agency - The secret **key** is from work.weixin.qq.com, default is `false`,
     *                                     placed with `true` or better of the `AgentId` value.
     *                                     [spec]{@link https://work.weixin.qq.com/api/doc/90000/90135/90281}
     *
     * @return string - The data signature
     */
    public static function md5(string $thing, string $key = '', $agency = false): string
    {
        $ctx = hash_init(ALGO_MD5);

        hash_update($ctx, $thing) && $key && hash_update($ctx, $agency ? '&secret=' : '&key=') && hash_update($ctx, $key);

        return hash_final($ctx);
    }

    /**
     * Calculate the input string with a secret `key` as of `algorithm` string which is one of the 'sha256', 'sha512' etc.
     *
     * @param string $thing - The input string.
     * @param string $key - The secret key string.
     * @param string $algorithm - The algorithm string, default is `sha256`.
     *
     * @return string - The data signature
     */
    public static function hmac(string $thing, string $key, string $algorithm = 'sha256'): string
    {
        $ctx = hash_init($algorithm, HASH_HMAC, $key);

        hash_update($ctx, $thing) && hash_update($ctx, '&key=') && hash_update($ctx, $key);

        return hash_final($ctx);
    }

    /**
     * Wrapping the builtins `hash_equals` function.
     *
     * @param string $known_string - The string of known length to compare against.
     * @param ?string $user_string - The user-supplied string.
     *
     * @return bool - Returns true when the two are equal, false otherwise.
     */
    public static function equals(string $known_string, ?string $user_string = null): bool
    {
        return is_null($user_string) ? false : hash_equals($known_string, $user_string);
    }

    /**
     * Utils of the data signature calculation.
     *
     * @param string $type - The sign type, one of the `MD5` or `HMAC-SHA256`.
     * @param string $data - The input data.
     * @param string $key - The secret key string.
     *
     * @return ?string - The data signature in UPPERCASE.
     */
    public static function sign(string $type, string $data, string $key): ?string
    {
        return array_key_exists($type, ALGO_DICTONARIES) ? strtoupper(static::{ALGO_DICTONARIES[$type]}($data, $key)) : null;
    }
}
