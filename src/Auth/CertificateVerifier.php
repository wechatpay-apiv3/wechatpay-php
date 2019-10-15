<?php
/**
 * CertificateVerifier
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Auth;

use WechatPay\GuzzleMiddleware\Auth\Verifier;

/**
 * CertificateVerifier
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class CertificateVerifier implements Verifier
{
    /**
     * WechatPay Certificates Public Keys
     *
     * @var array (serialNo => publicKey)
     */
    protected $publicKeys = [];

    /**
     * Constructor
     *
     * @param array of string|resource   $certifcates   WechatPay Certificates (string - PEM formatted \
     * certificate, or resource - X.509 certificate resource returned by openssl_x509_read)
     */
    public function __construct(array $certificates)
    {
        foreach ($certificates as $certificate) {
            $serialNo = $this->parseSerialNo($certificate);
            $this->publicKeys[$serialNo] = \openssl_get_publickey($certificate);
        }
    }

    /**
     * Verify signature of message
     *
     * @param string $serialNumber  certificate serial number
     * @param string $message   message to verify
     * @param string $signautre signature of message
     *
     * @return bool
     */
    public function verify($serialNumber, $message, $signature)
    {
        $serialNumber = \strtoupper(\ltrim($serialNumber, '0')); // trim leading 0 and uppercase
        if (!isset($this->publicKeys[$serialNumber])) {
            return false;
        }
        if (!in_array('sha256WithRSAEncryption', \openssl_get_md_methods(true))) {
            throw new \RuntimeException("当前PHP环境不支持SHA256withRSA");
        }
        $signature = \base64_decode($signature);
        return \openssl_verify($message, $signature, $this->publicKeys[$serialNumber], 
            'sha256WithRSAEncryption');
    }

    /**
     * Parse Serial Number from Certificate
     *
     * @param string|resource   $certifcates   WechatPay Certificates (string - PEM formatted \
     * certificate, or resource - X.509 certificate resource returned by openssl_x509_read)
     *
     * @return string
     */
    protected function parseSerialNo($certificate)
    {
        $info = \openssl_x509_parse($certificate);
        if (!isset($info['serialNumber']) && !isset($info['serialNumberHex'])) {
            throw new \InvalidArgumentException('证书格式错误');
        }

        $serialNo = '';
        // PHP 7.0+ provides serialNumberHex field
        if (isset($info['serialNumberHex'])) {
            $serialNo = $info['serialNumberHex'];
        } else {
            // PHP use i2s_ASN1_INTEGER in openssl to convert serial number to string,
            // i2s_ASN1_INTEGER may produce decimal or hexadecimal format,
            // depending on the version of openssl and length of data.
            if (\strtolower(\substr($info['serialNumber'], 0, 2)) == '0x') { // HEX format
                $serialNo = \substr($info['serialNumber'], 2);
            } else { // DEC format
                $value = $info['serialNumber'];
                $hexvalues = ['0','1','2','3','4','5','6','7',
                    '8','9','A','B','C','D','E','F'];
                while ($value != '0') {
                    $serialNo = $hexvalues[\bcmod($value, '16')].$serialNo;
                    $value = \bcdiv($value, '16', 0);
                }
            }
        }

        return \strtoupper($serialNo);
    }
}
