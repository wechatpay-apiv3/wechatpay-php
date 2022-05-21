<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V3\MerchantService\Images;

use function rtrim;
use function file_get_contents;
use function sprintf;

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class DownloadTest extends TestCase
{
    private const FIXTURES = 'file://' . __DIR__ . '/../../../../fixtures/%s';

    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /** @var string The media_slot_url by a community reporting */
    private const MEDIA_ID = 'ChsyMDAyMDgwMjAyMjAyMTgxMTA0NDEzMTEwMzASGzMwMDIwMDAyMDIyMDIxODE1MDQ0MTcwOTI5NhgAIO%2FFR1pAGKAMwAjgB';

    /**
     * @param array<string,mixed> $config
     * @param string $assertMethod
     * @return array{\WeChatPay\BuilderChainable,HandlerStack}
     */
    private function newInstance(array $config, string $assertMethod): array
    {
        $instance = Builder::factory($config + ['handler' => $this->guzzleMockStack(),]);

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select()->getConfig('handler');
        $stack = clone $stack;
        $stack->remove('verifier');

        $stack->push(Middleware::tap(/* before */static function (RequestInterface $request) use ($assertMethod) {
            self::assertTrue($request->hasHeader('Authorization'));
            self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $request->getHeaderLine('Authorization'));

            $target = $request->getRequestTarget();
            self::{$assertMethod}('/v3/merchant-service/images/' . self::MEDIA_ID, $target);
        }, /* after */static function (RequestInterface $request) use ($assertMethod) {
            self::assertTrue($request->hasHeader('Authorization'));
            self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $request->getHeaderLine('Authorization'));

            $target = $request->getRequestTarget();
            self::{$assertMethod}('/v3/merchant-service/images/' . self::MEDIA_ID, $target);
        }));

        return [$instance, $stack];
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function mockDataProvider(): array
    {
        $mchid  = '1230000109';
        $mchSerial = rtrim(file_get_contents(sprintf(self::FIXTURES, 'mock.serial.txt')) ?: '');
        $mchPrivateKey = Rsa::from(sprintf(self::FIXTURES, 'mock.pkcs8.key'));

        $stream = new LazyOpenStream(sprintf(self::FIXTURES, 'logo.png'), 'rb');

        return [
            'PNG image stream with the raw mediaId' => [
                ['mchid' => $mchid, 'serial' => $mchSerial, 'privateKey' => $mchPrivateKey, 'certs' => ['nop' => null]],
                self::MEDIA_ID,
                new Response(200, ['Content-Type' => 'image/png'], $stream),
            ],
        ];
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $slot
     * @param ResponseInterface $respondor
     */
    public function testGet(array $config, string $slot, ResponseInterface $respondor): void
    {
        // Note here: using the `UriTemplate` may be caused that, **IT IS NOT SAME TO** the original URI,
        // because the `$slot` is used onto the `signature` algorithm.
        // More @see https://github.com/guzzle/uri-template/issues/18
        // And **NO IDEA** about the platform HOW TO VERIFY the `$slot` while there contains the double pct-encoded characters.
        [$endpoint, $stack] = $this->newInstance($config, 'assertNotEquals');

        $this->mock->reset();
        $this->mock->append($respondor);
        $this->mock->append($respondor);
        $this->mock->append($respondor);

        $response = $endpoint->chain('v3/merchant-service/images/{media_slot_url}')->get([
            'media_slot_url' => $slot,
        ]);
        self::responseAssertion($response);

        $response = $endpoint->chain('v3/merchant-service/images/{media_slot_url}')->get([
            'handler' => $stack,
            'media_slot_url' => $slot,
        ]);
        self::responseAssertion($response);

        $response = $endpoint->chain('v3/merchant-service/images/{+media_slot_url}')->get([
            'handler' => $stack,
            'media_slot_url' => $slot,
        ]);
        self::responseAssertion($response);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertStringStartsWith('image/', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $slot
     * @param ResponseInterface $respondor
     */
    public function testGetAsync(array $config, string $slot, ResponseInterface $respondor): void
    {
        // Note here: using the `UriTemplate` may be caused that, **IT IS NOT SAME TO** the original URI,
        // because the `$slot` is used onto the `signature` algorithm.
        // More @see https://github.com/guzzle/uri-template/issues/18
        // And **NO IDEA** about the platform HOW TO VERIFY the `$slot` while there contains the double pct-encoded characters.
        [$endpoint, $stack] = $this->newInstance($config, 'assertNotEquals');

        $this->mock->reset();
        $this->mock->append($respondor);
        $this->mock->append($respondor);
        $this->mock->append($respondor);

        $endpoint->chain('v3/merchant-service/images/{media_slot_url}')->getAsync([
            'media_slot_url' => $slot,
        ])->then(static function (ResponseInterface $response) {
            self::responseAssertion($response);
        })->wait();

        $endpoint->chain('v3/merchant-service/images/{media_slot_url}')->getAsync([
            'handler' => $stack,
            'media_slot_url' => $slot,
        ])->then(static function(ResponseInterface $response) {
            self::responseAssertion($response);
        })->wait();

        $endpoint->chain('v3/merchant-service/images/{+media_slot_url}')->getAsync([
            'handler' => $stack,
            'media_slot_url' => $slot,
        ])->then(static function (ResponseInterface $response) {
            self::responseAssertion($response);
        })->wait();
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $slot
     * @param ResponseInterface $respondor
     */
    public function testUseStandardGuzzleHttpClient(array $config, string $slot, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->newInstance($config, 'assertEquals');

        $relativeUrl = 'v3/merchant-service/images/' . $slot;
        $fullUri = 'https://api.mch.weixin.qq.com/' . $relativeUrl;

        $apiv3Client = $endpoint->getDriver()->select();
        self::assertInstanceOf(ClientInterface::class, $apiv3Client);

        $this->mock->reset();

        $this->mock->append($respondor);
        $response = $apiv3Client->request('GET', $relativeUrl);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        $response = $apiv3Client->request('GET', $relativeUrl, ['handler' => $stack]);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        $response = $apiv3Client->request('GET', $fullUri);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        $response = $apiv3Client->request('GET', $fullUri, ['handler' => $stack]);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `get` method signature */
        $response = $apiv3Client->get($relativeUrl);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `get` method signature */
        $response = $apiv3Client->get($relativeUrl, ['handler' => $stack]);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `get` method signature */
        $response = $apiv3Client->get($fullUri);
        self::responseAssertion($response);

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `get` method signature */
        $response = $apiv3Client->get($fullUri, ['handler' => $stack]);
        self::responseAssertion($response);

        $asyncAssertion = static function (ResponseInterface $response) {
            self::responseAssertion($response);
        };

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `getAsync` method signature */
        $response = $apiv3Client->getAsync($fullUri)->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `getAsync` method signature */
        $response = $apiv3Client->getAsync($fullUri, ['handler' => $stack])->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `getAsync` method signature */
        $response = $apiv3Client->getAsync($relativeUrl)->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        /** @phpstan-ignore-next-line because of \GuzzleHttp\ClientInterface no `getAsync` method signature */
        $response = $apiv3Client->getAsync($relativeUrl, ['handler' => $stack])->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        $response = $apiv3Client->requestAsync('GET', $relativeUrl)->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        $response = $apiv3Client->requestAsync('GET', $relativeUrl, ['handler' => $stack])->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        $response = $apiv3Client->requestAsync('GET', $fullUri)->then($asyncAssertion)->wait();

        $this->mock->append($respondor);
        $response = $apiv3Client->requestAsync('GET', $fullUri, ['handler' => $stack])->then($asyncAssertion)->wait();
    }
}
