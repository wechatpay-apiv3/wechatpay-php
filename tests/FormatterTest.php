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
}
