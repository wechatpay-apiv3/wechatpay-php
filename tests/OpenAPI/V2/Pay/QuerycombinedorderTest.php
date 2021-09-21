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
     * @return array<string,array{string,string,ResponseInterface}>
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
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => $secret,
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/pay/querycombinedorder');

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post(['xml' => [ 'combine_mch_id' => $mchid, ]]);
        static::responseAssertion($res);
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
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, string $secret, ResponseInterface $respondor): void
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => $secret,
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/pay/querycombinedorder');

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'xml' => [ 'combine_mch_id' => $mchid, ],
        ])->then(static function(ResponseInterface $res) {
            static::responseAssertion($res);
        })->wait();
    }
}
