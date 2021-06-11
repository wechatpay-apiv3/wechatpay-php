<?php
namespace WechatPay\GuzzleMiddleware;

/**
 * Signature of the Chainable `\GuzzleHttp\Client` interface
 */
interface BuilderChainable extends \ArrayAccess
{
    /**
     * Chainable the given `$key` with the `ClientDecoratorInterface` instance
     *
     * @param string|int $key - The key
     *
     * @return BuilderChainable
     */
    public function offsetGet($key): BuilderChainable;
}
