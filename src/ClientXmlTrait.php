<?php

namespace WechatPay\GuzzleMiddleware;

use function assert;
use function strlen;
use function array_replace_recursive;

use InvalidArgumentException;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Promise as P;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @var array - The default headers whose passed in `GuzzleHttp\Client`.
     */
    protected static $headers = [
        'Accept' => 'text/xml, text/plain, application/x-gzip',
        'Content-Type' => 'text/xml; charset=utf-8',
    ];

    /**
     * APIv2's transformRequest, did the `datasign` and `array2xml` together
     *
     * @return callable
     */
    public static function transformRequest(?string $mchid = null, ?string $secret = null, ?array $merchant = null): callable
    {
        return static function (callable $handler) use ($mchid, $secret, $merchant): callable {
            return static function (RequestInterface $request, array $options = []) use ($handler, $mchid, $secret, $merchant) {
                $data = $options['xml'] ?? [];

                assert(
                    $mchid === ($data['mch_id'] ?? null),
                    new InvalidArgumentException("The xml's mch_id({$data['mch_id']}) doesn't matched the init one ({$mchid}).")
                );

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
     * @return callable
     */
    public static function transformResponse(?string $secret = null): callable
    {
        return static function (callable $handler) use ($secret): callable {
            return static function (RequestInterface $request, array $options = []) use ($secret, $handler) {
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
     * @param array $config - configuration
     * @param string|int $config[mchid] - The merchant ID
     * @param string $config[secret] - The secret key string
     * @param array $config[merchant] - The merchant private key and certificate array
     * @param string $config[merchant[key]] - The merchant private key(file path string).
     * @param string $config[merchant[cert]] - The merchant certificate(file path string).
     *
     * @return Client - The `GuzzleHttp\Client` instance
     */
    public static function xmlBased(array $config = []): Client
    {
        $handler = $config['handler'] ?? HandlerStack::create();
        $handler->before('prepare_body', static::transformRequest($config['mchid'] ?? null, $config['secret'] ?? null, $config['merchant'] ?? []), 'transform_request');
        $handler->before('prepare_body', static::transformResponse($config['secret'] ?? null), 'transform_response');
        $config['handler'] = $handler;
        $config['headers'] = array_replace_recursive(static::$headers, $config['headers'] ?? []);

        unset($config['mchid'], $config['serial'], $config['privateKey'], $config['certs'], $config['secret'], $config['merchant']);

        return new Client(static::withDefaults($config));
    }
}
