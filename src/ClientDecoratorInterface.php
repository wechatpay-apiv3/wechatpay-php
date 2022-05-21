<?php declare(strict_types=1);

namespace WeChatPay;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorate the `GuzzleHttp\Client` interface
 */
interface ClientDecoratorInterface
{
    /**
     * @var string - This library version
     */
    public const VERSION = '1.4.5';

    /**
     * @var string - The HTTP transfer `xml` based protocol
     * @deprecated 1.0 - @see \WeChatPay\Exception\WeChatPayException::DEP_XML_PROTOCOL_IS_REACHABLE_EOL
     */
    public const XML_BASED = 'v2';

    /**
     * @var string - The HTTP transfer `json` based protocol
     */
    public const JSON_BASED = 'v3';

    /**
     * Protocol selector
     *
     * @param string|null $protocol - one of the constants of `XML_BASED`, `JSON_BASED`, default is `JSON_BASED`
     * @return ClientInterface
     */
    public function select(?string $protocol = null): ClientInterface;

    /**
     * Request the remote `$uri` by a HTTP `$method` verb
     *
     * @param string $uri - The uri string.
     * @param string $method - The method string.
     * @param array<string,string|int|bool|array|mixed> $options - The options.
     *
     * @return ResponseInterface - The `Psr\Http\Message\ResponseInterface` instance
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface;

    /**
     * Async request the remote `$uri` by a HTTP `$method` verb
     *
     * @param string $uri - The uri string.
     * @param string $method - The method string.
     * @param array<string,string|int|bool|array|mixed> $options - The options.
     *
     * @return PromiseInterface - The `GuzzleHttp\Promise\PromiseInterface` instance
     */
    public function requestAsync(string $method, string $uri, array $options = []): PromiseInterface;
}
