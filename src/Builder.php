<?php declare(strict_types=1);

namespace WeChatPay;

use function preg_replace_callback_array;
use function strtolower;
use function implode;
use function array_filter;

use ArrayIterator;

/**
 * Chainable the client for sending HTTP requests.
 */
final class Builder
{
    /**
     * Building & decorate the chainable `GuzzleHttp\Client`
     *
     * Minimum mandatory \$config parameters structure
     *   - mchid: string - The merchant ID
     *   - serial: string - The serial number of the merchant certificate
     *   - privateKey: \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string - The merchant private key.
     *   - certs: array<string, \OpenSSLAsymmetricKey|\OpenSSLCertificate|object|resource|string> - The wechatpay platform serial and certificate(s), `[$serial => $cert]` pair
     *   - secret?: string - The secret key string (optional)
     *   - merchant?: array{key?: string, cert?: string} - The merchant private key and certificate array. (optional)
     *   - merchant<?key, string|string[]> - The merchant private key(file path string). (optional)
     *   - merchant<?cert, string|string[]> - The merchant certificate(file path string). (optional)
     *
     * ```php
     * // usage samples
     * $instance = Builder::factory([]);
     * $res = $instance->chain('v3/merchantService/complaintsV2')->get(['debug' => true]);
     * $res = $instance->chain('v3/merchant-service/complaint-notifications')->get(['debug' => true]);
     * $instance->v3->merchantService->ComplaintNotifications->postAsync([])->wait();
     * $instance->v3->certificates->getAsync()->then(function() {})->otherwise(function() {})->wait();
     * ```
     *
     * @param array<string,string|int|bool|array|mixed> $config - `\GuzzleHttp\Client`, `APIv3` and `APIv2` configuration settings.
     */
    public static function factory(array $config = []): BuilderChainable
    {
        return new class([], new ClientDecorator($config)) extends ArrayIterator implements BuilderChainable
        {
            use BuilderTrait;

            /**
             * Compose the chainable `ClientDecorator` instance, most starter with the tree root point
             * @param string[] $input
             * @param ?ClientDecoratorInterface $instance
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
             */
            public function setDriver(ClientDecoratorInterface &$instance): BuilderChainable
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
                return preg_replace_callback_array([
                    '#^[A-Z]#'   => static function(array $piece): string { return strtolower($piece[0]); },
                    '#[A-Z]#'    => static function(array $piece): string { return '-' . strtolower($piece[0]); },
                    '#^_(.*)_$#' => static function(array $piece): string { return '{' . $piece[1] . '}'; },
                ], $thing) ?? $thing;
            }

            /**
             * URI pathname
             *
             * @param string $seperator - The URI seperator, default is slash(`/`) character
             *
             * @return string - The URI string
             */
            protected function pathname(string $seperator = '/'): string
            {
                return implode($seperator, $this->simplized());
            }

            /**
             * Only retrieve a copy array of the URI segments
             *
             * @return string[] - The URI segments array
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
                    $indices   = $this->simplized();
                    $indices[] = $this->normalize($key);
                    $this->offsetSet($key, new self($indices, $this->getDriver()));
                }

                return parent::offsetGet($key);
            }

            /**
             * @inheritDoc
             */
            public function chain(string $segment): BuilderChainable
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
