<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Tools;

use function array_key_exists;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\RejectionException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\Transformer;
use WeChatPay\Formatter;
use WeChatPay\Crypto\Hash;

class AuthcodetoopenidTest extends TestCase
{
    private const SUCCESS = 'SUCCESS';

    private const FAIL = 'FAIL';

    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
    }

    /**
     * @param string $secret
     * @return \WeChatPay\BuilderChainable
     */
    private function prepareEnvironment(string $secret): \WeChatPay\BuilderChainable
    {
        $instance = Builder::factory([
            'mchid'      => '123',
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => $secret,
            'handler'    => $this->guzzleMockStack(),
        ]);

        $endpoint = $instance->chain('v2/tools/authcodetoopenid');

        return $endpoint;
    }

    /**
     * @return array<string,array{array<string,?string>,string,?string}>
     */
    public function mockDataProvider(): array
    {
        $serverFail = [
            'return_code' => self::FAIL,
            'return_msg'  => 'invalid reason',
        ];

        $serviceFail = [
            'return_code' => self::SUCCESS,
            'return_msg'  => '',
            'appid'       => '123',
            'mch_id'      => '123',
            'nonce_str'   => Formatter::nonce(),
            'sign'        => 'fake',
            'result_code' => self::FAIL,
            'err_code'    => 'AUTH_CODE_INVALID',
        ];

        $key1 = Formatter::nonce();
        $serviceFalsy = [
            'return_code' => self::SUCCESS,
            'return_msg'  => '',
            'appid'       => '123',
            'mch_id'      => '123',
            'nonce_str'   => Formatter::nonce(),
        ];
        $serviceFalsy['sign'] = Hash::sign(Hash::ALGO_MD5, Formatter::queryStringLike(Formatter::ksort($serviceFalsy)), $key1);

        $key2 = Formatter::nonce();
        $serviceTruthy = [
            'return_code' => self::SUCCESS,
            'return_msg'  => '',
            'appid'       => '123',
            'mch_id'      => '123',
            'nonce_str'   => Formatter::nonce(),
            'result_code' => self::SUCCESS,
            'err_code'    => '',
            'openid'      => '123',
        ];
        $serviceTruthy['sign'] = Hash::sign(Hash::ALGO_MD5, Formatter::queryStringLike(Formatter::ksort($serviceTruthy)), $key2);

        return [
            'return_code=FAIL then Exception occurred' => [$serverFail, Formatter::nonce(), RejectionException::class],
            'return_code=SUCCESS && result_code=FAIL then Exception occurred' => [$serviceFail, Formatter::nonce(), RejectionException::class],
            'return_code=SUCCESS && without `result_code` key then Passed with falsy data' => [$serviceFalsy, $key1, null],
            'return_code=SUCCESS && result_code=SUCCESS then Passed with truthy data' => [$serviceTruthy, $key2, null],
        ];
    }

    /**
     * @dataProvider mockDataProvider
     * @param array<string,string> $data
     * @param string $secret
     * @param ?string $expected
     */
    public function testResponseState(array $data, string $secret, ?string $expected = null): void
    {
        $endpoint = $this->prepareEnvironment($secret);

        $this->mock->reset();
        $this->mock->append(new Response(200, [], Transformer::toXml($data)));
        if (is_null($expected)) {
            $response = $endpoint->post(['xml' => ['appid' => '123', 'mch_id' => '123', 'auth_code' => '123']]);
            $xml = Transformer::toArray((string) $response->getBody());
            self::assertEquals($data, $xml);
        } else {
            try {
                $endpoint->post(['xml' => ['appid' => '123', 'mch_id' => '123', 'auth_code' => '123']]);
            } catch (\Throwable $e) {
                self::assertEquals($expected, $e::class);
                if ($e instanceof RejectionException && ($response = $e->getReason()) instanceof ResponseInterface) {
                    $err = Transformer::toArray((string)$response->getBody());
                    //three cases, maybe return_code and/or result_code 'FAIL'
                    self::assertEquals(self::FAIL, $err['result_code'] ?? $err['return_code'] ?? '');
                }
            }
        }
    }
}
