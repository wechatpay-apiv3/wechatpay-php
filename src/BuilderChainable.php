<?php declare(strict_types=1);

namespace WeChatPay;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Signature of the Chainable `GuzzleHttp\Client` interface
 * @property-read OpenAPI\V2 $v2 - The entrance of the APIv2 endpoint
 * @property-read OpenAPI\V3 $v3 - The entrance of the APIv3 endpoint
 */
interface BuilderChainable
{
    /**
     * `$driver` getter
     */
    public function getDriver(): ClientDecoratorInterface;

    /**
     * Chainable the given `$segment` with the `ClientDecoratorInterface` instance
     *
     * @param string $segment - The sgement or `URI`
     */
    public function chain(string $segment): BuilderChainable;

    /**
     * Create and send an HTTP GET request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function get(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP PUT request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function put(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP POST request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function post(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP PATCH request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function patch(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP DELETE request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function delete(array $options = []): ResponseInterface;

    /**
     * Create and send an asynchronous HTTP GET request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function getAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP PUT request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function putAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP POST request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function postAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP PATCH request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function patchAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP DELETE request.
     *
     * @param array<string,string|int|bool|array|mixed> $options Request options to apply.
     */
    public function deleteAsync(array $options = []): PromiseInterface;
}
