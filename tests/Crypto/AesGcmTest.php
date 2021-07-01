<?php declare(strict_types=1);

namespace WeChatPay\Tests\Crypto;

use function class_implements;

use WeChatPay\Formatter;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\AesInterface;
use PHPUnit\Framework\TestCase;

class AesGcmTest extends TestCase
{
    const BASE64_EXPRESSION = '#^[a-zA-Z0-9][a-zA-Z0-9\+/]*={0,2}$#';

    public function testImplementsAesInterface(): void
    {
        $map = class_implements(AesGcm::class);

        self::assertIsArray($map);
        self::assertNotEmpty($map);
        self::assertArrayHasKey(AesInterface::class, (array)$map);
        self::assertContainsEquals(AesInterface::class, (array)$map);
    }

    public function testClassConstants(): void
    {
        self::assertIsString(AesGcm::ALGO_AES_256_GCM);
        self::assertIsInt(AesGcm::KEY_LENGTH_BYTE);
        self::assertIsInt(AesGcm::BLOCK_SIZE);
    }

    /**
     * @return array<string,array{string,string,string,string}>
     */
    public function dataProvider(): array
    {
        return [
            'random key and iv' => [
                'hello wechatpay 你好 微信支付',
                Formatter::nonce(AesGcm::KEY_LENGTH_BYTE),
                Formatter::nonce(AesGcm::BLOCK_SIZE),
                ''
            ],
            'random key, iv and aad' => [
                'hello wechatpay 你好 微信支付',
                Formatter::nonce(AesGcm::KEY_LENGTH_BYTE),
                Formatter::nonce(AesGcm::BLOCK_SIZE),
                Formatter::nonce(AesGcm::BLOCK_SIZE)
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param string $plaintext
     * @param string $key
     * @param string $iv
     * @param string $aad
     */
    public function testEncrypt(string $plaintext, $key, $iv, $aad): void
    {
        $ciphertext = AesGcm::encrypt($plaintext, $key, $iv, $aad);
        self::assertIsString($ciphertext);
        self::assertNotEquals($plaintext, $ciphertext);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
        }
    }

    /**
     * @dataProvider dataProvider
     * @param string $plaintext
     * @param string $key
     * @param string $iv
     * @param string $aad
     */
    public function testDecrypt(string $plaintext, $key, $iv, $aad): void
    {
        $ciphertext = AesGcm::encrypt($plaintext, $key, $iv, $aad);
        self::assertIsString($ciphertext);
        self::assertNotEquals($plaintext, $ciphertext);

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(self::BASE64_EXPRESSION, $ciphertext);
        } else {
            self::assertRegExp(self::BASE64_EXPRESSION, $ciphertext);
        }

        $mytext = AesGcm::decrypt($ciphertext, $key, $iv, $aad);
        self::assertIsString($mytext);
        self::assertEquals($plaintext, $mytext);
    }
}
