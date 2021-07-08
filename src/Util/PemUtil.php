<?php declare(strict_types=1);

namespace WeChatPay\Util;

use function openssl_get_privatekey;
use function openssl_x509_read;
use function openssl_x509_parse;
use function file_get_contents;
use function strtoupper;

use UnexpectedValueException;

/**
 * Util for read private key and certificate.
 */
class PemUtil
{
    /**
     * Read private key from file
     *
     * @param string $filepath - PEM encoded private key file path
     * @param string $passphrase The optional parameter passphrase must be used if the specified key is encrypted (protected by a passphrase).
     *
     * @return \OpenSSLAsymmetricKey|object|resource|bool - Private key resource identifier on success, or FALSE on error
     * @throws UnexpectedValueException
     */
    public static function loadPrivateKey(string $filepath, string $passphrase = '')
    {
        $content = file_get_contents($filepath);

        if (false === $content) {
            throw new UnexpectedValueException("Loading the privateKey failed, please checking your {$filepath} input.");
        }

        return openssl_get_privatekey($content, $passphrase);
    }

    /**
     * Read certificate from file
     *
     * @param string $filepath - PEM encoded X.509 certificate file path
     *
     * @return \OpenSSLCertificate|object|resource|bool - X.509 certificate resource identifier on success or FALSE on failure
     * @throws UnexpectedValueException
     */
    public static function loadCertificate(string $filepath)
    {
        $content = file_get_contents($filepath);
        if (false === $content) {
            throw new UnexpectedValueException("Loading the certificate failed, please checking your {$filepath} input.");
        }

        return openssl_x509_read($content);
    }

    /**
     * Read private key from string
     *
     * @param \OpenSSLAsymmetricKey|object|resource|string|mixed $content - PEM encoded private key string content
     * @param string $passphrase The optional parameter passphrase must be used if the specified key is encrypted (protected by a passphrase).
     *
     * @return \OpenSSLAsymmetricKey|object|resource|bool - Private key resource identifier on success, or FALSE on error
     */
    public static function loadPrivateKeyFromString($content, string $passphrase = '')
    {
        return openssl_get_privatekey($content, $passphrase);
    }

    /**
     * Read certificate from string
     *
     * @param \OpenSSLCertificate|object|resource|string|mixed $content - PEM encoded X.509 certificate string content
     *
     * @return \OpenSSLCertificate|object|resource|bool - X.509 certificate resource identifier on success or FALSE on failure
     */
    public static function loadCertificateFromString($content)
    {
        return openssl_x509_read($content);
    }

    /**
     * Parse Serial Number from Certificate
     *
     * @param \OpenSSLCertificate|object|resource|string|mixed $certificate Certificates string or resource
     *
     * @return string - The serial number
     * @throws UnexpectedValueException
     */
    public static function parseCertificateSerialNo($certificate): string
    {
        $info = openssl_x509_parse($certificate);
        if (false === $info || !isset($info['serialNumberHex'])) {
            throw new UnexpectedValueException('Read the $certificate failed, please check it whether or nor correct');
        }

        return strtoupper($info['serialNumberHex'] ?? '');
    }
}
