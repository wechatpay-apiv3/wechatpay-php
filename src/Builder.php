<?php
namespace WechatPay\GuzzleMiddleware;

/**
 * Chainable the client for sending HTTP requests.
 */
class Builder
{
    /**
     * Building & decorate the chainable `\GuzzleHttp\Client`
     *
     * @example
     * $wxpay = Builder::factory([]);
     * $res = $wxpay['v3/merchantService/complaintsV2']->get(['debug' => true]);
     * $res = $wxpay['v3/merchant-service/complaint-notifications']->get(['debug' => true]);
     * $wxpay->v3->merchantService->ComplaintNotifications->postAsync([])->wait();
     * $wxpay->v3->certificates->getAsync()->then()->otherwise()->wait();
     *
     * @param array $config - configuration
     * @param string|int $config[mchid] - The merchant ID
     * @param string $config[serial] - The serial number of the merchant certificate
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|resource|array|string $config[privateKey] - The merchant private key.
     * @param array $config[certs] - The WeChatPay platform serial and certificate(s), `[serial => certificate]` pair
     *
     * @return BuilderChainable - The chainable client
     */
    public static function factory(array $config = [])
    {
        return new class([], new ClientDecorator($config)) extends \ArrayIterator implements BuilderChainable
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
             * `$driver` getter
             *
             * @return ClientDecoratorInterface - The `ClientDecorator` instance
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
             * @param string thing - The string waiting for normalization
             *
             * @return string
             */
            protected function normalize(string $thing = ''): string
            {
                return \preg_replace('/^_(.*)_$/', '{\1}', \preg_replace_callback('/[A-Z]/', static function($piece) {
                    return '-' . \strtolower($piece[0]);
                }, \preg_replace_callback('/^[A-Z]/', static function($piece) {
                    return \strtolower($piece);
                }, $thing)));
            }

            /**
             * URI pathname
             *
             * @param string [$seperator = '/'] - The URI seperator
             *
             * @return string - The URI string
             */
            protected function pathname(string $seperator = '/'): string
            {
                return \implode($seperator, $this->simplized());
            }

            /**
             * Only retrieve a copy array of the URI entities
             *
             * @return array - The URI entities' array
             */
            protected function simplized(): array
            {
                return \array_filter($this->getArrayCopy(), static function($v) { return !($v instanceof BuilderChainable); });
            }

            /**
             * Chainable the given `$key` with the `ClientDecoratorInterface` instance
             *
             * @param string|int $key - The key
             *
             * @return BuilderChainable
             */
            public function offsetGet($key): BuilderChainable
            {
                if (!$this->offsetExists($key)) {
                  $index = $this->simplized();
                  \array_push($index, $this->normalize($key));
                  $this->offsetSet($key, new self($index, $this->getDriver()));
                }

                return parent::offsetGet($key);
            }
        };
    }

    private function __construct()
    {
        // cannot be instantiated
    }
}
