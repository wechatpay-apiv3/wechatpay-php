<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Secapi\Mch;

use const DIRECTORY_SEPARATOR;

use function basename;
use function dirname;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\MultipartStream;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\Crypto\Hash;
use WeChatPay\Formatter;
use WeChatPay\Transformer;
use WeChatPay\ClientDecoratorInterface;
use PHPUnit\Framework\TestCase;

class UploadmediaTest extends TestCase
{
    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /**
     * @param string $mchid
     * @param string $secret
     * @return array{\WeChatPay\BuilderChainable,HandlerStack,MultipartStream}
     */
    private function prepareEnvironment(string $mchid, string $secret): array
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => $secret,
            'handler'    => $this->guzzleMockStack(),
        ]);

        // samples howto control the `HandlerStack`, only effect this request
        $stack = clone $instance->getDriver()->select(ClientDecoratorInterface::XML_BASED)->getConfig('handler');
        /** @var HandlerStack $stack */
        $stack->remove('transform_request');

        $endpoint = $instance->chain('v2/secapi/mch/uploadmedia');

        $logo  = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'logo.png';
        $media = new LazyOpenStream($logo, 'rb');

        $data = ['mch_id' => $mchid, 'media_hash' => Utils::hash($media, 'md5'),];
        $data['sign'] = Hash::sign(Hash::ALGO_MD5, Formatter::queryStringLike(Formatter::ksort($data)), $secret);
        $elements = [['name' => 'media', 'contents' => $media, 'filename' => basename($logo),]];
        foreach($data as $key => $value) {
            $elements[] = ['name' => $key, 'contents' => $value];
        }
        $body = new MultipartStream($elements);

        return [$endpoint, $stack, $body];
    }

    /**
     * @return array<string,array{string,string,ResponseInterface}>
     */
    public function mockRequestsDataProvider(): array
    {
        $mchid  = '1230000109';
        $secret = Formatter::nonce(32);
        $data = [
            'media_id'    => Formatter::nonce(107),
            'return_code' => 'SUCCESS',
            'return_msg'  => 'OK',
            'result_code' => 'SUCCESS',
        ];
        $data['sign'] = Hash::sign(Hash::ALGO_MD5, Formatter::queryStringLike(Formatter::ksort($data)), $secret);
        return [
            'return_code=SUCCESS' => [$mchid, $secret, new Response(200, [], Transformer::toXml($data))],
        ];
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param ResponseInterface $respondor
     */
    public function testPost(string $mchid, string $secret, ResponseInterface $respondor): void
    {
        [$endpoint, $stack, $body] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post([
            'body' => $body,
            'handler' => $stack,
            // 'ssl_key' => 'file:///path/to/apiclient_key.pem',
            // 'cert' => 'file:///path/to/apiclient_cert.pem',
        ]);
        static::responseAssertion($res);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        $txt = (string) $response->getBody();
        $array = Transformer::toArray($txt);
        static::assertArrayHasKey('sign', $array);
        static::assertArrayHasKey('media_id', $array);
        static::assertArrayHasKey('return_code', $array);
        static::assertArrayHasKey('result_code', $array);
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, string $secret, ResponseInterface $respondor): void
    {
        [$endpoint, $stack, $body] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'body' => $body,
            'handler' => $stack,
            // 'ssl_key' => 'file:///path/to/apiclient_key.pem',
            // 'cert' => 'file:///path/to/apiclient_cert.pem',
        ])->then(static function(ResponseInterface $res) {
            static::responseAssertion($res);
        })->wait();
    }
}
