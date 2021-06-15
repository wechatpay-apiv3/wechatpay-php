<?php

namespace WeChatPay;

use ArrayAccess;

/**
 * Signature of the Chainable `GuzzleHttp\Client` interface
 */
interface BuilderChainable extends ArrayAccess
{
    /**
     * `$driver` getter
     *
     * @return ClientDecoratorInterface - The `ClientDecorator` instance
     */
    public function getDriver(): ClientDecoratorInterface;

    /**
     * Chainable the given `$key` with the `ClientDecoratorInterface` instance
     *
     * @param string|int $key - The key
     *
     * @return BuilderChainable
     */
    public function offsetGet($key): BuilderChainable;
}
