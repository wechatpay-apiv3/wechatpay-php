<?php
/**
 * SignatureResult
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Auth;

/**
 * SignatureResult
 *
 * @package  WechatPay
 * @author   WeChat Pay Team
 */
class SignatureResult
{
    /**
     * Signature
     *
     * @var string
     */
    public $sign;

    /**
     * Certificate Serial Number
     *
     * @var string
     */
    public $certificateSerialNumber;

    /**
     * Constructor
     */
    public function __construct($sign, $serialNumber)
    {
        $this->sign = $sign;
        $this->certificateSerialNumber = $serialNumber;
    }

    /**
     * Get Signature
     *
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * Get Certificate Serial Number
     *
     * @return string
     */
    public function getCertificateSerialNumber()
    {
        return $this->certificateSerialNumber;
    }
}
