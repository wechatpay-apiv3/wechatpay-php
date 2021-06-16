<?php

namespace WeChatPay;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Signature of the Chainable `GuzzleHttp\Client` interface
 */
interface BuilderChainable
{
    /**
     * `$driver` getter
     *
     * @return ClientDecoratorInterface - The `ClientDecorator` instance
     */
    public function getDriver(): ClientDecoratorInterface;

    /**
     * Chainable the given `$segment` with the `ClientDecoratorInterface` instance
     *
     * @param string|int $segment - The sgement or `URI`
     *
     * @return BuilderChainable
     */
    public function chain($segment): BuilderChainable;

    /**
     * Create and send an HTTP GET request.
     *
     * @param array $options Request options to apply.
     */
    public function get(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP PUT request.
     *
     * @param array $options Request options to apply.
     */
    public function put(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP POST request.
     *
     * @param array $options Request options to apply.
     */
    public function post(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP PATCH request.
     *
     * @param array $options Request options to apply.
     */
    public function patch(array $options = []): ResponseInterface;

    /**
     * Create and send an HTTP DELETE request.
     *
     * @param array $options Request options to apply.
     */
    public function delete(array $options = []): ResponseInterface;

    /**
     * Create and send an asynchronous HTTP GET request.
     *
     * @param array $options Request options to apply.
     */
    public function getAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP PUT request.
     *
     * @param array $options Request options to apply.
     */
    public function putAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP POST request.
     *
     * @param array $options Request options to apply.
     */
    public function postAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP PATCH request.
     *
     * @param array $options Request options to apply.
     */
    public function patchAsync(array $options = []): PromiseInterface;

    /**
     * Create and send an asynchronous HTTP DELETE request.
     *
     * @param array $options Request options to apply.
     */
    public function deleteAsync(array $options = []): PromiseInterface;
}
