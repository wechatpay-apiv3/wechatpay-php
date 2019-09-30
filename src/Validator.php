<?php
/**
 * Validator
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface abstracting Validator.
 *
 * @package  WechatPay
 * @author   WeChat Pay Team
 */
interface Validator
{
    /**
     * Validate Response
     *
     * @param ResponseInterface $response Api response to validate
     *
     * @return bool
     */
    public function validate(ResponseInterface $response);
}
