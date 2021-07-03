<?php

namespace WeChatPay\Tests;

use function class_implements;
use function class_uses;
use function is_array;

use ReflectionClass;
use ReflectionMethod;

use WeChatPay\ClientDecorator;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ClientDecoratorTest extends TestCase
{
    public function testImplementsClientDecoratorInterface(): void
    {
        $map = class_implements(ClientDecorator::class);

        self::assertIsArray($map);
        self::assertNotEmpty($map);
        self::assertArrayHasKey(ClientDecoratorInterface::class, is_array($map) ? $map : []);
        self::assertContainsEquals(ClientDecoratorInterface::class, is_array($map) ? $map : []);
    }

    public function testClassUsesTraits(): void
    {
        $traits = class_uses(ClientDecorator::class);

        self::assertIsArray($traits);
        self::assertNotEmpty($traits);
        self::assertContains(\WeChatPay\ClientJsonTrait::class, is_array($traits) ? $traits : []);
        self::assertContains(\WeChatPay\ClientXmlTrait::class, is_array($traits) ? $traits : []);
    }

    public function testClassConstants(): void
    {
        self::assertIsString(ClientDecorator::VERSION);
        self::assertIsString(ClientDecorator::XML_BASED);
        self::assertIsString(ClientDecorator::JSON_BASED);
    }

    public function testByReflectionClass(): void
    {
        $ref = new ReflectionClass(ClientDecorator::class);
        self::assertInstanceOf(ReflectionClass::class, $ref);

        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        self::assertIsArray($methods);

        self::assertTrue($ref->isFinal());
        self::assertTrue($ref->hasMethod('select'));
        self::assertTrue($ref->hasMethod('request'));
        self::assertTrue($ref->hasMethod('requestAsync'));

        $traits = $ref->getTraitNames();
        self::assertIsArray($traits);
        self::assertContains(\WeChatPay\ClientJsonTrait::class, $traits);
        self::assertContains(\WeChatPay\ClientXmlTrait::class, $traits);
    }

    /**
     * @return array<string,array{array<string,mixed>,string}>
     */
    public function constructorExceptionsProvider(): array
    {
        return [
            'none args passed' => [
                [],
                '#`mchid` is required#',
            ],
            'only `mchid` passed' => [
                ['mchid' => '1230000109',],
                '#`serial` is required#',
            ],
            '`mchid` and `serial` passed' => [
                ['mchid' => '1230000109', 'serial' => 'MCH123SERIAL',],
                '#`privateKey` is required#',
            ],
            '`mchid`, `serial` and `priviateKey` in' => [
                ['mchid' => '1230000109', 'serial' => 'MCH123SERIAL', 'privateKey' => '------ BEGIN PRIVATE ------',],
                '#`certs` is required#',
            ],
            '`mchid`, `serial`, `priviateKey` and bad `certs` in' => [
                ['mchid' => '1230000109', 'serial' => 'MCH123SERIAL', 'privateKey' => '------ BEGIN PRIVATE ------', 'certs' => ['MCH123SERIAL' => '']],
                '#the merchant\'s certificate serial number.*? is not allowed here#',
            ],
        ];
    }

    /**
     * @dataProvider constructorExceptionsProvider
     * @param array<string,mixed> $config
     * @param string $pattern
     */
    public function testConstructorExceptions(array $config, string $pattern): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($pattern);
        new ClientDecorator($config);
    }
}
