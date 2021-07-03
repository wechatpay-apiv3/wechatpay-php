<?php

namespace WeChatPay\Tests;

use function class_implements;
use function class_uses;
use function is_array;
use function openssl_pkey_new;
use function openssl_pkey_get_details;

use const OPENSSL_KEYTYPE_RSA;
use const DIRECTORY_SEPARATOR as DS;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use ReflectionClass;
use ReflectionMethod;

use WeChatPay\Formatter;
use WeChatPay\ClientDecorator;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

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
                '#the merchant\'s certificate serial number\(MCH123SERIAL\) which is not allowed here#',
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

    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /**
     * @return array<string,array{array<string,mixed>}>
     */
    public function constructorSuccessProvider(): array
    {
        return [
            'default' => [
                [
                    'mchid' => '1230000109', 'serial' => 'MCH123SERIAL', 'privateKey' => '------ BEGIN PRIVATE ------', 'certs' => ['PLAT123SERIAL' => ''],
                ],
            ],
            'with base_uri' => [
                [
                    'mchid' => '1230000109', 'serial' => 'MCH123SERIAL', 'privateKey' => '------ BEGIN PRIVATE ------', 'certs' => ['PLAT123SERIAL' => ''],
                    'base_uri' => 'https://api.mch.weixin.qq.com/hk/',
                ],
            ],
            'with base_uri and handler' => [
                [
                    'mchid' => '1230000109', 'serial' => 'MCH123SERIAL', 'privateKey' => '------ BEGIN PRIVATE ------', 'certs' => ['PLAT123SERIAL' => ''],
                    'base_uri' => 'https://apius.mch.weixin.qq.com/v3/sandbox/',
                    'handler' => $this->guzzleMockStack(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider constructorSuccessProvider
     *
     * @param array<string,mixed> $config
     */
    public function testConstructorSuccess(array $config): void
    {
        $instance = new ClientDecorator($config);

        self::assertInstanceOf(ClientDecoratorInterface::class, $instance);

        $client = $instance->select(ClientDecoratorInterface::JSON_BASED);
        self::assertInstanceOf(\GuzzleHttp\Client::class, $client);

        /** @var array<string,mixed> $settings */
        $settings = $client->getConfig(); // TODO: refactor while Guzzle8 dropped this API
        self::assertIsArray($settings);
        self::assertArrayHasKey('handler', $settings);
        /** @var HandlerStack $stack */
        ['handler' => $stack] = $settings;
        self::assertInstanceOf(HandlerStack::class, $stack);
        self::assertStringContainsString('verifier', $stack);
        self::assertStringContainsString('signer', $stack);
        self::assertStringNotContainsString('transform_request', $stack);
        self::assertStringNotContainsString('transform_response', $stack);

        $client = $instance->select(ClientDecoratorInterface::XML_BASED);
        self::assertInstanceOf(\GuzzleHttp\Client::class, $client);

        /** @var array<string,mixed> $settings */
        $settings = $client->getConfig(); // TODO: refactor while Guzzle8 dropped this API
        self::assertIsArray($settings);

        self::assertArrayHasKey('handler', $settings);
        /** @var HandlerStack $stack */
        ['handler' => $stack] = $settings;
        self::assertInstanceOf(HandlerStack::class, $stack);
        self::assertStringNotContainsString('verifier', $stack);
        self::assertStringNotContainsString('signer', $stack);
        self::assertStringContainsString('transform_request', $stack);
        self::assertStringContainsString('transform_response', $stack);
    }

    /**
     * @return array<string,array{string,resource|mixed,string|resource|mixed,string,string,object|mixed,string,string,string}>
     */
    public function withMockHandlerProvider(): array
    {
        $privateKey = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'default_bits' => 2048,
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => __DIR__ . DS . 'fixtures' . DS . 'openssl.conf',
        ]);

        ['key' => $publicKey] = $privateKey ? openssl_pkey_get_details($privateKey) : [];

        $mchid = '1230000109';
        $mchSerial = Formatter::nonce(40);
        $platSerial = Formatter::nonce(40);

        return [
            'HTTP 400 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(400), ClientException::class, 'GET', '/',
            ],
            'HTTP 401 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(401), ClientException::class, 'POST', '/next/aipay',
            ],
            'HTTP 403 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(403), ClientException::class, 'PUT', 'app/done',
            ],
            'HTTP 404 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(404), ClientException::class, 'DELETE', 'secapi/micro',
            ],
            'HTTP 429 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(429), ClientException::class, 'PATCH', 'sandboxnew',
            ],
            'HTTP 500 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(500), ServerException::class, 'POST', 'v3/sandbox',
            ],
            'HTTP 502 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(502), ServerException::class, 'PATCH', 'facepay',
            ],
            'HTTP 503 STATUS' => [
                $mchid, $privateKey, $publicKey, $mchSerial, $platSerial,
                new Response(503), ServerException::class, 'PUT', 'frog-over-miniprogram/pay',
            ],
        ];
    }

    /**
     * @dataProvider withMockHandlerProvider
     *
     * @param string $mchid
     * @param resource|mixed $privateKey
     * @param string|resource|mixed $publicKey
     * @param string $mchSerial
     * @param string $platSerial
     * @param ResponseInterface $response
     * @param class-string<\Psr\Http\Client\RequestExceptionInterface> $expectedGuzzleException
     * @param string $method
     * @param string $uri
     */
    public function testRequestsWithMockHandler(
        string $mchid, $privateKey, $publicKey, string $mchSerial, string $platSerial,
        ResponseInterface $response, string $expectedGuzzleException, string $method, string $uri): void
    {
        $instance = new ClientDecorator([
            'mchid' => $mchid,
            'serial' => $mchSerial,
            'privateKey' => $privateKey,
            'certs' => [$platSerial => $publicKey],
            'handler' => $this->guzzleMockStack(),
        ]);

        $this->mock->reset();
        $this->mock->append($response);
        $this->expectException($expectedGuzzleException);

        $instance->request($method, $uri);
    }

    /**
     * @dataProvider withMockHandlerProvider
     *
     * @param string $mchid
     * @param resource|mixed $privateKey
     * @param string|resource|mixed $publicKey
     * @param string $mchSerial
     * @param string $platSerial
     * @param ResponseInterface $response
     * @param class-string<\Psr\Http\Client\RequestExceptionInterface> $expectedGuzzleException
     * @param string $method
     * @param string $uri
     */
    public function testAsyncRequestsWithMockHandler(
        string $mchid, $privateKey, $publicKey, string $mchSerial, string $platSerial,
        ResponseInterface $response, string $expectedGuzzleException, string $method, string $uri): void
    {
        $instance = new ClientDecorator([
            'mchid' => $mchid,
            'serial' => $mchSerial,
            'privateKey' => $privateKey,
            'certs' => [$platSerial => $publicKey],
            'handler' => $this->guzzleMockStack(),
        ]);

        $this->mock->reset();
        $this->mock->append($response);

        $instance->requestAsync($method, $uri)->otherwise(static function($actual) use ($expectedGuzzleException) {
            self::assertInstanceOf($expectedGuzzleException, $actual);
        })->wait();
    }
}
