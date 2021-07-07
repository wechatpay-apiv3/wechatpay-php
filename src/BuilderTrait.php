<?php declare(strict_types=1);

namespace WeChatPay;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Chainable points the client interface for sending HTTP requests.
 */
trait BuilderTrait
{
    abstract public function getDriver(): ClientDecoratorInterface;

    /**
     * URI pathname
     *
     * @param string $seperator - The URI seperator, default is slash(`/`) character
     *
     * @return string - The URI string
     */
    abstract protected function pathname(string $seperator = '/'): string;

    /**
     * @inheritDoc
     */
    public function get(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('GET', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function put(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('PUT', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function post(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('POST', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function patch(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('PATCH', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function delete(array $options = []): ResponseInterface
    {
        return $this->getDriver()->request('DELETE', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function getAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('GET', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function putAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('PUT', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function postAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('POST', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function patchAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('PATCH', $this->pathname(), $options);
    }

    /**
     * @inheritDoc
     */
    public function deleteAsync(array $options = []): PromiseInterface
    {
        return $this->getDriver()->requestAsync('DELETE', $this->pathname(), $options);
    }
}
