<?php declare(strict_types=1);

namespace WeChatPay\Tests\Util;

use const DIRECTORY_SEPARATOR;

use function dirname;
use function hash;
use function hash_file;
use function base64_encode;
use function base64_decode;
use function json_decode;
use function json_encode;

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
            'data://text/csv;base64 string' => [//RFC2397
                'active_user_batch_tasks_001.csv',
                new LazyOpenStream(
                    'data://text/csv;base64,' . base64_encode($data = implode('\n', [
                        'LQT_Wechatpay_Platform_Certificate_Encrypted_Line_One',
                        'LQT_Wechatpay_Platform_Certificate_Encrypted_Line_Two',
                    ])),
                    self::FOPEN_MODE_BINARYREAD
                ),
                'active_user_batch_tasks_001.csv',
                hash(self::ALGO_SHA256, $data) ?: '',
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

        ['filename' => $filename, 'sha256' => $digest] = (array)json_decode($json, true);
        self::assertEquals($expectedFilename, $filename);
        self::assertEquals($expectedSha256Digest, $digest);

        self::assertInstanceOf(StreamInterface::class, $util->getStream());
        self::assertInstanceOf(\GuzzleHttp\Psr7\FnStream::class, $util->getStream());
        self::assertEquals($json, (string)$util->getStream());
        self::assertNull($util->getStream()->getSize());

        self::assertIsString($util->getContentType());
        self::assertStringStartsWith('multipart/form-data; boundary=', $util->getContentType());
    }

    /**
     * @dataProvider fileDataProvider
     *
     * @param string $file
     * @param ?StreamInterface $stream
     * @param string $expectedFilename
     * @param string $expectedSha256Digest
     */
    public function testSetMeta($file, $stream = null, string $expectedFilename = '', string $expectedSha256Digest = ''): void
    {
        $media = new MediaUtil($file, $stream);
        $json = $media->getMeta();
        self::assertJson($json);

        /** @var array{'filename':string,'sha256':string} $array */
        $array = json_decode($json, true);
        self::assertIsArray($array);
        self::assertArrayHasKey('filename', $array);
        self::assertArrayHasKey('sha256', $array);
        self::assertArrayNotHasKey('bank_type', $array);

        ['filename' => $filename, 'sha256' => $digest] = $array;
        self::assertEquals($expectedFilename, $filename);
        self::assertEquals($expectedSha256Digest, $digest);
        self::assertEquals($json, (string)$media->getStream());

        $meta = json_encode(['filename' => $filename, 'sha256' => $digest, 'bank_type' => 'LQT']) ?: null;
        self::assertIsInt($media->setMeta($meta));

        $json = $media->getMeta();
        self::assertJson($json);
        self::assertEquals($meta, $json);
        self::assertEquals($meta, (string)$media->getStream());
        self::assertEquals($json, (string)$media->getStream());

        /** @var array{'filename':string,'sha256':string,'bank_type':string} $array */
        $array = json_decode((string)$media->getStream(), true);
        self::assertIsArray($array);
        self::assertArrayHasKey('filename', $array);
        self::assertArrayHasKey('sha256', $array);
        self::assertArrayHasKey('bank_type', $array);
    }
}
