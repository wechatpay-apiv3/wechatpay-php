<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Papay;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\Crypto\Hash;
use WeChatPay\Formatter;
use WeChatPay\Transformer;
use PHPUnit\Framework\TestCase;

class H5entrustwebTest extends TestCase
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

        $endpoint = $instance->chain('v2/papay/h5entrustweb');

        return [$endpoint];
    }

    /**
     * @return array<string,array{string,string,array<string,int|string>,ResponseInterface}>
     */
    public function mockRequestsDataProvider(): array
    {
        $mchid  = '1230000109';
        $secret = Formatter::nonce(32);
        $queryData = [
            'appid'                    => 'wxcbda96de0b165486',
            'mch_id'                   => $mchid,
            'plan_id'                  => '12535',
            'contract_code'            => '100000',
            'request_serial'           => 1000,
            'contract_display_account' => '微信代扣',
            'notify_url'               => 'https://weixin.qq.com',
            'version'                  => '1.0',
            'sign_type'                => 'HMAC-SHA256',
            'timestamp'                => Formatter::timestamp(),
            'return_appid'             => 'wxcbda96de0b165486',
        ];
        $responseData = [
            'return_code'  => 'SUCCESS',
            'return_msg'   => '',
            'result_code'  => 'SUCCESS',
            'result_msg'   => '',
            'redirect_url' => 'https://payapp.weixin.qq.com',
        ];

        return [
            'return_code=SUCCESS' => [$mchid, $secret, $queryData, new Response(200, [], Transformer::toXml($responseData))],
        ];
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param array<string,int|string> $query
     * @param ResponseInterface $respondor
     */
    public function testGet(string $mchid, string $secret, array $query, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        $res = $endpoint->get(['nonceless' => true, 'query' => $query]);
        self::responseAssertion($res);
    }

    /**
     * @param ResponseInterface $response
     */
    private static function responseAssertion(ResponseInterface $response): void
    {
        $txt = (string) $response->getBody();
        $array = Transformer::toArray($txt);
        static::assertArrayHasKey('redirect_url', $array);
        static::assertArrayHasKey('return_code', $array);
        static::assertArrayHasKey('result_code', $array);
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param string $secret
     * @param array<string,string> $query
     * @param ResponseInterface $respondor
     */
    public function testGetAsync(string $mchid, string $secret, array $query, ResponseInterface $respondor): void
    {
        [$endpoint] = $this->prepareEnvironment($mchid, $secret);

        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint->getAsync([
            'nonceless' => true,
            'query' => $query,
        ])->then(static function(ResponseInterface $res) {
            self::responseAssertion($res);
        })->wait();
    }
}
