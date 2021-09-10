<?php declare(strict_types=1);

namespace WeChatPay\Tests;

use const SORT_FLAG_CASE;
use const SORT_STRING;
use const SORT_NATURAL;
use const SORT_REGULAR;

use function method_exists;
use function strlen;
use function strval;
use function preg_quote;
use function substr_count;
use function count;
use function ksort;

use InvalidArgumentException;
use WeChatPay\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    private const LINE_FEED = "\n";

    /**
     * @return array<string,array{int,string}>
     */
    public function nonceRulesProvider(): array
    {
        return [
            'default $size=32'       => [32,  '/[a-zA-Z0-9]{32}/'],
            'half-default $size=16'  => [16,  '/[a-zA-Z0-9]{16}/'],
            'hundred $size=100'      => [100, '/[a-zA-Z0-9]{100}/'],
            'one $size=1'            => [1,   '/[a-zA-Z0-9]{1}/'],
            'zero $size=0'           => [0,   '#Size must be a positive integer\.#'],
            'negative $size=-1'      => [-1,  '#Size must be a positive integer\.#'],
            'negative $size=-16'     => [-16, '#Size must be a positive integer\.#'],
            'negative $size=-32'     => [-32, '#Size must be a positive integer\.#'],
        ];
    }

    /**
     * @dataProvider nonceRulesProvider
     */
    public function testNonce(int $size, string $pattern): void
    {
        if ($size < 1) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches($pattern);
        }

        $nonce = Formatter::nonce($size);

        self::assertIsString($nonce);

        self::assertTrue(strlen($nonce) === $size);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($pattern, $nonce);
        } else {
            self::assertRegExp($pattern, $nonce);
        }
    }

    public function testTimestamp(): void
    {
        $timestamp = Formatter::timestamp();
        $pattern = '/^1[0-9]{9}/';

        self::assertIsInt($timestamp);

        $timestamp = strval($timestamp);

        self::assertTrue(strlen($timestamp) === 10);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($pattern, $timestamp);
        } else {
            self::assertRegExp($pattern, $timestamp);
        }
    }

    public function testAuthorization(): void
    {
        $value = Formatter::authorization('1001', Formatter::nonce(), 'Cg==', (string) Formatter::timestamp(), 'mockmockmock');

        self::assertIsString($value);

        self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048 ', $value);
        self::assertStringEndsWith('"', $value);

        $pattern = '/^WECHATPAY2-SHA256-RSA2048 '
            . 'mchid="[0-9A-Za-z]{1,32}",'
            . 'serial_no="[0-9A-Za-z]{8,40}",'
            . 'timestamp="1[0-9]{9}",'
            . 'nonce_str="[0-9A-Za-z]{16,}",'
            . 'signature="[0-9A-Za-z\+\/]+={0,2}"$/';

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($pattern, $value);
        } else {
            self::assertRegExp($pattern, $value);
        }
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public function requestPhrasesProvider(): array
    {
        return [
            'DELETE root(/)' => ['DELETE', '/', ''],
            'DELETE root(/) with query' => ['DELETE', '/?hello=wechatpay', ''],
            'GET root(/)' => ['GET', '/', ''],
            'GET root(/) with query' => ['GET', '/?hello=wechatpay', ''],
            'POST root(/) with body' => ['POST', '/', '{}'],
            'POST root(/) with body and query' => ['POST', '/?hello=wechatpay', '{}'],
            'PUT root(/) with body' => ['PUT', '/', '{}'],
            'PUT root(/) with body and query' => ['PUT', '/?hello=wechatpay', '{}'],
            'PATCH root(/) with body' => ['PATCH', '/', '{}'],
            'PATCH root(/) with body and query' => ['PATCH', '/?hello=wechatpay', '{}'],
        ];
    }

    /**
     * @dataProvider requestPhrasesProvider
     */
    public function testRequest(string $method, string $uri, string $body): void
    {
        $value = Formatter::request($method, $uri, (string) Formatter::timestamp(), Formatter::nonce(), $body);

        self::assertIsString($value);

        self::assertStringStartsWith($method, $value);
        self::assertStringEndsWith(static::LINE_FEED, $value);
        self::assertLessThanOrEqual(substr_count($value, static::LINE_FEED), 5);

        $pattern = '#^' . $method . static::LINE_FEED
            .  preg_quote($uri) . static::LINE_FEED
            . '1[0-9]{9}' . static::LINE_FEED
            . '[0-9A-Za-z]{32}' . static::LINE_FEED
            . preg_quote($body) . static::LINE_FEED
            . '$#';

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($pattern, $value);
        } else {
            self::assertRegExp($pattern, $value);
        }
    }

    /**
     * @return array<string,array{string}>
     */
    public function responsePhrasesProvider(): array
    {
        return [
            'HTTP 200 STATUS with body' => ['{}'],
            'HTTP 200 STATUS with no body' => [''],
            'HTTP 202 STATUS with no body' => [''],
            'HTTP 204 STATUS with no body' => [''],
            'HTTP 301 STATUS with no body' => [''],
            'HTTP 301 STATUS with body' => ['<html></html>'],
            'HTTP 302 STATUS with no body' => [''],
            'HTTP 302 STATUS with body' => ['<html></html>'],
            'HTTP 307 STATUS with no body' => [''],
            'HTTP 307 STATUS with body' => ['<html></html>'],
            'HTTP 400 STATUS with body' => ['{}'],
            'HTTP 401 STATUS with body' => ['{}'],
            'HTTP 403 STATUS with body' => ['<html></html>'],
            'HTTP 404 STATUS with body' => ['<html></html>'],
            'HTTP 500 STATUS with body' => ['{}'],
            'HTTP 502 STATUS with body' => ['<html></html>'],
            'HTTP 503 STATUS with body' => ['<html></html>'],
        ];
    }

    /**
     * @dataProvider responsePhrasesProvider
     */
    public function testResponse(string $body): void
    {
        $value = Formatter::response((string) Formatter::timestamp(), Formatter::nonce(), $body);

        self::assertIsString($value);

        self::assertStringEndsWith(static::LINE_FEED, $value);
        self::assertLessThanOrEqual(substr_count($value, static::LINE_FEED), 3);

        $pattern = '#^1[0-9]{9}' . static::LINE_FEED
            . '[0-9A-Za-z]{32}' . static::LINE_FEED
            . preg_quote($body) . static::LINE_FEED
            . '$#';

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($pattern, $value);
        } else {
            self::assertRegExp($pattern, $value);
        }
    }

    /**
     * @return array<string,array{string|int|bool|null|float}>
     */
    public function joinedByLineFeedPhrasesProvider(): array
    {
        return [
            'one argument' => [1],
            'two arguments' => [1, '2'],
            'more arguments' => [1, 2.0, '3', static::LINE_FEED, true, false, null, '4'],
        ];
    }

    /**
     * @param string $data
     * @dataProvider joinedByLineFeedPhrasesProvider
     */
    public function testJoinedByLineFeed(...$data): void
    {
        $value = Formatter::joinedByLineFeed(...$data);

        self::assertIsString($value);

        self::assertStringEndsWith(static::LINE_FEED, $value);

        self::assertLessThanOrEqual(substr_count($value, static::LINE_FEED), count($data));
    }

    public function testNoneArgumentPassedToJoinedByLineFeed(): void
    {
        $value = Formatter::joinedByLineFeed();

        self::assertIsString($value);

        self::assertStringNotContainsString(static::LINE_FEED, $value);

        self::assertTrue(strlen($value) == 0);
    }

    /**
     * @return array<string,array<array<string,string>>>
     */
    public function ksortPhrasesProvider(): array
    {
        return [
            'normal' => [
                ['a' => '1', 'b' => '3', 'aa' => '2'],
                ['a' => '1', 'aa' => '2', 'b' => '3'],
            ],
            'key with numeric' => [
                ['rfc1' => '1', 'b' => '4', 'rfc822' => '2', 'rfc2086' => '3'],
                ['b' => '4', 'rfc1' => '1', 'rfc2086' => '3', 'rfc822' => '2'],
            ],
            'issue #41 `re_openid` with `remark` keys' => [
                [
                    'mch_billno' => 'etGkDmT3BJyuhnhU9d', 'mch_id' => 'xxxxx', 'wxappid' => 'wx01111111', 'send_name' => 'aaaaa', 're_openid' => 'o8xSOxxxxxxx',
                    'total_amount' => '100', 'total_num' => '1', 'wishing' => '红包祝福语', 'client_ip' => '192.168.0.1', 'act_name' => '活动名称', 'remark' => '备注',
                ],
                [
                    'act_name' => '活动名称', 'client_ip' => '192.168.0.1', 'mch_billno' => 'etGkDmT3BJyuhnhU9d', 'mch_id' => 'xxxxx', 're_openid' => 'o8xSOxxxxxxx',
                    'remark' => '备注', 'send_name' => 'aaaaa', 'total_amount' => '100', 'total_num' => '1', 'wishing' => '红包祝福语', 'wxappid' => 'wx01111111',
                ],
            ],
            'the key point of the issue #41 different' => [
                [
                    're_openid' => 'o8xSOxxxxxxx', 'remark' => '备注',
                ],
                [
                    // charcode of the `_` is 95, `m` is 109, the ordering should be the following
                    're_openid' => 'o8xSOxxxxxxx', 'remark' => '备注',
                ],
            ],
            'case-sensitive cover' => [
                [
                    'remark' => '备注', 'RE_OPENID' => 'RE_OPENID', 're_openid' => 'o8xSOxxxxxxx', 'REMARK' => 'REMARK',
                ],
                [
                    // charcode of the `R` is 82, 'M' is 77, `_` is 95, `m` is 109, the ordering should be the following
                    'REMARK' => 'REMARK', 'RE_OPENID' => 'RE_OPENID', 're_openid' => 'o8xSOxxxxxxx', 'remark' => '备注',
                ],
            ],
        ];
    }

    /**
     * @param array<string,string> $thing
     * @param array<string,string> $excepted
     * @dataProvider ksortPhrasesProvider
     */
    public function testKsort(array $thing, array $excepted): void
    {
        self::assertEquals(array_keys($excepted), array_keys(Formatter::ksort($thing)));
        self::assertEquals(array_values($excepted), array_values(Formatter::ksort($thing)));
    }

    /**
     * @return array<string,array{array<string,string>,array<string,string>,int,string}>
     */
    public function ksortWithDifferentFlagsPharasesProvider(): array
    {
        return [
            '`SORT_FLAG_CASE | SORT_NATURAL` flag' => [
                $input = ['remark' => '备注', 're_openid' => 'o8xSOxxxxxxx'],
                // charcode of the `_` is 95, `m` is 109, the ordering should be the following
                $excepted = ['re_openid' => 'o8xSOxxxxxxx', 'remark' => '备注'],
                SORT_FLAG_CASE | SORT_NATURAL,
                'assertNotEquals',
            ],
            '`SORT_FLAG_CASE | SORT_STRING` flag' => [
                $input,
                $excepted,
                SORT_FLAG_CASE | SORT_STRING,
                'assertEquals',
            ],
            '`SORT_NATURAL` flag' => [
                $input,
                $excepted,
                SORT_NATURAL,
                'assertEquals',
            ],
            'default `SORT_REGULAR` flag' => [
                $input,
                $excepted,
                SORT_REGULAR,
                'assertEquals',
            ],
            '`dictionary order` = `SORT_STRING` flag' => [
                $input,
                $excepted,
                SORT_STRING,
                'assertEquals',
            ],
            'case-insensitive with `SORT_FLAG_CASE | SORT_STRING` flag(NOT EQUALS)' => [
                $input = ['re_openid' => 'o8xSOxxxxxxx', 'REMARK' => 'REMARK', 'remark' => '备注', 'RE_OPENID' => 'RE_OPENID',],
                // charcode of the `R` is 82, 'M' is 77, `_` is 95, `m` is 109
                $excepted = ['REMARK' => 'REMARK', 'RE_OPENID' => 'RE_OPENID', 're_openid' => 'o8xSOxxxxxxx', 'remark' => '备注',],
                SORT_FLAG_CASE | SORT_STRING,
                'assertNotEquals',
            ],
            'case-sensitive with `SORT_STRING` flag' => [
                $input,
                $excepted,
                SORT_STRING,
                'assertEquals',
            ],
            'case-insensitive with `SORT_FLAG_CASE | SORT_STRING` flag(EQUALS)' => [
                $input,
                // dependency on the `\$input where the `REMARK` was listed before `remark`
                ['re_openid' => 'o8xSOxxxxxxx', 'RE_OPENID' => 'RE_OPENID', 'REMARK' => 'REMARK', 'remark' => '备注',],
                SORT_FLAG_CASE | SORT_STRING,
                'assertEquals',
            ],
        ];
    }

    /**
     * @param array<string,string> $input
     * @param array<string,string> $excepted
     * @param integer $flag
     * @param string $assertMethod
     * @dataProvider ksortWithDifferentFlagsPharasesProvider
     */
    public function testKsortWithDifferentFlags(array $input, array $excepted, int $flag, string $assertMethod): void
    {
        self::assertTrue(ksort($input, $flag));
        self::{$assertMethod}(array_keys($excepted), array_keys($input));
        self::{$assertMethod}(array_values($excepted), array_values($input));
    }

    /**
     * @return array<string,array<array<string,string>>>
     */
    public function nativeKsortPhrasesProvider(): array
    {
        return [
            'normal' => [
                ['a' => '1', 'b' => '3', 'aa' => '2'],
                ['a' => '1', 'aa' => '2', 'b' => '3'],
            ],
            'key with numeric' => [
                ['rfc1' => '1', 'b' => '4', 'rfc822' => '2', 'rfc2086' => '3'],
                ['b' => '4', 'rfc1' => '1', 'rfc2086' => '3', 'rfc822' => '2'],
            ],
        ];
    }

    /**
     * @param array<string,string> $thing
     * @param array<string,string> $excepted
     * @dataProvider nativeKsortPhrasesProvider
     */
    public function testNativeKsort(array $thing, array $excepted): void
    {
        self::assertTrue(ksort($thing));
        self::assertEquals($thing, $excepted);
    }

    /**
     * @return array<string,array{array<string,string|null>,string}>
     */
    public function queryStringLikePhrasesProvider(): array
    {
        return [
            'none specific chars' => [
                ['a' => '1', 'b' => '3', 'aa' => '2'],
                'a=1&b=3&aa=2',
            ],
            'has `sign` key' => [
                ['a' => '1', 'b' => '3', 'sign' => '2'],
                'a=1&b=3',
            ],
            'has `empty` value' => [
                ['a' => '1', 'b' => '3', 'c' => ''],
                'a=1&b=3',
            ],
            'has `null` value' => [
                ['a' => '1', 'b' => null, 'c' => '2'],
                'a=1&c=2',
            ],
            'mixed `sign` key, `empty` and `null` values' => [
                ['bob' => '1', 'alice' => null, 'tom' => '', 'sign' => 'mock'],
                'bob=1',
            ],
        ];
    }

    /**
     * @param array<string,string|null> $thing
     * @param string $excepted
     * @dataProvider queryStringLikePhrasesProvider
     */
    public function testQueryStringLike(array $thing, string $excepted): void
    {
        $value = Formatter::queryStringLike($thing);
        self::assertIsString($value);
        self::assertEquals($value, $excepted);
    }
}
