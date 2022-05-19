<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Mmpaymkttransfers\Promotion;

use WeChatPay\Builder;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
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

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select(ClientDecoratorInterface::XML_BASED)->getConfig('handler');
        $stack = clone $stack;
        $stack->remove('transform_response');

        $endpoint = $instance->chain('v2/mmpaymkttransfers/promotion/transfers');

        return [$endpoint, $stack];
    }

    /**
     * @return array<string,array{string,array<string,string>,ResponseInterface}>
     */
    public function mockRequestsDataProvider(): array
    {
        return [
            'return_code=SUCCESS' => [
                $mchid = '1230000109',
                [
                    'mchid'            => $mchid,
                    'mch_appid'        => 'wx8888888888888888',
                    'device_info'      => '013467007045764',
                    'partner_trade_no' => '10000098201411111234567890',
                    'openid'           => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
                    'check_name'       => 'FORCE_CHECK',
                    're_user_name'     => '王小王',
                    'amount'           => '10099',
                    'desc'             => '理赔',
                    'spbill_create_ip' => '192.168.0.1',
                ],
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
     * @param array<string,string> $data
     * @param ResponseInterface $respondor
     */
    public function testPost(string $mchid, array $data, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->prepareEnvironment($mchid);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post(['xml' => $data, 'handler' => $stack]);
        self::responseAssertion($res);

        $this->mock->reset();
        $this->mock->append($respondor);
        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post(['xml' => $data]);
        self::responseAssertion($res);
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
     * @param array<string,string> $data
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, array $data, ResponseInterface $respondor): void
    {
        [$endpoint, $stack] = $this->prepareEnvironment($mchid);

        $this->mock->reset();
        $this->mock->append($respondor);

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'xml' => $data, 'handler' => $stack
        ])->then(static function(ResponseInterface $res) {
            self::responseAssertion($res);
        })->wait();

        $this->mock->reset();
        $this->mock->append($respondor);
        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'xml' => $data
        ])->then(static function(ResponseInterface $res) {
            self::responseAssertion($res);
        })->wait();
    }
}
