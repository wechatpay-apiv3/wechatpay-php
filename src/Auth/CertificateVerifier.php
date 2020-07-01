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
use WechatPay\GuzzleMiddleware\Util\PemUtil;

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
            $serialNo = PemUtil::parseCertificateSerialNo($certificate);
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
}
