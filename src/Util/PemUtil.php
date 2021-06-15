<?php

namespace WeChatPay\Util;

use function openssl_get_privatekey;
use function openssl_x509_read;
use function openssl_x509_parse;
use function file_get_contents;
use function strtoupper;

use InvalidArgumentException;

/**
 * Util for read private key and certificate.
 */
class PemUtil
{
    /**
     * Read private key from file
     *
     * @param string $filepath - PEM encoded private key file path
     *
     * @return OpenSSLAsymmetricKey|resource|bool - Private key resource identifier on success, or FALSE on error
     */
    public static function loadPrivateKey(string $filepath, ?string $passphrase = null)
    {
        return openssl_get_privatekey(file_get_contents($filepath), $passphrase);
    }

    /**
     * Read certificate from file
     *
     * @param string $filepath - PEM encoded X.509 certificate file path
     *
     * @return OpenSSLCertificate|resource|bool - X.509 certificate resource identifier on success or FALSE on failure
     */
    public static function loadCertificate(string $filepath)
    {
        return openssl_x509_read(file_get_contents($filepath));
    }

    /**
     * Read private key from string
     *
     * @param OpenSSLAsymmetricKey|resource|array|string $content - PEM encoded private key string content
     * @param ?string $passphrase The optional parameter passphrase must be used if the specified key is encrypted (protected by a passphrase).
     *
     * @return OpenSSLAsymmetricKey|resource|bool - Private key resource identifier on success, or FALSE on error
     */
    public static function loadPrivateKeyFromString($content, ?string $passphrase = null)
    {
        return openssl_get_privatekey($content, $passphrase);
    }

    /**
     * Read certificate from string
     *
     * @param OpenSSLCertificate|resource|string $content - PEM encoded X.509 certificate string content
     *
     * @return OpenSSLCertificate|resource|bool - X.509 certificate resource identifier on success or FALSE on failure
     */
    public static function loadCertificateFromString($content)
    {
        return openssl_x509_read($content);
    }

    /**
     * Parse Serial Number from Certificate
     *
     * @param OpenSSLCertificate|resource|string $certifcates Certificates string or resource
     *
     * @return string - The serial number
     * @throws InvalidArgumentException
     */
    public static function parseCertificateSerialNo($certificate): string
    {
        $info = openssl_x509_parse($certificate);
        if (false === $info || !isset($info['serialNumberHex'])) {
            throw new InvalidArgumentException('证书格式错误');
        }

        return strtoupper($info['serialNumberHex'] ?? '');
    }
}
