<?php
/**
 * SensitiveInfoCrypto
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChatPay Team
 * @link     https://pay.weixin.qq.com
 */
namespace WechatPay\GuzzleMiddleware\Util;

/**
 * Encrypt/Decrypt the sensitive information by the certificates pair.
 *
 * <code>
 * // Encrypt usage:
 * $encryptor = new SensitiveInfoCrypto(
 *     PemUtil::loadCertificate('/downloaded/pubcert.pem')
 * );
 * $json = json_encode(['name' => $encryptor('Alice')]);
 * // That's simple!
 *
 * // Decrypt usage:
 * $decryptor = new SensitiveInfoCrypto(
 *     null,
 *     PemUtil::loadPrivateKey('/merchant/key.pem')
 * );
 * $decrypted = $decryptor->setStage('decrypt')(
 *     'base64 encoding message was given by the payment plat'
 * );
 * // That's simple too!
 *
 * // Working both Encrypt and Decrypt usages:
 * $crypto = new SensitiveInfoCrypto(
 *     PemUtil::loadCertificate('/merchant/cert.pem'),
 *     PemUtil::loadPrivateKey('/merchant/key.pem')
 * );
 * $encrypted = $crypto('Carol');
 * $decrypted = $crypto->setStage('decrypt')($encrypted);
 * // Having fun with this!
 * </code>
 *
 * @package  WechatPay
 */
class SensitiveInfoCrypto implements \JsonSerializable {

    /**
     * @var resource|null $publicCert The public certificate
     */
    private $publicCert;

    /**
     * @var resource|null $privateKey The private key
     */
    private $privateKey;

    /**
     * @var string $message The encryped or decrypted content
     */
    private $message = '';

    /**
     * @var string $stage The crypto working scenario, default is `encrypt`.
     *                    Mention here: while toggle the scenario,
     *                    the next stage is the previous one.
     */
    private $stage = 'encrypt';

    /**
     * @var array $scenarios Methods that allowed.
     */
    private static $scenarios = ['encrypt', 'decrypt'];

    /**
     * Constructor
     *
     * @param resource|null $publicCert The public certificate resource
     * @param resource|null $privateKey The private key resource
     */
    public function __construct($publicCert, $privateKey = null) {
        $this->publicCert = $publicCert;
        $this->privateKey = $privateKey;
    }

    /**
     * Encrypt the string by the public certificate
     *
     * @param string $str The content shall be encrypted
     *
     * @return SensitiveInfoCrypto
     */
    private function encrypt($str) {
        if (!is_resource($this->publicCert)) {
            throw new \InvalidArgumentException('The publicCert must be resource.');
        }
        openssl_public_encrypt($str, $encrypted,
            $this->publicCert, \OPENSSL_PKCS1_OAEP_PADDING);
        $this->message = \base64_encode($encrypted);

        return $this;
    }

    /**
     * Decrypt the string by the private key certificate
     *
     * @param string $str The content shall be decrypted
     *
     * @return SensitiveInfoCrypto
     */
    private function decrypt($str) {
        if (!is_resource($this->privateKey)) {
            throw new \InvalidArgumentException('The privateKey must be resource.');
        }
        openssl_private_decrypt(\base64_decode($str), $decrypted,
            $this->privateKey, \OPENSSL_PKCS1_OAEP_PADDING);
        $this->message = $decrypted;

        return $this;
    }

    /**
     * Specify data which should be
     *
     * @return string
     */
    public function jsonSerialize() {
        return $this->message;
    }

    /**
     * Toggle the crypto instance onto `encrypt` or `decrypt` stage
     *
     * @param string $scenario Should be `encrypt` or `decrypt`
     *
     * @throws \InvalidArgumentException if the scenario is invalid.
     *
     * @return SensitiveInfoCrypto
     */
    public function setStage($scenario) {
        if (!in_array($scenario, self::$scenarios)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot setStage `%s`, here is only allowed one of the %s.',
                $scenario,
                \implode(', ', self::$scenarios)
            ));
        }
        $this->stage = $scenario;

        return $this;
    }

    public function __invoke($str) {
        $copy = clone $this;
        return $copy->{$this->stage}($str);
    }

    public function __toString() {
        return $this->jsonSerialize();
    }
}
