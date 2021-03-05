<?php
/**
 * WechatPayMiddleware
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace WechatPay\GuzzleMiddleware;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WechatPay\GuzzleMiddleware\Credentials;
use WechatPay\GuzzleMiddleware\Validator;
use WechatPay\GuzzleMiddleware\WechatPayMiddlewareBuilder;

/**
 * WechatPayMiddleware
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class WechatPayMiddleware
{
    /**
     * WechatPayMiddleware version
     *
     * @var string
     */
    const VERSION = "0.2.0";

    /**
     * WechatPay API domain
     *
     * @var string
     */
    protected static $API_DOMAINS = [
        'api.mch.weixin.qq.com',
        'api2.mch.weixin.qq.com',
        'apius.mch.weixin.qq.com',
        'apihk.mch.weixin.qq.com'
    ];

    /**
     * WechatPay API base urls
     *
     * @var array of string
     */
    protected static $BASE_URLS = [
        '/v3/',
        '/sandbox/v3/',
        '/hk/v3/'
    ];

    /**
     * Merchant credentials
     *
     * @var Credentials
     */
    protected $credentials;

    /**
     * Response Validator
     *
     *  @var Validator
     */
    protected $validator;

    /**
     * Constructor
     */
    public function __construct(Credentials $credentials, Validator $validator)
    {
        $this->credentials = $credentials;
        $this->validator = $validator;
    }

    /**
     * Use as Guzzle middleware
     *
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if (!self::isWechatPayApiUrl($request->getUri())) {
                return $handler($request, $options);
            }
            if (!$request->getBody()->isSeekable() && \class_exists("\\GuzzleHttp\\Psr7\\CachingStream")) {
                $request = $request->withBody(new \GuzzleHttp\Psr7\CachingStream($request->getBody()));
            }
            $schema = $this->credentials->getSchema();
            $token = $this->credentials->getToken($request);
            $request = $request->withHeader("Authorization", $schema.' '.$token);
            if (self::isUserAgentOverwritable($request)) {
                $request = $request->withHeader('User-Agent', self::getUserAgent());
            }

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request) {
                    $code = $response->getStatusCode();
                    if ($code >= 200 && $code < 300) {
                        if (!$response->getBody()->isSeekable() && \class_exists("\\GuzzleHttp\\Psr7\\CachingStream")) {
                            $response = $response->withBody(new \GuzzleHttp\Psr7\CachingStream($response->getBody()));
                        }
                        if (!$this->validator->validate($response)) {
                            if (\class_exists('\\GuzzleHttp\\Exception\\ServerException')) {
                                throw new \GuzzleHttp\Exception\ServerException(
                                    "应答的微信支付签名验证失败", $request, $response);
                            } else {
                                throw new \RuntimeException("应答的微信支付签名验证失败", $code);
                            }
                        }
                    }
                    return $response;
                }
            );
        };
    }

    /**
     * Create a new builder
     *
     * @return WechatPayMiddlewareBuilder
     */
    public static function builder()
    {
        return new WechatPayMiddlewareBuilder();
    }

    /**
     * Check whether url is WechatPay API V3 url
     */
    protected static function isWechatPayApiUrl(UriInterface $url)
    {
        if ($url->getScheme() !== 'https' || !\in_array($url->getHost(), self::$API_DOMAINS)) {
            return false;
        }
        foreach (self::$BASE_URLS as $baseUrl) {
            if (\substr($url->getPath(), 0, strlen($baseUrl)) === $baseUrl) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get User Agent
     * @return string
     */
    protected static function getUserAgent()
    {
        static $userAgent = '';
        if (!$userAgent) {
            $agent = 'WechatPay-Guzzle/'.self::VERSION;
            if (\class_exists('\\GuzzleHttp\\Client')) {
                $version = defined('\\GuzzleHttp\\Client::VERSION') ? \GuzzleHttp\Client::VERSION
                                                                    : \GuzzleHttp\Client::MAJOR_VERSION;
                $agent .= ' GuzzleHttp/'.$version;
            }
            if (extension_loaded('curl') && function_exists('curl_version')) {
                $agent .= ' curl/'.\curl_version()['version'];
            }
            $agent .= \sprintf(" (%s/%s) PHP/%s", PHP_OS, \php_uname('r'), PHP_VERSION);
            $userAgent = $agent;
        }
        return $userAgent;
    }

    private static function isUserAgentOverwritable(RequestInterface $request)
    {
        if (!$request->hasHeader('User-Agent')) {
            return true;
        }
        $headers = $request->getHeader('User-Agent');
        $userAgent = $headers[\count($headers) - 1];
        if (\function_exists('\\GuzzleHttp\\default_user_agent')) {
            return $userAgent === \GuzzleHttp\default_user_agent();
        }
        return false;
    }
}
