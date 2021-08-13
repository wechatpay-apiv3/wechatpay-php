<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI;

use WeChatPay\Builder;
use WeChatPay\Formatter;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\RejectionException;
use PHPUnit\Framework\TestCase;
use WeChatPay\Transformer;

class V2MmpaymkttransfersPromotionTransfersTest extends TestCase
{
    /** @var MockHandler $mock */
    private $mock;

    private function guzzleMockStack(): HandlerStack
    {
        $this->mock = new MockHandler();

        return HandlerStack::create($this->mock);
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
    public function testTransfersRequest(string $mchid, ResponseInterface $respondor): void
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => Formatter::nonce(32),
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/mmpaymkttransfers/promotion/transfers');
        try {
            // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
            @$endpoint->post(['xml' => [ 'mchid' => $mchid, ]]);
        } catch (RejectionException $e) {
            /** @var ResponseInterface $res */
            $res = $e->getReason();
            $txt = (string) $res->getBody();
            $array = Transformer::toArray($txt);
            static::assertArrayHasKey('mchid', $array);
            static::assertArrayHasKey('return_code', $array);
            static::assertArrayHasKey('result_code', $array);
        }
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param ResponseInterface $respondor
     */
    public function testTransfersAsyncRequest(string $mchid, ResponseInterface $respondor): void
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => Formatter::nonce(32),
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/mmpaymkttransfers/promotion/transfers');

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync(['xml' => [ 'mchid' => $mchid, ]])->otherwise(static function($res) {
            $txt = (string) $res->getBody();
            $array = Transformer::toArray($txt);
            static::assertArrayHasKey('mchid', $array);
            static::assertArrayHasKey('return_code', $array);
            static::assertArrayHasKey('result_code', $array);
        })->wait();
    }
}
