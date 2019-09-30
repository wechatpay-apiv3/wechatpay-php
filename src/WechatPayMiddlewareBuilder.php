<?php
/**
 * WechatPayMiddlewareBuilder
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware;

use WechatPay\GuzzleMiddleware\Credentials;
use WechatPay\GuzzleMiddleware\Validator;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;
use WechatPay\GuzzleMiddleware\Auth\PrivateKeySigner;
use WechatPay\GuzzleMiddleware\Auth\CertificateVerifier;
use WechatPay\GuzzleMiddleware\Auth\WechatPay2Credentials;
use WechatPay\GuzzleMiddleware\Auth\WechatPay2Validator;

/**
 * WechatPayMiddlewareBuilder
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class WechatPayMiddlewareBuilder
{
    /**
     * Merchant credentials
     *
     * @var Credentials
     */
    protected $credentials;

    /**
     * Response Validator
     *
     *  @var Validator
     */
    protected $validator;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set Merchant Infomation
     *
     * @param string            $merchantId   Merchant Id
     * @param string            $serialNo     Merchant Certificate Serial Number
     * @param string|resource   $privateKey   Merchant Certificate Private Key (string - PEM formatted key, or resource - key returned by openssl_get_privatekey)
     *
     * @return $this
     */
    public function withMerchant($merchantId, $serialNo, $privateKey)
    {
        $this->credentials = new WechatPay2Credentials($merchantId, 
            new PrivateKeySigner($serialNo, $privateKey));
        return $this;
    }

    /**
     * Set Merchant Credentials
     *
     * @param Credentials $credentials Merchant Certificate Credentials
     *
     * @return $this
     */
    public function withCredentials(Credentials $credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * Set WechatPay Certificates Infomation
     *
     * @param array of string|resource   $certifcates   WechatPay Certificates (string - PEM formatted \
     * certificate, or resource - X.509 certificate resource returned by openssl_x509_read)
     *
     * @return $this
     */
    public function withWechatPay(array $certificates)
    {
        $this->validator = new WechatPay2Validator(new CertificateVerifier($certificates));
        return $this;
    }

    /**
     * Set WechatPay Validator
     *
     * @param Validator $Validator  WechatPay Validator
     *
     * @return $this
     */
    public function withValidator(Validator $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Build WechatPayMiddleware
     *
     * @return WechatPayMiddleware
     */
    public function build()
    {
        if (!isset($this->credentials)) {
            throw new \InvalidArgumentException('商户认证信息(credentials)未设置');
        }
        if (!isset($this->validator)) {
            throw new \InvalidArgumentException('微信支付平台签名验证(validator)未设置');
        }

        return new WechatPayMiddleware($this->credentials, $this->validator);
    }
}
