<?php declare(strict_types=1);

namespace WeChatPay;

use function abs;
use function intval;
use function is_string;
use function is_numeric;
use function is_resource;
use function is_object;
use function is_array;
use function implode;
use function count;
use function sprintf;
use function array_key_exists;
use function array_keys;

use Throwable;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\MessageInterface;

/** @var int - The maximum clock offset in second */
const MAXIMUM_CLOCK_OFFSET = 300;

const WechatpayNonce = 'Wechatpay-Nonce';
const WechatpaySerial = 'Wechatpay-Serial';
const WechatpaySignature = 'Wechatpay-Signature';
const WechatpayTimestamp = 'Wechatpay-Timestamp';

/**
 * JSON based Client interface for sending HTTP requests.
 */
trait ClientJsonTrait
{
    /**
     * @var array<string, string|array<string, string>> - The defaults configuration whose pased in `GuzzleHttp\Client`.
     */
    protected static $defaults = [
        'base_uri' => 'https://api.mch.weixin.qq.com/',
        'headers' => [
            'Accept' => 'application/json, text/plain, application/x-gzip',
            'Content-Type' => 'application/json; charset=utf-8',
        ],
    ];

    abstract protected static function body(MessageInterface $message): string;

    abstract protected static function withDefaults(array ...$config): array;

    /**
     * APIv3's signer middleware stack
     *
     * @param string $mchid - The merchant ID
     * @param string $serial - The serial number of the merchant certificate
     * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string $privateKey - The merchant private key.
     *
     * @return callable(RequestInterface)
     */
    public static function signer(string $mchid, string $serial, $privateKey): callable
    {
        return static function (RequestInterface $request) use ($mchid, $serial, $privateKey): RequestInterface {
            $nonce = Formatter::nonce();
            $timestamp = (string) Formatter::timestamp();
            $signature = Crypto\Rsa::sign(Formatter::request(
                $request->getMethod(), $request->getRequestTarget(), $timestamp, $nonce, static::body($request)
            ), $privateKey);

            return $request->withHeader('Authorization', Formatter::authorization(
                $mchid, $nonce, $signature, $timestamp, $serial
            ));
        };
    }

    /**
     * Assert the HTTP `20X` responses fit for the business logic, otherwise thrown a `\GuzzleHttp\Exception\RequestException`.
     *
     * The `30X` responses were handled by `\GuzzleHttp\RedirectMiddleware`.
     * The `4XX, 5XX` responses were handled by `\GuzzleHttp\Middleware::httpErrors`.
     *
     * @param array<string,\OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string> $certs The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     * @return callable(ResponseInterface,RequestInterface)
     * @throws RequestException
     */
    protected static function assertSuccessfulResponse(array &$certs): callable
    {
        return static function (ResponseInterface $response, RequestInterface $request) use(&$certs): ResponseInterface {
            if (!($response->hasHeader(WechatpayNonce) && $response->hasHeader(WechatpaySerial)
                && $response->hasHeader(WechatpaySignature) && $response->hasHeader(WechatpayTimestamp))) {
                throw new RequestException(sprintf(
                    Exception\WeChatPayException::EV3_RES_HEADERS_INCOMPLETE,
                    WechatpayNonce, WechatpaySerial, WechatpaySignature, WechatpayTimestamp
                ), $request, $response);
            }

            [$nonce] = $response->getHeader(WechatpayNonce);
            [$serial] = $response->getHeader(WechatpaySerial);
            [$signature] = $response->getHeader(WechatpaySignature);
            [$timestamp] = $response->getHeader(WechatpayTimestamp);

            $localTimestamp = Formatter::timestamp();

            if (abs($localTimestamp - intval($timestamp)) > MAXIMUM_CLOCK_OFFSET) {
                throw new RequestException(sprintf(
                    Exception\WeChatPayException::EV3_RES_HEADER_TIMESTAMP_OFFSET,
                    MAXIMUM_CLOCK_OFFSET, $timestamp, $localTimestamp
                ), $request, $response);
            }

            if (!array_key_exists($serial, $certs)) {
                throw new RequestException(sprintf(
                    Exception\WeChatPayException::EV3_RES_HEADER_PLATFORM_SERIAL,
                    $serial, WechatpaySerial, implode(',', array_keys($certs))
                ), $request, $response);
            }

            $verified = false;
            try {
                $verified = Crypto\Rsa::verify(
                    Formatter::response($timestamp, $nonce, static::body($response)),
                    $signature, $certs[$serial]
                );
            } catch (Throwable $exception) {}
            if ($verified === false) {
                throw new RequestException(sprintf(
                    Exception\WeChatPayException::EV3_RES_HEADER_SIGNATURE_DIGEST,
                    $timestamp, $nonce, $signature, $serial
                ), $request, $response, $exception ?? null);
            }

            return $response;
        };
    }

