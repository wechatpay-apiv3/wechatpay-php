<?php declare(strict_types=1);

namespace WeChatPay\Tests\OpenAPI\V2\Pay;

use const DIRECTORY_SEPARATOR;

use function dirname;
use function substr_count;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\Transformer;
use WeChatPay\ClientDecoratorInterface;
use PHPUnit\Framework\TestCase;

class DownloadbillTest extends TestCase
{
    private const CSV_DATA_LINE_MAXIMUM_BYTES = 1024;
    private const CSV_DATA_FIRST_BYTE = '`';
    private const CSV_DATA_SEPERATOR = ',`';

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
        $mchid  = '1230000109';
        $data = [
            'return_code' => 'FAIL',
            'return_msg'  => 'invalid reason',
            'error_code'  => '20001'
        ];
        $file   = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'bill.ALL.csv';
        $stream = new LazyOpenStream($file, 'rb');
        return [
            'return_code=FAIL' => [$mchid, new Response(200, [], Transformer::toXml($data))],
            'CSV stream'       => [$mchid, new Response(200, [], $stream)],
        ];
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param ResponseInterface $respondor
     */
    public function testPost(string $mchid, ResponseInterface $respondor): void
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => '',
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/pay/downloadbill');
        $stack = clone $endpoint->getDriver()->select(ClientDecoratorInterface::XML_BASED)->getConfig('handler');
        /** @var HandlerStack $stack */
        $stack->remove('transform_response');

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        $res = @$endpoint->post([
            'handler' => $stack,
            'xml' => [
                'appid'     => 'wx8888888888888888',
                'mch_id'    => $mchid,
                'bill_type' => 'ALL',
                'bill_date' => '20140603',
            ],
        ]);
        static::responseAssertion($res);
    }

    /**
     * @param ResponseInterface $response
     * @param boolean $testFinished
     */
    private static function responseAssertion(ResponseInterface $response, bool $testFinished = false): void
    {
        $stream = $response->getBody();
        $stream->tell() && $stream->rewind();
        $firstFiveBytes = $stream->read(5);
        $stream->rewind();
        if ('<xml>' === $firstFiveBytes) {
            $txt = (string) $stream;
            $array = Transformer::toArray($txt);
            static::assertArrayHasKey('return_msg', $array);
            static::assertArrayHasKey('return_code', $array);
            static::assertArrayHasKey('error_code', $array);
        } else {
            $line = Utils::readLine($stream, self::CSV_DATA_LINE_MAXIMUM_BYTES);
            $headerCommaCount = substr_count($line, ',');
            $isRecord = false;
            do {
                $line = Utils::readLine($stream, self::CSV_DATA_LINE_MAXIMUM_BYTES);
                $isRecord = $line[0] === self::CSV_DATA_FIRST_BYTE;
                if ($isRecord) {
                    static::assertEquals($headerCommaCount, substr_count($line, self::CSV_DATA_SEPERATOR));
                }
            } while(!$stream->eof() && $isRecord);
            $summaryCommaCount = substr_count($line, ',');
            $line = Utils::readLine($stream, self::CSV_DATA_LINE_MAXIMUM_BYTES);
            static::assertTrue($line[0] === self::CSV_DATA_FIRST_BYTE);
            static::assertEquals($summaryCommaCount, substr_count($line, self::CSV_DATA_SEPERATOR));
            $stream->rewind();
            if ($testFinished) {
                $stream->close();
                static::assertFalse($stream->isSeekable());
            }
        }
    }

    /**
     * @dataProvider mockRequestsDataProvider
     * @param string $mchid
     * @param ResponseInterface $respondor
     */
    public function testPostAsync(string $mchid, ResponseInterface $respondor): void
    {
        $instance = Builder::factory([
            'mchid'      => $mchid,
            'serial'     => 'nop',
            'privateKey' => 'any',
            'certs'      => ['any' => null],
            'secret'     => '',
            'handler'    => $this->guzzleMockStack(),
        ]);
        $this->mock->reset();
        $this->mock->append($respondor);

        $endpoint = $instance->chain('v2/pay/downloadbill');
        $stack = clone $endpoint->getDriver()->select(ClientDecoratorInterface::XML_BASED)->getConfig('handler');
        /** @var HandlerStack $stack */
        $stack->remove('transform_response');

        // yes, start with `@` to prevent the internal `E_USER_DEPRECATED`
        @$endpoint->postAsync([
            'handler' => $stack,
            'xml' => [
                'appid'     => 'wx8888888888888888',
                'mch_id'    => $mchid,
                'bill_type' => 'ALL',
                'bill_date' => '20140603',
            ],
        ])->then(static function(ResponseInterface $response) {
            static::responseAssertion($response, true);
        })->wait();
    }
}
