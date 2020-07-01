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

    /**
     * Parse Serial Number from Certificate
     *
     * @param string|resource   $certifcates   Certificates (string - PEM formatted certificate, \
     * or resource - X.509 certificate resource returned by loadCertificate or openssl_x509_read)
     *
     * @return string
     */
    public static function parseCertificateSerialNo($certificate)
    {
        $info = \openssl_x509_parse($certificate);
        if (!isset($info['serialNumber']) && !isset($info['serialNumberHex'])) {
            throw new \InvalidArgumentException('证书格式错误');
        }

        $serialNo = '';
        // PHP 7.0+ provides serialNumberHex field
        if (isset($info['serialNumberHex'])) {
            $serialNo = $info['serialNumberHex'];
        } else {
            // PHP use i2s_ASN1_INTEGER in openssl to convert serial number to string,
            // i2s_ASN1_INTEGER may produce decimal or hexadecimal format,
            // depending on the version of openssl and length of data.
            if (\strtolower(\substr($info['serialNumber'], 0, 2)) == '0x') { // HEX format
                $serialNo = \substr($info['serialNumber'], 2);
            } else { // DEC format
                $value = $info['serialNumber'];
                $hexvalues = ['0','1','2','3','4','5','6','7',
                    '8','9','A','B','C','D','E','F'];
                while ($value != '0') {
                    $serialNo = $hexvalues[\bcmod($value, '16')].$serialNo;
                    $value = \bcdiv($value, '16', 0);
                }
            }
        }

        return \strtoupper($serialNo);
    }
}
