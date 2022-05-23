<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V3;

use function rtrim;
use function file_get_contents;
use function sprintf;
use function strlen;

use WeChatPay\Builder;
use WeChatPay\Formatter;
use WeChatPay\Crypto\Rsa;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

class StatementsTest extends TestCase
{
    private const FIXTURES = 'file://' . __DIR__ . '/../../fixtures/%s';

    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /**
     * @param array<string,mixed> $config
     * @return array{\WeChatPay\BuilderChainable}
     */
    private function newInstance(array $config): array
    {
        $instance = Builder::factory($config + ['handler' => $this->guzzleMockStack(),]);

        return [$instance];
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function mockDataProvider(): array
    {
        $mchid  = '1230000109';
        $mchSerial = rtrim(file_get_contents(sprintf(self::FIXTURES, 'mock.serial.txt')) ?: '');
        $mchPrivateKey = Rsa::from(sprintf(self::FIXTURES, 'mock.pkcs8.key'));
        $platSerial = Formatter::nonce(40);
        $platPublicKey = Rsa::from(sprintf(self::FIXTURES, 'mock.spki.pem'), Rsa::KEY_TYPE_PUBLIC);

        $stream = new LazyOpenStream(sprintf(self::FIXTURES, 'bill.ALL.csv'), 'rb');

        $platPrivateKey = Rsa::from(sprintf(self::FIXTURES, 'mock.pkcs1.key'));
        $signature = Rsa::sign(Formatter::response(
            $timestamp = (string)Formatter::timestamp(),
            $nonce = Formatter::nonce(),
            sprintf('{"sha1":"%s"}', $digest = Utils::hash($stream, 'SHA1'))
        ), $platPrivateKey);

        return [
            'configuration with base_uri' => [
                [
                    'base_uri' => 'https://api.mch.weixin.qq.com/hk/',
                    'mchid' => $mchid,
                    'serial' => $mchSerial,
                    'privateKey' => $mchPrivateKey,
                    'certs' => [$platSerial => $platPublicKey]
                ],
                new Response(200, [
                    'Content-Type' => 'text/plain',
                    'Wechatpay-Timestamp' => $timestamp,
                    'Wechatpay-Nonce' => $nonce,
                    'Wechatpay-Signature' => $signature,
                    'Wechatpay-Serial' => $platSerial,
                    'Wechatpay-Statement-Sha1' => $digest,
                ], $stream),
            ],
        ];
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param ResponseInterface $respondor
     */
    public function testGet(array $config, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->newInstance($config);

        $this->mock->reset();
        $this->mock->append($respondor);

        $response = $endpoint->chain('v3/statements')->get([
            'date' => '20180103',
        ]);
        self::responseAssertion($response);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertTrue($response->hasHeader('Wechatpay-Statement-Sha1'));
        self::assertNotEmpty($digest = $response->getHeaderLine('Wechatpay-Statement-Sha1'));
        self::assertTrue(strlen($digest) === 40);
        self::assertEquals($digest, Utils::hash($response->getBody(), 'SHA1'));
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param ResponseInterface $respondor
     */
    public function testGetAsync(array $config, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->newInstance($config);

        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint->chain('v3/statements')->getAsync([
            'date' => '20180103',
        ])->then(static function (ResponseInterface $response) {
            self::responseAssertion($response);
            $response->getBody()->close();// cleanup the opening file handler
        })->wait();
    }
}
