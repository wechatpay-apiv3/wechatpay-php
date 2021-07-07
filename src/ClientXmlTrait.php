<?php declare(strict_types=1);

namespace WeChatPay;

use function strlen;
use function array_replace_recursive;
use function trigger_error;
use function sprintf;

use const E_USER_DEPRECATED;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Promise as P;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\MessageInterface;

/**
 * XML based Client interface for sending HTTP requests.
 */
trait ClientXmlTrait
{
    /**
     * @var Client - The APIv2's `GuzzleHttp\Client`
     */
    protected $v2;

    /**
     * @var array<string, string> - The default headers whose passed in `GuzzleHttp\Client`.
     */
    protected static $headers = [
        'Accept' => 'text/xml, text/plain, application/x-gzip',
        'Content-Type' => 'text/xml; charset=utf-8',
    ];

    abstract protected static function body(MessageInterface $message): string;

    abstract protected static function withDefaults(array $config = []): array;

    /**
     * APIv2's transformRequest, did the `datasign` and `array2xml` together
     *
     * @param ?string $mchid - The merchant ID
     * @param string $secret - The secret key string (optional)
     * @param array{cert?: ?string, key?: ?string} $merchant - The merchant private key and certificate array. (optional)
     *
     * @return callable(callable(RequestInterface, array))
     * @throws \WeChatPay\Exception\InvalidArgumentException
     */
    public static function transformRequest(?string $mchid = null, string $secret = '', ?array $merchant = null): callable
    {
        return static function (callable $handler) use ($mchid, $secret, $merchant): callable {
            trigger_error(Exception\WeChatPayException::DEP_XML_PROTOCOL_UNDER_END_OF_LIFE, E_USER_DEPRECATED);

            return static function (RequestInterface $request, array $options = []) use ($handler, $mchid, $secret, $merchant): P\PromiseInterface {
                $data = $options['xml'] ?? [];

                if ($mchid && $mchid !== ($data['mch_id'] ?? null)) {
                    throw new Exception\InvalidArgumentException(sprintf(Exception\EV2_REQ_XML_NOTMATCHED_MCHID, $data['mch_id'] ?? '', $mchid));
                }

                $type = $data['sign_type'] ?? Crypto\Hash::ALGO_MD5;

                isset($options['nonceless']) || $data['nonce_str'] = $data['nonce_str'] ?? Formatter::nonce();

                $data['sign'] = Crypto\Hash::sign($type, Formatter::queryStringLike(Formatter::ksort($data)), $secret);

                $modify = ['body' => Transformer::toXml($data)];

                // for security request, it was required the merchant's private_key and certificate
                if (isset($options['security']) && true === $options['security']) {
                    // @uses GuzzleHttp\RequestOptions::SSL_KEY
                    $options['ssl_key'] = $merchant['key'] ?? null;
                    // @uses GuzzleHttp\RequestOptions::CERT
                    $options['cert'] = $merchant['cert'] ?? null;
                }

                unset($options['xml'], $options['nonceless'], $options['security']);

                return $handler(Utils::modifyRequest($request, $modify), $options);
            };
        };
    }

    /**
     * APIv2's transformResponse, doing the `xml2array` then `verify` the signature job only
     *
     * @param string $secret - The secret key string (optional)
     *
     * @return callable(callable(RequestInterface, array))
     */
    public static function transformResponse(string $secret = ''): callable
    {
        return static function (callable $handler) use ($secret): callable {
            return static function (RequestInterface $request, array $options = []) use ($secret, $handler): P\PromiseInterface {
                $promise = $handler($request, $options);

                return $promise->then(static function(ResponseInterface $response) use ($secret) {
                    $result = Transformer::toArray(static::body($response));

                    $sign = $result['sign'] ?? null;
                    $type = $sign && strlen($sign) === 64 ? Crypto\Hash::ALGO_HMAC_SHA256 : Crypto\Hash::ALGO_MD5;

                    if ($sign !== Crypto\Hash::sign($type, Formatter::queryStringLike(Formatter::ksort($result)), $secret)) {
                        return P\Create::rejectionFor($response);
                    }

                    return $response;
                });
            };
        };
    }

    /**
     * Create an APIv2's client
     *
     * @deprecated 1.0 - @see \WeChatPay\Exception\WeChatPayException::DEP_XML_PROTOCOL_UNDER_END_OF_LIFE
     *
     * Optional acceptable \$config parameters
     *   - mchid?: ?string - The merchant ID
     *   - secret?: ?string - The secret key string
     *   - merchant?: array{key?: string, cert?: string} - The merchant private key and certificate array. (optional)
     *   - merchant<?key, string|string[]> - The merchant private key(file path string). (optional)
     *   - merchant<?cert, string|string[]> - The merchant certificate(file path string). (optional)
     *
     * @param array<string,string|int|bool|array|mixed> $config - The configuration
     */
    public static function xmlBased(array $config = []): Client
    {
        /** @var \GuzzleHttp\HandlerStack $handler */
        $handler = isset($config['handler']) && ($config['handler'] instanceof HandlerStack) ? (clone $config['handler']) : HandlerStack::create();
        $handler->before('prepare_body', static::transformRequest($config['mchid'] ?? null, $config['secret'] ?? '', $config['merchant'] ?? []), 'transform_request');
        $handler->before('prepare_body', static::transformResponse($config['secret'] ?? ''), 'transform_response');
        $config['handler'] = $handler;
        $config['headers'] = array_replace_recursive(static::$headers, $config['headers'] ?? []);

        unset($config['mchid'], $config['serial'], $config['privateKey'], $config['certs'], $config['secret'], $config['merchant']);

        return new Client(static::withDefaults($config));
    }
}
