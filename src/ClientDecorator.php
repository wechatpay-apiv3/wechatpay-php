<?php

namespace WeChatPay;

use function array_replace_recursive;
use function array_push;
use function extension_loaded;
use function function_exists;
use function sprintf;
use function php_uname;
use function implode;
use function preg_match;
use function strncasecmp;
use function strcasecmp;
use function substr;

use const PHP_OS;
use const PHP_VERSION;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorate the `GuzzleHttp\Client` instance
 */
final class ClientDecorator implements ClientDecoratorInterface
{
    use ClientXmlTrait;
    use ClientJsonTrait;

    /**
     * Deep merge the input with the defaults
     *
     * @param array<string,string|int|bool|array|mixed> $config - The configuration.
     *
     * @return array<string, string|mixed> - With the built-in configuration.
     */
    protected static function withDefaults(array $config = []): array
    {
        return array_replace_recursive(static::$defaults, ['headers' => static::userAgent()], $config);
    }

    /**
     * Prepare the `User-Agent` value key/value pair
     *
     * @return array<string, string>
     */
    protected static function userAgent(): array
    {
        $value = ['wechatpay-php/' . static::VERSION];

        array_push($value, 'GuzzleHttp/' . ClientInterface::MAJOR_VERSION);

        extension_loaded('curl') && function_exists('curl_version') && array_push($value, 'curl/' . curl_version()['version']);

        array_push($value, sprintf('(%s/%s) PHP/%s', PHP_OS, php_uname('r'), PHP_VERSION));

        return ['User-Agent' => implode(' ', $value)];
    }

    /**
     * Taken body string
     *
     * @param MessageInterface $message - The message
     */
    protected static function body(MessageInterface $message): string
    {
        $body = '';
        $bodyStream = $message->getBody();
        if ($bodyStream->isSeekable()) {
            $body = (string)$bodyStream;
            $bodyStream->rewind();
        }

        return $body;
    }

    /**
     * Decorate the `GuzzleHttp\Client` factory
     *
     * Acceptable \$config parameters stucture
     *   - mchid: string - The merchant ID
     *   - serial: string - The serial number of the merchant certificate
     *   - privateKey: \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string - The merchant private key.
     *   - certs: array<string, \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string> - The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     *   - secret?: string - The secret key string (optional)
     *   - merchant?: array{key?: string, cert?: string} - The merchant private key and certificate array. (optional)
     *   - merchant<?key, string> - The merchant private key(file path string). (optional)
     *   - merchant<?cert, string> - The merchant certificate(file path string). (optional)
     *
     * @param array<string,string|int|bool|array|mixed> $config - `\GuzzleHttp\Client`, `APIv3` and `APIv2` configuration settings.
     */
    public function __construct(array $config = [])
    {
        $this->{ClientDecoratorInterface::XML_BASED} = static::xmlBased($config);
        $this->{ClientDecoratorInterface::JSON_BASED} = static::jsonBased($config);
    }

    /**
     * Identify the `protocol` and `uri`
     *
     * @param string $uri - The uri string.
     *
     * @return array<string> - the first element is the API version ask `protocol`, the second is the real `uri`
     */
    private static function prepare(string $uri): array
    {
        return $uri && 0 === strncasecmp(ClientDecoratorInterface::XML_BASED . '/', $uri, 3)
            ? [ClientDecoratorInterface::XML_BASED, substr($uri, 3)]
            : [ClientDecoratorInterface::JSON_BASED, $uri];
    }

    /**
     * @inheritDoc
     */
    public function select(?string $protocol = null): ClientInterface
    {
        return $protocol && 0 === strcasecmp(ClientDecoratorInterface::XML_BASED, $protocol)
            ? $this->{ClientDecoratorInterface::XML_BASED}
            : $this->{ClientDecoratorInterface::JSON_BASED};
    }

    /**
     * Expands a URI template
     *
     * @param string $template  URI template
     * @param array<string|int,string|int> $variables Template variables
     *
     * @return string
    */
    protected static function withUriTemplate(string $template, array $variables = []): string
    {
        if (0 === preg_match('#\{(?:[^/]+)\}#', $template)) {
            return $template;
        }

        static $uriTemplate;
        if (!$uriTemplate) {
            $uriTemplate = new UriTemplate();
        }

        return $uriTemplate->expand($template, $variables);
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        list($protocol, $pathname) = static::prepare($uri);

        return $this->select($protocol)->request($method, static::withUriTemplate($pathname, $options), $options);
    }

    /**
     * @inheritDoc
     */
    public function requestAsync(string $method, string $uri, array $options = []): PromiseInterface
    {
        list($protocol, $pathname) = static::prepare($uri);

        return $this->select($protocol)->requestAsync($method, static::withUriTemplate($pathname, $options), $options);
    }
}
