<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Mmpaymkttransfers\Promotion;

use WeChatPay\Builder;
use WeChatPay\Formatter;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\RejectionException;
use PHPUnit\Framework\TestCase;
use WeChatPay\Transformer;
use WeChatPay\ClientDecoratorInterface;

class TransfersTest extends TestCase
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
     * @return array{\WeChatPay\BuilderChainable,HandlerStack}
     */
    private function prepareEnvironment(string $mchid): array
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => '',
            'handler'    => $this->guzzleMockStack(),
        ]);

        // samples howto control the `HandlerStack`, only effect this request
        $stack = clone $instance->getDriver()->select(ClientDecoratorInterface::XML_BASED)->getConfig('handler');
        /** @var HandlerStack $stack */
        $stack->remove('transform_response');

        $endpoint = $instance->chain('v2/mmpaymkttransfers/promotion/transfers');

        return [$endpoint, $stack];
    }

    /**
     * @return array<string,array{string,ResponseInterface}>
     */
    public function mockRequestsDataProvider(): array
    {
        return [
            'return_code=SUCCESS' => [
                $mchid = '1230000109',
                new Response(200, [], Transformer::toXml([
                    'mchid'       => $mchid,
                    'return_code' => 'SUCCESS',
                    'result_code' => 'SUCCESS',
                ]))
            ],
        ];
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param ResponseInterface $respondor
     */
    public function testPost(string $mchid, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->prepareEnvironment($mchid);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post(['xml' => [ 'mchid' => $mchid, ], 'handler' => $stack]);
        static::responseAssertion($res);

        $this->mock->reset();
        $this->mock->append($respondor);
        try {
            // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
            @$endpoint->post(['xml' => [ 'mchid' => $mchid, ]]);
        } catch (RejectionException $e) {
            /** @var ResponseInterface $res */
            $res = $e->getReason();
            static::responseAssertion($res);
        }
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        $txt = (string) $response->getBody();
        $array = Transformer::toArray($txt);
        static::assertArrayHasKey('mchid', $array);
        static::assertArrayHasKey('return_code', $array);
        static::assertArrayHasKey('result_code', $array);
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->prepareEnvironment($mchid);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'xml' => [ 'mchid' => $mchid, ], 'handler' => $stack
        ])->then(static function(ResponseInterface $res) {
            static::responseAssertion($res);
        })->wait();

        $this->mock->reset();
        $this->mock->append($respondor);
        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'xml' => [ 'mchid' => $mchid, ]
        ])->otherwise(static function($res) {
            static::responseAssertion($res);
        })->wait();
    }
}
