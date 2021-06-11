<?php
namespace WechatPay\GuzzleMiddleware;

/**
 * Decorate the `\GuzzleHttp\Client` interface
 */
interface ClientDecoratorInterface
{
    /**
     * @var string - The WechatPayMiddleware version
     */
    const VERSION = '1.0.0';
}
