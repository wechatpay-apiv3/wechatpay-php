<?php declare(strict_types=1);

namespace WeChatPay\Tests\Util;

use const DIRECTORY_SEPARATOR;

use function dirname;

use WeChatPay\Util\MediaUtil;
use PHPUnit\Framework\TestCase;

class MediaUtilTest extends TestCase
{
    private const ALGO_SHA256 = 'sha256';
    /**
     * @return array<string,string[]>
     */
    public function fileDataProvider(): array
    {
        return [
            'normal local file' => [
                $logo = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'logo.png',
                hash_file(self::ALGO_SHA256, $logo) ?: '',
            ],
            'file:// protocol with local file' => [
                'file://' . $logo,
                hash_file(self::ALGO_SHA256, $logo) ?: '',
            ],
            'data:// protocol with base64 string' => [//RFC2397
                'data://image/gif;base64,' . ($data = 'R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='),
                hash(self::ALGO_SHA256, base64_decode($data)) ?: '',
            ],
        ];
    }
    /**
     * @dataProvider fileDataProvider
     *
     * @param string $file
     * @param string $sha256Digest
     */
    public function testConstructor($file, $sha256Digest): void
    {
        $util = new MediaUtil($file);

        self::assertIsObject($util);
        self::assertIsString($json = $util->getMeta());
        self::assertJson($json);
        ['sha256' => $digest] = json_decode($json, true);
        self::assertEquals($sha256Digest, $digest);
    }
}
