<?php

namespace WeChatPay;

use function preg_replace;
use function preg_replace_callback;
use function strtolower;
use function implode;
use function array_filter;
use function array_push;

use ArrayIterator;

/**
 * Chainable the client for sending HTTP requests.
 */
final class Builder
{
    /**
     * Building & decorate the chainable `GuzzleHttp\Client`
     *
     * ```php
     * // usage samples
     * $instance = Builder::factory([]);
     * $res = $instance['v3/merchantService/complaintsV2']->get(['debug' => true]);
     * $res = $instance['v3/merchant-service/complaint-notifications']->get(['debug' => true]);
     * $instance->v3->merchantService->ComplaintNotifications->postAsync([])->wait();
     * $instance->v3->certificates->getAsync()->then(function() {})->otherwise(function() {})->wait();
     * ```
     *
     * Acceptable \$config parameters stucture
     *   - mchid: string - The merchant ID
     *   - serial: string - The serial number of the merchant certificate
     *   - privateKey: OpenSSLAsymmetricKey|OpenSSLCertificate|resource|array|string - The merchant private key.
     *   - certs: [$key:string => $value: mixed] - The wechatpay platform serial and certificate(s)
     *   - secret: ?string - The secret key string (optional)
     *   - merchant: ?array - The merchant private key and certificate array (optional)
     *   - merchant<key: ?string> - The merchant private key(file path string). (optional)
     *   - merchant<cert: ?string> - The merchant certificate(file path string). (optional)
     *
     * @return BuilderChainable - The chainable client
     */
    public static function factory(array $config = [])
    {
        return new class([], new ClientDecorator($config)) extends ArrayIterator implements BuilderChainable
        {
            use BuilderTrait;

            /**
             * Compose the chainable `ClientDecorator` instance, most starter with the tree root point
             */
            public function __construct(array $input = [], ?ClientDecoratorInterface $instance = null) {
                parent::__construct($input, self::STD_PROP_LIST | self::ARRAY_AS_PROPS);

                $this->setDriver($instance);
            }

            /**
             * @var ClientDecoratorInterface $driver - The `ClientDecorator` instance
             */
            protected $driver;

            /**
             * `$driver` setter
             * @param ClientDecoratorInterface $instance - The `ClientDecorator` instance
             *
             * @return BuilderChainable
             */
            public function setDriver(ClientDecoratorInterface &$instance)
            {
                $this->driver = $instance;

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function getDriver(): ClientDecoratorInterface
            {
                return $this->driver;
            }

            /**
             * Normalize the `$thing` by the rules: `PascalCase` -> `camelCase`
             *                                    & `camelCase` -> `camel-case`
             *                                    & `_dynamic_` -> `{dynamic}`
             *
             * @param string $thing - The string waiting for normalization
             *
             * @return string
             */
            protected function normalize(string $thing = ''): string
            {
                return preg_replace('#^_(.*)_$#', '{\1}', preg_replace_callback('#[A-Z]#', static function($piece) {
                    return '-' . strtolower($piece[0]);
                }, preg_replace_callback('#^[A-Z]#', static function($piece) {
                    return strtolower($piece);
                }, $thing)));
            }

            /**
             * URI pathname
             *
             * @param string $seperator- The URI seperator, default is slash(`/`) character
             *
             * @return string - The URI string
             */
            protected function pathname(string $seperator = '/'): string
            {
                return implode($seperator, $this->simplized());
            }

            /**
             * Only retrieve a copy array of the URI entities
             *
             * @return array - The URI entities' array
             */
            protected function simplized(): array
            {
                return array_filter($this->getArrayCopy(), static function($v) { return !($v instanceof BuilderChainable); });
            }

            /**
             * @inheritDoc
             */
            public function offsetGet($key): BuilderChainable
            {
                if (!$this->offsetExists($key)) {
                  $index = $this->simplized();
                  array_push($index, $this->normalize($key));
                  $this->offsetSet($key, new self($index, $this->getDriver()));
                }

                return parent::offsetGet($key);
            }

            /**
             * @inheritDoc
             */
            public function chain($segment): BuilderChainable
            {
                return $this->offsetGet($segment);
            }
        };
    }

    private function __construct()
    {
        // cannot be instantiated
    }
}
