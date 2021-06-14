<?php

namespace WechatPay\GuzzleMiddleware;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorate the `GuzzleHttp\Client` interface
 */
interface ClientDecoratorInterface
{
    /**
     * @var string - The WechatPayMiddleware version
     */
    const VERSION = '1.0.0';

    /**
     * Request the remote `$pathname` by a HTTP `$method` verb
     *
     * @param string $pathname - The pathname string.
     * @param string $method - The method string.
     * @param array $options - The options.
     *
     * @return ResponseInterface - The `Psr\Http\Message\ResponseInterface` instance
     */
    public function request(string $method, string $pathname, array $options = []): ResponseInterface;

    /**
     * Async request the remote `$pathname` by a HTTP `$method` verb
     *
     * @param string $pathname - The pathname string.
     * @param string $method - The method string.
     * @param array $options - The options.
     *
     * @return PromiseInterface - The `GuzzleHttp\Promise\PromiseInterface` instance
     */
    public function requestAsync(string $method, string $pathname, array $options = []): PromiseInterface;
}
