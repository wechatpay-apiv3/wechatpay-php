<?php
/**
 * AuthUtil
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChatPay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware\Util;

/**
 * Util for read private key and certificate.
 *
 * @package  WechatPay
 * @author   WeChatPay Team
 */
class PemUtil
{
    /**
     * Read private key from file
     *
     * @param string    $filepath     PEM encoded private key file path
     *
     * @return resource|bool     Private key resource identifier on success, or FALSE on error
     */
    public static function loadPrivateKey($filepath)
    {
        return \openssl_get_privatekey(\file_get_contents($filepath));
    }

    /**
     * Read certificate from file
     *
     * @param string    $filepath     PEM encoded X.509 certificate file path
     *
     * @return resource|bool  X.509 certificate resource identifier on success or FALSE on failure
     */
    public static function loadCertificate($filepath)
    {
        return \openssl_x509_read(\file_get_contents($filepath));
    }

    /**
     * Read private key from string
     *
     * @param string    $content     PEM encoded private key string content
     *
     * @return resource|bool     Private key resource identifier on success, or FALSE on error
     */
    public static function loadPrivateKeyFromString($content)
    {
        return \openssl_get_privatekey($content);
    }

    /**
     * Read certificate from string
     *
     * @param string    $content     PEM encoded X.509 certificate string content
     *
     * @return resource|bool  X.509 certificate resource identifier on success or FALSE on failure
     */
    public static function loadCertificateFromString($content)
    {
        return \openssl_x509_read($content);
    }
}
