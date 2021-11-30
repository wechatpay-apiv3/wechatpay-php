<?php declare(strict_types=1);

namespace WeChatPay;

use function array_replace_recursive;
use function call_user_func;
use function sprintf;
use function php_uname;
use function implode;
use function strncasecmp;
use function strcasecmp;
use function substr;
use function constant;
use function defined;

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
     * @var ClientInterface - The APIv2's `\GuzzleHttp\Client`
     */
    protected $v2;

    /**
     * @var ClientInterface - The APIv3's `\GuzzleHttp\Client`
     */
    protected $v3;

    /**
     * Deep merge the input with the defaults
     *
     * @param array<string,string|int|bool|array|mixed> $config - The configuration.
     *
     * @return array<string, string|mixed> - With the built-in configuration.
     */
    protected static function withDefaults(array ...$config): array
    {
        return array_replace_recursive(static::$defaults, ['headers' => static::userAgent()], ...$config);
    }

    /**
     * Prepare the `User-Agent` value key/value pair
     *
     * @return array<string, string>
     */
    protected static function userAgent(): array
    {
        return ['User-Agent' => implode(' ', [
            sprintf('wechatpay-php/%s', static::VERSION),
            sprintf('GuzzleHttp/%s', constant(ClientInterface::class . (defined(ClientInterface::class . '::VERSION') ? '::VERSION' : '::MAJOR_VERSION'))),
            sprintf('curl/%s', ((array)call_user_func('\curl_version'))['version'] ?? 'unknown'),
            sprintf('(%s/%s)', PHP_OS, php_uname('r')),
            sprintf('PHP/%s', PHP_VERSION),
        ])];
    }

    /**
     * Taken body string
     *
     * @param MessageInterface $message - The message
     */
    protected static function body(MessageInterface $message): string
    {
        $stream = $message->getBody();
        $content = (string) $stream;

        $stream->tell() && $stream->rewind();

        return $content;
    }

    /**
     * Decorate the `GuzzleHttp\Client` factory
     *
     * Acceptable \$config parameters stucture
     *   - mchid: string - The merchant ID
     *   - serial: string - The serial number of the merchant certificate
     *   - privateKey: \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string - The merchant private key.
     *   - certs: array<string, \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string> - The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     *   - secret?: string - The secret key string (optional)
     *   - merchant?: array{key?: string, cert?: string} - The merchant private key and certificate array. (optional)
     *   - merchant<?key, string|string[]> - The merchant private key(file path string). (optional)
     *   - merchant<?cert, string|string[]> - The merchant certificate(file path string). (optional)
     *
     * @param array<string,string|int|bool|array|mixed> $config - `\GuzzleHttp\Client`, `APIv3` and `APIv2` configuration settings.
     */
    public function __construct(array $config = [])
    {
        $this->{static::XML_BASED} = static::xmlBased($config);
        $this->{static::JSON_BASED} = static::jsonBased($config);
    }

    /**
     * Identify the `protocol` and `uri`
     *
     * @param string $uri - The uri string.
     *
     * @return string[] - the first element is the API version aka `protocol`, the second is the real `uri`
     */
    private static function prepare(string $uri): array
    {
        return $uri && 0 === strncasecmp(static::XML_BASED . '/', $uri, 3)
            ? [static::XML_BASED, substr($uri, 3)]
            : [static::JSON_BASED, $uri];
    }

    /**
     * @inheritDoc
     */
    public function select(?string $protocol = null): ClientInterface
    {
        return $protocol && 0 === strcasecmp(static::XML_BASED, $protocol)
            ? $this->{static::XML_BASED}
            : $this->{static::JSON_BASED};
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        [$protocol, $pathname] = self::prepare(UriTemplate::expand($uri, $options));

        return $this->select($protocol)->request($method, $pathname, $options);
    }

    /**
     * @inheritDoc
     */
    public function requestAsync(string $method, string $uri, array $options = []): PromiseInterface
    {
        [$protocol, $pathname] = self::prepare(UriTemplate::expand($uri, $options));

        return $this->select($protocol)->requestAsync($method, $pathname, $options);
    }
}
