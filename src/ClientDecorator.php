<?php

namespace WechatPay\GuzzleMiddleware;

use function array_replace_recursive;
use function array_push;
use function extension_loaded;
use function function_exists;
use function sprintf;
use function php_uname;
use function implode;
use function preg_match;
use function strncasecmp;
use function preg_replace;
use function strtoupper;

use const PHP_OS;
use const PHP_VERSION;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorate the `\GuzzleHttp\Client` instance
 */
final class ClientDecorator implements ClientDecoratorInterface
{
    use ClientXmlTrait;
    use ClientJsonTrait;

    /**
     * Deep merge the input with the defaults
     *
     * @param array $config - The configuration.
     *
     * @return array - With the built-in configuration.
     */
    protected static function withDefaults(array $config = []): array
    {
        return array_replace_recursive(static::$defaults, ['headers' => static::userAgent()], $config);
    }

    /**
     * Prepare the `User-Agent` value key/value pair
     */
    protected static function userAgent(): array
    {
        $value = ['WechatPay-Guzzle/' . static::VERSION];

        array_push($value, 'GuzzleHttp/' . Client::MAJOR_VERSION);

        extension_loaded('curl') && function_exists('curl_version') && array_push($value, 'curl/' . curl_version()['version']);

        array_push($value, sprintf('(%s/%s) PHP/%s', PHP_OS, php_uname('r'), PHP_VERSION));

        return ['User-Agent' => implode(' ', $value)];
    }

    /**
     * Taken body string
     *
     * @param MessageInterface $message - The message
     *
     * @return string
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
     * @param array $config - configuration
     * @param string|int $config[mchid] - The merchant ID
     * @param string $config[serial] - The serial number of the merchant certificate
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|resource|array|string $config[privateKey] - The merchant private key.
     * @param array $config[certs] - The WeChatPay platform serial and certificate(s), `[serial => certificate]` pair
     *
     * @return ClientDecorator - The `ClientDecorator` instance
     */
    public function __construct(array $config = [])
    {
        $this->v2 = static::xmlBased($config);
        $this->v3 = static::jsonBased($config);
    }

    /**
     * Identify the `Client` and `uri`
     *
     * @param string $pathname - The pathname string.
     *
     * @return array - the first element is the client instance, the second is the real uri
     */
    private static function prepare(string $pathname): array
    {
        return [0 === strncasecmp('v2/', $pathname, 3) ? 'v2' : 'v3', preg_replace('#^v2/#i', '', $pathname)];
    }

    /**
     * Expands a URI template
     *
     * @param string $template  URI template
     * @param array  $variables Template variables
     *
     * @return string
    */
    protected function withUriTemplate(string $template, array $variables = []): string
    {
        if (0 === preg_match('#{(?:[^/]+)}#', $template)) {
            return $template;
        }

        if (extension_loaded('uri_template')) {
            // @codeCoverageIgnoreStart
            return \uri_template($template, $variables);
            // @codeCoverageIgnoreEnd
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
    public function request(string $method, string $pathname, array $options = []): ResponseInterface
    {
        $did = static::prepare($pathname);

        return $this->{$did[0]}->request(strtoupper($method), $this->withUriTemplate($did[1], $options), $options);
    }

    /**
     * @inheritDoc
     */
    public function requestAsync(string $method, string $pathname, array $options = []): PromiseInterface
    {
        $did = static::prepare($pathname);

        return $this->{$did[0]}->requestAsync(strtoupper($method), $this->withUriTemplate($did[1], $options), $options);
    }
}
