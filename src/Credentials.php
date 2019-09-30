<?php
/**
 * Credentials
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;

/**
 * Interface abstracting Credentials.
 *
 * @package  WechatPay
 * @author   WeChat Pay Team
 */
interface Credentials
{
    /**
     * Get schema of credentials
     *
     * @return string
     */
    public function getSchema();

    /**
     * Get token of credentials
     *
     * @param RequestInterface $request Api request
     *
     * @return string
     */
    public function getToken(RequestInterface $request);
}
