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
     * @return array{\WeChatPay\BuilderChainable,HandlerStack}
     */
    private function newInstance(array $config): array
    {
        $instance = Builder::factory($config + ['handler' => $this->guzzleMockStack(),]);

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select()->getConfig('handler');
        $stack = clone $stack;
        $stack->remove('verifier');

        $stack->push(Middleware::tap(/* before */static function (RequestInterface $request) {
            // Note here: because the `$target` is used onto the `signature`, **IT IS NOT SAME TO** the original URI.
            // And **NO IDEA** about the platform HOW TO VERIFY the `media_slot_url` while there contains the double pct-encoded characters.
            $target = $request->getRequestTarget();
            self::assertNotEquals('/v3/merchant-service/images/' . self::MEDIA_ID, $target);
        }, /* after */static function (RequestInterface $request) {
            $target = $request->getRequestTarget();
            self::assertNotEquals('/v3/merchant-service/images/' . self::MEDIA_ID, $target);
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
        [$endpoint, $stack] = $this->newInstance($config);

        $this->mock->reset();
        $this->mock->append($respondor);
        $this->mock->append($respondor);

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
        [$endpoint, $stack] = $this->newInstance($config);

        $this->mock->reset();
        $this->mock->append($respondor);
        $this->mock->append($respondor);

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
}
