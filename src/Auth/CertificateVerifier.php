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
        if (!isset($info['serialNumber'])) {
            throw new \InvalidArgumentException('证书格式错误');
        }

        $serialNo = $info['serialNumber'];
        if (\is_int($serialNo)) {
            return \strtoupper(\dechex($serialNo));
        }
        $hexvalues = ['0','1','2','3','4','5','6','7',
               '8','9','A','B','C','D','E','F'];
        $hexval = '';
        while ($serialNo != '0') {
            $hexval = $hexvalues[\bcmod($serialNo, '16')].$hexval;
            $serialNo = \bcdiv($serialNo, '16', 0);
        }
        return $hexval;
    }
}
