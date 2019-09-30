<?php
/**
 * PrivateKeySigner
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Auth;

use WechatPay\GuzzleMiddleware\Auth\Signer;
use WechatPay\GuzzleMiddleware\Auth\SignatureResult;

/**
 * PrivateKeySigner
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class PrivateKeySigner implements Signer
{
    /**
     * Certificate Serial Number
     *
     * @var string
     */
    protected $certificateSerialNumber;

    /**
     * Merchant certificate private key
     *
     * @var string|resource
     */
    protected $privateKey;

    /**
     * Constructor
     *
     * @param string            $serialNo     Merchant Certificate Serial Number
     * @param string|resource   $privateKey   Merchant Certificate Private Key \
     * (string - PEM formatted key, or resource - key returned by openssl_get_privatekey)
     */
    public function __construct($serialNumber, $privateKey)
    {
        $this->certificateSerialNumber = $serialNumber;
        $this->privateKey = $privateKey;
    }

    /**
     * Sign Message
     *
     * @param string $message Message to sign
     *
     * @return string
     */
    public function sign($message)
    {
        if (!in_array('sha256WithRSAEncryption', \openssl_get_md_methods(true))) {
            throw new \RuntimeException("当前PHP环境不支持SHA256withRSA");
        }

        if (!\openssl_sign($message, $sign, $this->privateKey, 'sha256WithRSAEncryption')) {
            throw new \UnexpectedValueException("签名验证过程发生了错误");
        }
        return new SignatureResult(\base64_encode($sign), $this->certificateSerialNumber);
    }
}
