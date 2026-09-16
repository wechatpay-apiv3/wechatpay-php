<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V3\MerchantService\Images;

use function ltrim;
use function rtrim;
use function file_get_contents;
use function sprintf;
use function version_compare;

use Composer\InstalledVersions;
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

    /** @var string The `self::MEDIA_ID` whose pct-encoded triplet was encoded once more, aka the double pct-encoded */
    private const MEDIA_ID_DOUBLE_ENCODED = 'ChsyMDAyMDgwMjAyMjAyMTgxMTA0NDEzMTEwMzASGzMwMDIwMDAyMDIyMDIxODE1MDQ0MTcwOTI5NhgAIO%252FFR1pAGKAMwAjgB';

    /** @var string The request target prefix of the images downloading API */
    private const TARGET_PREFIX = '/v3/merchant-service/images/';

    /**
     * The `{+var}` reserved expansion preserves the pct-encoded triplets since
     * `guzzlehttp/uri-template@v1.0.6`(PHP>=7.2.5), the elder ones double-encode them.
     *
     * Determined by the installed version rather than by expanding the template, because the
     * expander is exact the one under testing, ref guzzle/uri-template#18.
     */
    private static function reservedExpansionTarget(): string
    {
        $version = ltrim((string) InstalledVersions::getPrettyVersion('guzzlehttp/uri-template'), 'v');

        return self::TARGET_PREFIX . (version_compare($version, '1.0.6', '>=')
            ? self::MEDIA_ID : self::MEDIA_ID_DOUBLE_ENCODED);
    }

    /**
     * @param array<string,mixed> $config
     * @param string $expectedTarget
     * @return array{\WeChatPay\BuilderChainable,HandlerStack}
     */
    private function newInstance(array $config, string $expectedTarget): array
    {
        $instance = Builder::factory($config + ['handler' => $this->guzzleMockStack(),]);

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select()->getConfig('handler');
        $stack = clone $stack;
        $stack->remove('verifier');

        $stack->push(Middleware::tap(/* before */static function (RequestInterface $request) use ($expectedTarget) {
            self::assertTrue($request->hasHeader('Authorization'));
            self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $request->getHeaderLine('Authorization'));

            self::assertEquals($expectedTarget, $request->getRequestTarget());
        }, /* after */static function (RequestInterface $request) use ($expectedTarget) {
            self::assertTrue($request->hasHeader('Authorization'));
            self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $request->getHeaderLine('Authorization'));

            self::assertEquals($expectedTarget, $request->getRequestTarget());
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
        // Note here: the `{var}` simple expansion pct-encodes the `%` of the `$slot`, which means the
        // request target is **NOT SAME TO** the original URI, while the `$slot` is used onto the `signature` algorithm.
        // More @see https://github.com/guzzle/uri-template/issues/18
        // And **NO IDEA** about the platform HOW TO VERIFY the `$slot` while there contains the double pct-encoded characters.
        [$endpoint, $stack] = $this->newInstance($config, self::TARGET_PREFIX . self::MEDIA_ID_DOUBLE_ENCODED);

        $this->mock->reset();
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
    }

    /**
     * The `{+var}` reserved expansion shall keep the `$slot` as is, so that the request target is
     * identical to the original one, which is essential to the `signature` algorithm.
     *
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $slot
     * @param ResponseInterface $respondor
     */
    public function testGetWithReservedExpansion(array $config, string $slot, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->newInstance($config, self::reservedExpansionTarget());

        $this->mock->reset();
        $this->mock->append($respondor);

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
        // Note here: the `{var}` simple expansion pct-encodes the `%` of the `$slot`, which means the
        // request target is **NOT SAME TO** the original URI, while the `$slot` is used onto the `signature` algorithm.
        // More @see https://github.com/guzzle/uri-template/issues/18
        // And **NO IDEA** about the platform HOW TO VERIFY the `$slot` while there contains the double pct-encoded characters.
        [$endpoint, $stack] = $this->newInstance($config, self::TARGET_PREFIX . self::MEDIA_ID_DOUBLE_ENCODED);

        $this->mock->reset();
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
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $slot
     * @param ResponseInterface $respondor
     */
    public function testGetAsyncWithReservedExpansion(array $config, string $slot, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->newInstance($config, self::reservedExpansionTarget());

        $this->mock->reset();
        $this->mock->append($respondor);

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
        [$endpoint, $stack] = $this->newInstance($config, self::TARGET_PREFIX . self::MEDIA_ID);

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
