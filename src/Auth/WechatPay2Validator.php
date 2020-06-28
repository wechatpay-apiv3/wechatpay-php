<?php
/**
 * WechatPay2Validator
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Auth;

use Psr\Http\Message\ResponseInterface;
use WechatPay\GuzzleMiddleware\Validator;
use WechatPay\GuzzleMiddleware\Auth\Verifier;

/**
 * WechatPay2Validator
 *
 * @category Class
 * @package  WechatPay\GuzzleMiddleware\Auth
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class WechatPay2Validator implements Validator
{
    /**
     * sign verifier
     *
     * @var Verifier
     */
    protected $verifier;

    /**
     * Constructor
     */
    public function __construct(Verifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Validate Response
     *
     * @param ResponseInterface $response Api response to validate
     *
     * @return bool
     */
    public function validate(ResponseInterface $response)
    {
        $serialNo = $this->getHeader($response, 'Wechatpay-Serial');
        $sign = $this->getHeader($response, 'Wechatpay-Signature');
        $timestamp = $this->getHeader($response, 'Wechatpay-TimeStamp');
        $nonce = $this->getHeader($response, 'Wechatpay-Nonce');

        if (!isset($serialNo, $sign, $timestamp, $nonce)) {
            return false;
        }

        if (!$this->checkTimestamp($timestamp)) {
            // log here
            return false;
        }

        $body = $this->getBody($response);
        $message = "$timestamp\n$nonce\n$body\n";

        return $this->verifier->verify($serialNo, $message, $sign);
    }
 
    /**
     * Build message to sign
     *
     * @param string            $nonce      Nonce string
     * @param integer           $timestamp  Unix timestamp
     * @param RequestInterface  $request    Api request
     *
     * @return string
     */
    protected function getHeader(ResponseInterface $response, $name)
    {
        $values = $response->getHeader($name);
        return empty($values) ? null : $values[count($values) - 1];
    }

    /**
     * Check whether timestamp is valid
     *
     * @param integer           $timestamp  Unix timestamp
     *
     * @return bool
     */
    protected function checkTimestamp($timestamp)
    {
        // reject responses beyond 5 minutes
        return \abs((int)$timestamp - \time()) <= 300;
    }

    /**
     * Build message to sign
     *
     * @param string            $nonce      Nonce string
     * @param integer           $timestamp  Unix timestamp
     * @param RequestInterface  $request    Api request
     *
     * @return string
     */
    protected function getBody(ResponseInterface $response)
    {
        $body = '';
        $bodyStream = $response->getBody();
        // non-seekable stream need to be handled by the caller
        if ($bodyStream->isSeekable()) {
            $body = (string)$bodyStream;
            $bodyStream->rewind();
        }
        return $body;
    }
}
