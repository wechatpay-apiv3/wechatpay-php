<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Pay;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\Crypto\Hash;
use WeChatPay\Formatter;
use WeChatPay\Transformer;
use PHPUnit\Framework\TestCase;

class QuerycombinedorderTest extends TestCase
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
     * @return array{\WeChatPay\BuilderChainable}
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

        $endpoint = $instance->chain('v2/pay/querycombinedorder');

        return [$endpoint];
    }

    /**
     * @return array<string,array{string,string,array<string,string>,ResponseInterface}>
     */
    public function mockRequestsDataProvider(): array
    {
        $mchid  = '1230000109';
        $secret = Formatter::nonce(32);
        $data = [
            'combine_mch_id' => $mchid,
            'combine_appid'  => 'wx8888888888888888',
            'combine_openid' => 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o',
            'sub_order_list' => (string)json_encode(['order_num' => 1, 'order_list' => []]),
            'return_code'    => 'SUCCESS',
            'result_code'    => 'SUCCESS',
        ];
        $data['sign'] = Hash::sign(Hash::ALGO_MD5, Formatter::queryStringLike(Formatter::ksort($data)), $secret);
        $xmlDataStructure = [
            'combine_appid'        => 'wx8888888888888888',
            'combine_mch_id'       => $mchid,
            'combine_out_trade_no' => '1217752501201407033233368018',
        ];

        return [
            'return_code=SUCCESS' => [$mchid, $secret, $xmlDataStructure, new Response(200, [], Transformer::toXml($data))],
        ];
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param array<string,string> $data
     * @param ResponseInterface $respondor
     */
    public function testPost(string $mchid, string $secret, array $data, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        $res = $endpoint->post(['xml' => $data]);
        self::responseAssertion($res);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        $txt = (string) $response->getBody();
        $array = Transformer::toArray($txt);
        static::assertArrayHasKey('combine_mch_id', $array);
        static::assertArrayHasKey('return_code', $array);
        static::assertArrayHasKey('result_code', $array);
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param array<string,string> $data
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, string $secret, array $data, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint->postAsync([
            'xml' => $data,
        ])->then(static function(ResponseInterface $res) {
            self::responseAssertion($res);
        })->wait();
    }
}
