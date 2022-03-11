<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V3\MerchantService\Images;

use function rtrim;
use function file_get_contents;
use function sprintf;
use function strtoupper;

use WeChatPay\Builder;
use WeChatPay\BuilderChainable;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\MediaUtil;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use WeChatPay\Formatter;

class UploadTest extends TestCase
{
    private const FIXTURES = 'file://' . __DIR__ . '/../../../../fixtures/%s';
    private const MEDIA_JSON = '{"media_id":"BB04A5DEEFEA18D4F2554C1EDD3B610B.bmp"}';

    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function newInstance(array $config): BuilderChainable
    {
        $check = static function (RequestInterface $request) {
            self::assertTrue($request->hasHeader('Authorization'));
            self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $request->getHeaderLine('Authorization'));
            self::assertEquals('/v3/merchant-service/images/upload', $request->getRequestTarget());
            self::assertTrue($request->hasHeader('Content-Type'));
            self::assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));
            self::assertInstanceOf(FnStream::class, $request->getBody());
        };

        $instance = Builder::factory($config + ['handler' => $this->guzzleMockStack(),]);

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select()->getConfig('handler');
        $stack->push(Middleware::tap(/* before */$check, /* after */$check));

        return $instance;
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function mockDataProvider(): array
    {
        $mchid  = '1230000109';
        $mchSerial = rtrim(file_get_contents(sprintf(self::FIXTURES, 'mock.serial.txt')) ?: '');
        $mchPrivateKey = Rsa::from(sprintf(self::FIXTURES, 'mock.pkcs1.key'));

        $platPrivateKey = Rsa::from(sprintf(self::FIXTURES, 'mock.pkcs8.key'));
        $platPublicKey = Rsa::from(sprintf(self::FIXTURES, 'mock.spki.pem'), Rsa::KEY_TYPE_PUBLIC);
        $platSerial = strtoupper(Formatter::nonce(40));

        return [
            'image upload' => [
                ['mchid' => $mchid, 'serial' => $mchSerial, 'privateKey' => $mchPrivateKey, 'certs' => [$platSerial => $platPublicKey]],
                sprintf(self::FIXTURES, 'logo.png'),
                new Response(200, [
                    'Content-Type' => 'application/json',
                    'Wechatpay-Serial' => $platSerial,
                    'Wechatpay-Nonce' => $nonce = Formatter::nonce(),
                    'Wechatpay-Timestamp' => $timestamp = (string) Formatter::timestamp(),
                    'Wechatpay-Signature' => Rsa::sign(Formatter::response($timestamp, $nonce, self::MEDIA_JSON), $platPrivateKey),
                ], self::MEDIA_JSON),
            ],
        ];
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $file
     * @param ResponseInterface $respondor
     */
    public function testPost(array $config, string $file, ResponseInterface $respondor): void
    {
        $endpoint = $this->newInstance($config);
        $media = new MediaUtil($file);

        $this->mock->reset();
        $this->mock->append($respondor);

        $response = $endpoint->chain('v3/merchant-service/images/upload')->post([
            'body' => $media->getStream(),
            'headers' => ['Content-Type' => $media->getContentType()],
        ]);
        self::responseAssertion($response);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        self::assertEquals(self::MEDIA_JSON, (string) $response->getBody());
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,mixed> $config
     * @param string $file
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(array $config, string $file, ResponseInterface $respondor): void
    {
        $endpoint = $this->newInstance($config);
        $media = new MediaUtil($file);

        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint->chain('v3/merchant-service/images/upload')->postAsync([
            'body' => $media->getStream(),
            'headers' => ['Content-Type' => $media->getContentType()],
        ])->then(static function(ResponseInterface $response) {
            self::responseAssertion($response);
        })->wait();
    }
}
