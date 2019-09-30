<?php
/**
 * Verifier
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Auth;

/**
 * Interface abstracting Verifier.
 *
 * @package  WechatPay
 * @author   WeChat Pay Team
 */
interface Verifier
{

    /**
     * Verify signature of message
     *
     * @param string $serialNumber  certificate serial number
     * @param string $message       message to verify
     * @param string $signautre     signature of message
     *
     * @return bool
     */
    public function verify($serialNumber, $message, $signature);
}
