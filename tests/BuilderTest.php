<?php declare(strict_types=1);

namespace WeChatPay\Tests;

use function class_implements;
use function class_uses;
use function is_array;
use function array_map;
use function iterator_to_array;
use function openssl_pkey_get_private;
use function openssl_pkey_get_public;
use function sprintf;
use function method_exists;

use ArrayAccess;
use WeChatPay\Builder;
use WeChatPay\BuilderChainable;
use WeChatPay\Formatter;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/fixtures/mock.%s.%s';

    public function testConstractor(): void
    {
        $this->expectError();
        new Builder(); /** @phpstan-ignore-line */
    }

    /**
     * @return array<string,array{string,\OpenSSLAsymmetricKey|resource|string|mixed,\OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string|mixed,string,string}>
     */
    public function configurationDataProvider(): array
    {
        $privateKey = openssl_pkey_get_private('file://' . sprintf(static::FIXTURES, 'pkcs8', 'key'));
        $publicKey  = openssl_pkey_get_public('file://' . sprintf(static::FIXTURES, 'spki', 'pem'));

        if (false === $privateKey || false === $publicKey) {
            throw new \Exception('Loading the pkey failed.');
        }

        return [
            'standard' => ['1230000109', $privateKey, $publicKey, Formatter::nonce(40), Formatter::nonce(40)],
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param string $mchid
     * @param resource|mixed $privateKey
     * @param string|resource|mixed $publicKey
     * @param string $mchSerial
     * @param string $platSerial
     */
    public function testFactory(string $mchid, $privateKey, $publicKey, string $mchSerial, string $platSerial): void
    {
        $instance = Builder::factory([
            'mchid' => $mchid,
            'serial' => $mchSerial,
            'privateKey' => $privateKey,
            'certs' => [$platSerial => $publicKey],
        ]);

        $map = class_implements($instance);

        self::assertIsArray($map);
        self::assertNotEmpty($map);

        self::assertArrayHasKey(BuilderChainable::class, is_array($map) ? $map : []);
        self::assertContainsEquals(BuilderChainable::class, is_array($map) ? $map : []);

        self::assertInstanceOf(ArrayAccess::class, $instance);
        self::assertInstanceOf(BuilderChainable::class, $instance);

        $traits = class_uses($instance);

        self::assertIsArray($traits);
        self::assertNotEmpty($traits);
        self::assertContains(\WeChatPay\BuilderTrait::class, is_array($traits) ? $traits : []);

        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance->v3);
        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance->v3->pay->transcations->native);
        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance->v3->combineTransactions->{'{combine_out_trade_no}'});
        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance->V3->Marketing->Busifavor->Users['{openid}/coupons/{coupon_code}']->Appids['{appid}']);

        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance['v2/pay/micropay']);
        /** @phpstan-ignore-next-line */
        self::assertInstanceOf(BuilderChainable::class, $instance['v2/pay/refundquery']);

        self::assertInstanceOf(BuilderChainable::class, $instance->chain('what_ever_endpoints/with-anyDepths_segments/also/contains/{uri_template}/{blah}/blah/'));

        /** @phpstan-ignore-next-line */
        $copy = iterator_to_array($instance->v3->combineTransactions->{'{combine_out_trade_no}'});
        self::assertIsArray($copy);
        self::assertNotEmpty($copy);
        self::assertNotContains('combineTransactions', $copy);
        self::assertContains('combine-transactions', $copy);

        /** @phpstan-ignore-next-line */
        $copy = iterator_to_array($instance->V3->Marketing->Busifavor->Users['{openid}']->Coupons->{'{coupon_code}'}->Appids->_appid_);
        self::assertIsArray($copy);
        self::assertNotEmpty($copy);
        self::assertNotContains('V3', $copy);
        self::assertContains('v3', $copy);
        self::assertNotContains('_appid_', $copy);
        self::assertContains('{appid}', $copy);

        $context = $this;
        array_map(static function($item) use($context) {
            static::assertIsString($item);
            if (method_exists($context, 'assertMatchesRegularExpression')) {
                $context->assertMatchesRegularExpression('#[^A-Z]#', $item);
            } else {
                static::assertRegExp('#[^A-Z]#', $item);
            }
        }, $copy);
    }
}