    /**
     * APIv3's verifier middleware stack
     *
     * @param array<string,\OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string> $certs The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     * @return callable(callable(RequestInterface,array))
     */
    public static function verifier(array &$certs): callable
    {
        $assert = static::assertSuccessfulResponse($certs);
        return static function (callable $handler) use ($assert): callable {
            return static function (RequestInterface $request, array $options = []) use ($assert, $handler): PromiseInterface {
                return $handler($request, $options)->then(static function(ResponseInterface $response) use ($assert, $request): ResponseInterface {
                    return $assert($response, $request);
                });
            };
        };
    }

    /**
     * Create an APIv3's client
     *
     * Mandatory \$config array paramters
     *   - mchid: string - The merchant ID
     *   - serial: string - The serial number of the merchant certificate
     *   - privateKey: \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string - The merchant private key.
     *   - certs: array{string, \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string} - The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     *
     * @param array<string,string|int|bool|array|mixed> $config - The configuration
     * @throws \WeChatPay\Exception\InvalidArgumentException
     */
    public static function jsonBased(array $config = []): Client
    {
        if (!(
           isset($config['mchid']) && (is_string($config['mchid']) || is_numeric($config['mchid']))
        )) { throw new Exception\InvalidArgumentException(Exception\ERR_INIT_MCHID_IS_MANDATORY); }

        if (!(
            isset($config['serial']) && is_string($config['serial'])
        )) { throw new Exception\InvalidArgumentException(Exception\ERR_INIT_SERIAL_IS_MANDATORY); }

        if (!(
            isset($config['privateKey']) && (is_string($config['privateKey']) || is_resource($config['privateKey']) || is_object($config['privateKey']))
        )) { throw new Exception\InvalidArgumentException(Exception\ERR_INIT_PRIVATEKEY_IS_MANDATORY); }

        if (!(
            isset($config['certs']) && is_array($config['certs']) && count($config['certs'])
        )) { throw new Exception\InvalidArgumentException(Exception\ERR_INIT_CERTS_IS_MANDATORY); }

        if (array_key_exists($config['serial'], $config['certs'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                Exception\ERR_INIT_CERTS_EXCLUDE_MCHSERIAL, implode(',', array_keys($config['certs'])), $config['serial']
            ));
        }

        /** @var HandlerStack $stack */
        $stack = isset($config['handler']) && ($config['handler'] instanceof HandlerStack) ? (clone $config['handler']) : HandlerStack::create();
        $stack->before('prepare_body', Middleware::mapRequest(static::signer((string)$config['mchid'], $config['serial'], $config['privateKey'])), 'signer');
        $stack->before('http_errors', static::verifier($config['certs']), 'verifier');
        $config['handler'] = $stack;

        unset($config['mchid'], $config['serial'], $config['privateKey'], $config['certs'], $config['secret'], $config['merchant']);

        return new Client(static::withDefaults($config));
    }
}
