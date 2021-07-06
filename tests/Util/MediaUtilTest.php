<?php declare(strict_types=1);

namespace WeChatPay\Tests\Util;

use const DIRECTORY_SEPARATOR;

use function dirname;
use function hash;
use function hash_file;
use function base64_decode;
use function json_decode;

use WeChatPay\Util\MediaUtil;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

class MediaUtilTest extends TestCase
{
    private const ALGO_SHA256 = 'sha256';
    private const FOPEN_MODE_BINARYREAD = 'rb';

    /**
     * @return array<string,array{string,StreamInterface|null,string,string}>
     */
    public function fileDataProvider(): array
    {
        return [
            'normal local file' => [
                $logo = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'logo.png',
                null,
                'logo.png',
                hash_file(self::ALGO_SHA256, $logo) ?: '',
            ],
            'file:// protocol with local file' => [
                'file://' . $logo,
                null,
                'logo.png',
                hash_file(self::ALGO_SHA256, $logo) ?: '',
            ],
            'data:// protocol with base64 string' => [//RFC2397
                'transparent.gif',
                new LazyOpenStream(
                    'data://image/gif;base64,' . ($data = 'R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='),
                    self::FOPEN_MODE_BINARYREAD
                ),
                'transparent.gif',
                hash(self::ALGO_SHA256, base64_decode($data)) ?: '',
            ],
        ];
    }
    /**
     * @dataProvider fileDataProvider
     *
     * @param string $file
     * @param ?StreamInterface $stream
     * @param string $expectedFilename
     * @param string $expectedSha256Digest
     */
    public function testConstructor($file, $stream = null, string $expectedFilename = '', string $expectedSha256Digest = ''): void
    {
        $util = new MediaUtil($file, $stream);

        self::assertIsObject($util);
        self::assertIsString($json = $util->getMeta());
        self::assertJson($json);

        ['filename' => $filename, 'sha256' => $digest] = json_decode($json, true);
        self::assertEquals($expectedFilename, $filename);
        self::assertEquals($expectedSha256Digest, $digest);

        self::assertInstanceOf(StreamInterface::class, $util->getStream());
        self::assertInstanceOf(\GuzzleHttp\Psr7\FnStream::class, $util->getStream());
        self::assertEquals($json, (string)$util->getStream());
        self::assertNull($util->getStream()->getSize());

        self::assertIsString($util->getContentType());
        self::assertStringStartsWith('multipart/form-data; boundary=', $util->getContentType());
    }
}
