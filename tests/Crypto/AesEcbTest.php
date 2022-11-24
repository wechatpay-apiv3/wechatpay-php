<?php declare(strict_types=1);

namespace WeChatPay\Tests\Crypto;

use function class_implements;
use function is_array;
use function is_null;
use function method_exists;

use WeChatPay\Formatter;
use WeChatPay\Crypto\AesEcb;
use WeChatPay\Crypto\AesInterface;
use PHPUnit\Framework\TestCase;

class AesEcbTest extends TestCase
{
    private const BASE64_EXPRESSION = '#^[a-zA-Z0-9\+/]+={0,2}$#';

    public function testImplementsAesInterface(): void
    {
        $map = class_implements(AesEcb::class);

        self::assertIsArray($map);
        self::assertNotEmpty($map);
        self::assertArrayHasKey(AesInterface::class, $map);
        if (method_exists($this, 'assertContainsEquals')) {
            $this->assertContainsEquals(AesInterface::class, $map);
        }
    }

    public function testClassConstants(): void
    {
        self::assertIsString(AesEcb::ALGO_AES_256_ECB);
        self::assertIsInt(AesEcb::KEY_LENGTH_BYTE);
    }

    /**
     * @return array<string,array{string,string,string,string|null}>
     */
    public function phrasesDataProvider(): array
    {
        return [
            'fixed plaintext and key' => [
                'hello',
                '0123456789abcdef0123456789abcdef',
                '',
                'pZwJZBLuy3mDACEQT4YTBw=='
            ],
            'random key' => [
                'hello wechatpay 你好 微信支付',
                Formatter::nonce(AesEcb::KEY_LENGTH_BYTE),
                '',
                null,
            ],
            'empty text with random key' => [
                '',
                Formatter::nonce(AesEcb::KEY_LENGTH_BYTE),
                '',
                null,
            ],
        ];
    }

    /**
     * @dataProvider phrasesDataProvider
     * @param string $plaintext
     * @param string $key
     * @param string $iv
     * @param ?string $excepted
     */
    public function testEncrypt(string $plaintext, string $key, string $iv, ?string $excepted = null): void
    {
        $ciphertext = AesEcb::encrypt($plaintext, $key, $iv);
        self::assertIsString($ciphertext);
        self::assertNotEmpty($ciphertext);

        if (!is_null($excepted)) {
            self::assertEquals($ciphertext, $excepted);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
        }
    }

    /**
     * @dataProvider phrasesDataProvider
     * @param string $plaintext
     * @param string $key
     * @param string $iv
     * @param ?string $ciphertext
     */
    public function testDecrypt(string $plaintext, string $key, string $iv, ?string $ciphertext = null): void
    {
        if (is_null($ciphertext)) {
            $ciphertext = AesEcb::encrypt($plaintext, $key, $iv);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
        }

        self::assertIsString($ciphertext);
        self::assertNotEmpty($ciphertext);
        self::assertNotEquals($plaintext, $ciphertext);

        $excepted = AesEcb::decrypt($ciphertext, $key, $iv);

        self::assertIsString($excepted);
        self::assertEquals($plaintext, $excepted);
    }
}
