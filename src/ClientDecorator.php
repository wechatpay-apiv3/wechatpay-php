<?php
namespace WechatPay\GuzzleMiddleware;

use GuzzleHttp\Client;

/**
 * Decorate the `\GuzzleHttp\Client` instance
 */
final class ClientDecorator
{
    use ClientJsonTrait;

    /**
     * @var string - The WechatPayMiddleware version
     */
    const VERSION = '1.0.0';

    /**
     * Deep merge the input with the defaults
     *
     * @param array $config - The configuration.
     *
     * @return array - With the built-in configuration.
     */
    protected static function withDefaults(array $config = []): array
    {
        return \array_replace_recursive(static::$defaults, ['headers' => static::userAgent()], $config);
    }

    /**
     * Prepare the `User-Agent` value key/value pair
     */
    protected static function userAgent(): array
    {
        $value = ['WechatPay-Guzzle/' . static::VERSION];

        \array_push($value, 'GuzzleHttp/' . Client::MAJOR_VERSION);

        if (\extension_loaded('curl') && \function_exists('curl_version')) {
            \array_push($value, 'curl/' . \curl_version()['version']);
        }

        \array_push($value, \sprintf('(%s/%s) PHP/%s', \PHP_OS, \php_uname('r'), \PHP_VERSION));

        return ['User-Agent' => \implode(' ', $value)];
    }

    /**
     * Decorate the `\GuzzleHttp\Client` factory
     *
     * @param array $config - configuration
     * @param string|int $config[mchid] - The merchant ID
     * @param string $config[serial] - The serial number of the merchant certificate
     * @param resource|array|string $config[privateKey] - The merchant private key.
     * @param array $config[certs] - The WeChatPay platform serial and certificate(s), `[serial => certificate]` pair
     *
     * @return ClientDecorator - The `ClientDecorator` instance
     */
    public function __construct(array $config = [])
    {
        $this->v3 = static::jsonBased($config);
    }

    /**
     * Request the remote `$pathname` by a HTTP `$method` verb
     *
     * @param string $pathname - The pathname string.
     * @param string $method - The method string.
     * @param array $options - The options.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface - The `\GuzzleHttp\Promise` instance
     */
    public function request(string $method, string $pathname, array $options = [])
    {
        return $this->v3->requestAsync(strtoupper($method), $pathname, $options);
    }
}
