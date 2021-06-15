<?php

namespace WechatPay\GuzzleMiddleware;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Chainable points the client interface for sending HTTP requests.
 */
trait BuilderTrait
{
    /**
     * `$driver` getter
     *
     * @return ClientDecoratorInterface - The `ClientDecorator` instance
     */
    abstract public function getDriver(): ClientDecoratorInterface;

    /**
     * URI pathname
     *
     * @param string [$seperator = '/'] - The URI seperator
     *
     * @return string - The URI string
     */
    abstract protected function pathname(string $seperator = '/'): string;

    /**
     * Create and send an HTTP GET request.
     *
     * @param array $options Request options to apply.
     */
    public function get(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('GET', $this->pathname(), $options);
    }

    /**
     * Create and send an HTTP PUT request.
     *
     * @param array $options Request options to apply.
     */
    public function put(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('PUT', $this->pathname(), $options);
    }

    /**
     * Create and send an HTTP POST request.
     *
     * @param array $options Request options to apply.
     */
    public function post(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('POST', $this->pathname(), $options);
    }

    /**
     * Create and send an HTTP PATCH request.
     *
     * @param array $options Request options to apply.
     */
    public function patch(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('PATCH', $this->pathname(), $options);
    }

    /**
     * Create and send an HTTP DELETE request.
     *
     * @param array $options Request options to apply.
     */
    public function delete(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('DELETE', $this->pathname(), $options);
    }

    /**
     * Create and send an asynchronous HTTP GET request.
     *
     * @param array $options Request options to apply.
     */
    public function getAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('GET', $this->pathname(), $options);
    }

    /**
     * Create and send an asynchronous HTTP PUT request.
     *
     * @param array $options Request options to apply.
     */
    public function putAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('PUT', $this->pathname(), $options);
    }

    /**
     * Create and send an asynchronous HTTP POST request.
     *
     * @param array $options Request options to apply.
     */
    public function postAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('POST', $this->pathname(), $options);
    }

    /**
     * Create and send an asynchronous HTTP PATCH request.
     *
     * @param array $options Request options to apply.
     */
    public function patchAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('PATCH', $this->pathname(), $options);
    }

    /**
     * Create and send an asynchronous HTTP DELETE request.
     *
     * @param array $options Request options to apply.
     */
    public function deleteAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('DELETE', $this->pathname(), $options);
    }
}
