<?php declare(strict_types=1);

namespace WeChatPay\Tests;

use function strlen;
use function abs;
use function strval;

use WeChatPay\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    public function nonceRulesProvider(): array
    {
        return [
            'default $size=32'       => [32,  '/[a-zA-Z0-9]{32}/'],
            'half-default $size=16'  => [16,  '/[a-zA-Z0-9]{16}/'],
            'hundred $size=100'      => [100, '/[a-zA-Z0-9]{100}/'],
            'one $size=1'            => [1,   '/[a-zA-Z0-9]{1}/'],
            'zero $size=0'           => [0,   '/[a-zA-Z0-9]{2}/'],
            'negative $size=-1'      => [-1,  '/[a-zA-Z0-9]{3}/'],
            'negative $size=-16'     => [-16, '/[a-zA-Z0-9]{18}/'],
            'negative $size=-32'     => [-32, '/[a-zA-Z0-9]{34}/'],
        ];
    }

    /**
     * @dataProvider nonceRulesProvider
     */
    public function testNonce(int $size, string $pattern): void
    {
        $nonce = Formatter::nonce($size);

        self::assertIsString($nonce);

        self::assertTrue(strlen($nonce) === ($size > 0 ? $size : abs($size - 2)));

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            self::assertMatchesRegularExpression($pattern, $nonce);
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
            self::assertMatchesRegularExpression($pattern, $timestamp);
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
            . 'mchid="(?:[0-9A-Za-z]{1,32})",'
            . 'nonce_str="(?:[0-9A-Za-z]{16,})",'
            . 'signature="(?:[0-9A-Za-z+\/]+)={0,2}",'
            . 'timestamp="(?:1[0-9]{9})",'
            . 'serial_no="(?:[0-9A-Za-z]{8,40})"$/';

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            self::assertMatchesRegularExpression($pattern, $value);
        } else {
            self::assertRegExp($pattern, $value);
        }
    }
}
