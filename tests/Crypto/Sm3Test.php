<?php declare(strict_types=1);

namespace WeChatPay\Tests\Crypto;

use function array_combine;
use function array_map;
use function file_get_contents;
use function file_put_contents;
use function preg_match_all;
use function sprintf;
use function str_repeat;
use function strlen;

use WeChatPay\Crypto\Sm3;
use PHPUnit\Framework\TestCase;

class Sm3Test extends TestCase
{
    private const FIXTURES = __DIR__ . '/../fixtures/mock.%s.txt';
    private const OPENSSL_OUTPUTS_PATTERN = '|SM3\((?<file>[^\)]+)\)=\s(?<digest>[a-f0-9]{64})|';

    /**
     * @return array<string,array{string,string}>
     */
    public function digestDataProvider(): array
    {
        return [
            'GM/T 0004-2012 A.1 示例1' => ['abc',                  '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0'],
            'GM/T 0004-2012 A.1 示例2' => [str_repeat('abcd', 16), 'debe9ff92275b8a138604889c18e5a4d6fdb70e5387e5765293dcba39c0c5732'],
        ];
    }

    /**
     * @dataProvider digestDataProvider
     *
     * @param string $thing
     * @param string $value
     */
    public function testDigest(string $thing, string $value): void
    {
        $calc = Sm3::digest($thing);
        static::assertIsString($calc);
        static::assertNotEmpty($calc);
        static::assertEquals(strlen($value), strlen($calc));
        static::assertEquals($value, $calc);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public function fileDataProvider(): array
    {
        $load = (string) file_get_contents(sprintf(self::FIXTURES, 'sm3'));
        preg_match_all(self::OPENSSL_OUTPUTS_PATTERN, $load, $matches);

        $samples = [];
        foreach ($this->digestDataProvider() as $id => [$content, $digest]) {
            file_put_contents($file = sprintf(self::FIXTURES, $content), $content);
            $samples[$id] = [$file, $digest];
        }

        return $samples + (array_combine(
            $matches['file'],
            array_map(static function(string $file, string $digest): array { return [$file, $digest]; }, $matches['file'], $matches['digest'])
        ) ?: []);
    }

    /**
     * @dataProvider fileDataProvider
     *
     * @param string $path
     * @param string $value
     */
    public function testFile(string $path, string $value): void
    {
        $calc = Sm3::file($path);
        static::assertIsString($calc);
        static::assertNotEmpty($calc);
        static::assertEquals(strlen($value), strlen($calc));
        static::assertEquals($value, $calc);
    }
}
