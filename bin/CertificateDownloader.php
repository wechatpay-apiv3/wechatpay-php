#!/usr/bin/env php
<?php declare(strict_types=1);

// load autoload.php
$possibleFiles = [__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php', __DIR__.'/../../autoload.php'];
$file = null;
foreach ($possibleFiles as $possibleFile) {
    if (\file_exists($possibleFile)) {
        $file = $possibleFile;
        break;
    }
}
if (null === $file) {
    throw new \RuntimeException('Unable to locate autoload.php file.');
}

require_once $file;
unset($possibleFiles, $possibleFile, $file);

use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\Builder;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Crypto\AesGcm;

 /**
  * CertificateDownloader class
  */
class CertificateDownloader
{
    private const DEFAULT_BASE_URI = 'https://api.mch.weixin.qq.com/';

    public function run(): void
    {
        $opts = $this->parseOpts();

        if (!$opts || isset($opts['help'])) {
            $this->printHelp();
            return;
        }
        if (isset($opts['version'])) {
            self::prompt(ClientDecoratorInterface::VERSION);
            return;
        }
        $this->job($opts);
    }

    /**
     * Before `verifier` executing, decrypt and put the platform certificate(s) into the `$certs` reference.
     *
     * @param string $apiv3Key
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsInjector(string $apiv3Key, array &$certs): callable {
        return static function(ResponseInterface $response) use ($apiv3Key, &$certs): ResponseInterface {
            $body = (string) $response->getBody();
            /** @var object{data:array<object{encrypt_certificate:object{serial_no:string,nonce:string,associated_data:string}}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_map(static function($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
            }, $data);

            return $response;
        };
    }

    /**
     * @param array<string,string|true> $opts
     *
     * @return void
     */
    private function job(array $opts): void
    {
        static $certs = ['any' => null];

        $outputDir = $opts['output'] ?? \sys_get_temp_dir();
        $apiv3Key = (string) $opts['key'];

        $instance = Builder::factory([
            'mchid'      => $opts['mchid'],
            'serial'     => $opts['serialno'],
            'privateKey' => \file_get_contents((string)$opts['privatekey']),
            'certs'      => &$certs,
            'base_uri'   => (string)($opts['baseuri'] ?? self::DEFAULT_BASE_URI),
        ]);

        /** @var \GuzzleHttp\HandlerStack $stack */
        $stack = $instance->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
        // The response middle stacks were executed one by one on `FILO` order.
        $stack->after('verifier', Middleware::mapResponse(self::certsInjector($apiv3Key, $certs)), 'injector');
        $stack->before('verifier', Middleware::mapResponse(self::certsRecorder((string) $outputDir, $certs)), 'recorder');

        $instance->chain('v3/certificates')->getAsync(
            ['debug' => true]
        )->otherwise(static function($exception) {
            self::prompt($exception->getMessage());
            if ($exception instanceof RequestException && $exception->hasResponse()) {
                /** @var ResponseInterface $response */
                $response = $exception->getResponse();
                self::prompt((string) $response->getBody(), '', '');
            }
            self::prompt($exception->getTraceAsString());
        })->wait();
    }

    /**
     * After `verifier` executed, wrote the platform certificate(s) onto disk.
     *
     * @param string $outputDir
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsRecorder(string $outputDir, array &$certs): callable {
        return static function(ResponseInterface $response) use ($outputDir, &$certs): ResponseInterface {
            $body = (string) $response->getBody();
            /** @var object{data:array<object{effective_time:string,expire_time:string:serial_no:string}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_walk($data, static function($row, $index, $certs) use ($outputDir) {
                $serialNo = $row->serial_no;
                $outpath = $outputDir . \DIRECTORY_SEPARATOR . 'wechatpay_' . $serialNo . '.pem';

                self::prompt(
                    'Certificate #' . $index . ' {',
                    '    Serial Number: ' . self::highlight($serialNo),
                    '    Not Before: ' . (new \DateTime($row->effective_time))->format(\DateTime::W3C),
                    '    Not After: ' . (new \DateTime($row->expire_time))->format(\DateTime::W3C),
                    '    Saved to: ' . self::highlight($outpath),
                    '    You may confirm the above infos again even if this library already did(by Crypto\Rsa::verify):',
                    '      ' . self::highlight(\sprintf('openssl x509 -in %s -noout -serial -dates', $outpath)),
                    '    Content: ', '', $certs[$serialNo] ?? '', '',
                    '}'
                );

                \file_put_contents($outpath, $certs[$serialNo]);
            }, $certs);

            return $response;
        };
    }

    /**
     * @param string $thing
     */
    private static function highlight(string $thing): string
    {
        return \sprintf("\x1B[1;32m%s\x1B[0m", $thing);
    }

    /**
     * @param string $messages
     */
    private static function prompt(...$messages): void
    {
        \array_walk($messages, static function (string $message): void { \printf('%s%s', $message, \PHP_EOL); });
    }

    /**
     * @return ?array<string,string|true>
     */
    private function parseOpts(): ?array
    {
        $opts = [
            [ 'key', 'k', true ],
            [ 'mchid', 'm', true ],
            [ 'privatekey', 'f', true ],
            [ 'serialno', 's', true ],
            [ 'output', 'o', false ],
            // baseuri can be one of 'https://api2.mch.weixin.qq.com/', 'https://apihk.mch.weixin.qq.com/'
            [ 'baseuri', 'u', false ],
        ];

        $shortopts = 'hV';
        $longopts = [ 'help', 'version' ];
        foreach ($opts as $opt) {
            [$key, $alias] = $opt;
            $shortopts .= $alias . ':';
            $longopts[] = $key . ':';
        }
        $parsed = \getopt($shortopts, $longopts);

        if (!$parsed) {
            return null;
        }

        $args = [];
        foreach ($opts as $opt) {
            [$key, $alias, $mandatory] = $opt;
            if (isset($parsed[$key]) || isset($parsed[$alias])) {
                /** @var string|string[] $possible */
                $possible = $parsed[$key] ?? $parsed[$alias] ?? '';
                $args[$key] = \is_array($possible) ? $possible[0] : $possible;
            } elseif ($mandatory) {
                return null;
            }
        }

        if (isset($parsed['h']) || isset($parsed['help'])) {
            $args['help'] = true;
        }
        if (isset($parsed['V']) || isset($parsed['version'])) {
            $args['version'] = true;
        }
        return $args;
    }

    private function printHelp(): void
    {
        self::prompt(
            'Usage: 微信支付平台证书下载工具 [-hV]',
            '                    -f=<privateKeyFilePath> -k=<apiv3Key> -m=<merchantId>',
            '                    -s=<serialNo> -o=[outputFilePath] -u=[baseUri]',
            'Options:',
            '  -m, --mchid=<merchantId>   商户号',
            '  -s, --serialno=<serialNo>  商户证书的序列号',
            '  -f, --privatekey=<privateKeyFilePath>',
            '                             商户的私钥文件',
            '  -k, --key=<apiv3Key>       APIv3密钥',
            '  -o, --output=[outputFilePath]',
            '                             下载成功后保存证书的路径，可选，默认为临时文件目录夹',
            '  -u, --baseuri=[baseUri]    接入点，可选，默认为 ' . self::DEFAULT_BASE_URI,
            '  -V, --version              Print version information and exit.',
            '  -h, --help                 Show this help message and exit.', ''
        );
    }
}

// main
(new CertificateDownloader())->run();
