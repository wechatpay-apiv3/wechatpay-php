#!/usr/bin/env php
<?php

// load autoload.php
$possibleFiles = [__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php', __DIR__.'/../../autoload.php'];
$file = null;
foreach ($possibleFiles as $possibleFile) {
    if (file_exists($possibleFile)) {
        $file = $possibleFile;
        break;
    }
}
if (null === $file) {
    throw new RuntimeException('Unable to locate autoload.php file.');
}

require_once $file;
unset($possibleFiles, $possibleFile, $file);

use GuzzleHttp\Middleware;
use GuzzleHttp\Utils;
use WeChatPay\Builder;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Crypto\AesGcm;

 /**
  * CertificateDownloader class
  */
class CertificateDownloader
{
    public function run(): void
    {
        $opts = $this->parseOpts();

        if (!$opts) {
            $this->printHelp();
            return;
        }

        if (isset($opts['help'])) {
            $this->printHelp();
            return;
        }
        if (isset($opts['version'])) {
            echo ClientDecoratorInterface::VERSION, PHP_EOL;
            return;
        }
        $this->job($opts);
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
            'mchid' => $opts['mchid'],
            'serial' => $opts['serialno'],
            'privateKey' => \file_get_contents((string)$opts['privatekey']),
            'certs' => &$certs,
        ]);

        $handler = $instance->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
        $handler->after('verifier', Middleware::mapResponse(static function($response) use ($apiv3Key, &$certs) {
            $body = $response->getBody()->getContents();
            /** @var object{data:array<object{encrypt_certificate:object{serial_no:string,nonce:string,associated_data:string}}>} $json */
            $json = Utils::jsonDecode($body);
            \array_map(static function($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
            }, \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : []);

            return $response;
        }), 'injector');

        $instance->chain('v3/certificates')->getAsync(['debug' => true])->then(static function($response) use ($outputDir, &$certs) {
            $body = $response->getBody()->getContents();
            $timeZone = new \DateTimeZone('Asia/Shanghai');
            /** @var object{data:array<object{effective_time:string,expire_time:string:serial_no:string}>} $json */
            $json = Utils::jsonDecode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_walk($data, static function($row, $index, $certs) use ($outputDir, $timeZone) {
                $serialNo = $row->serial_no;
                $outpath = $outputDir . DIRECTORY_SEPARATOR . 'wechatpay_' . $serialNo . '.pem';

                echo 'Certificate #', $index, ' {', PHP_EOL;
                echo '    Serial Number: ', $serialNo, PHP_EOL;
                echo '    Not Before: ', (new \DateTime($row->effective_time, $timeZone))->format('Y-m-d H:i:s'), PHP_EOL;
                echo '    Not After: ', (new \DateTime($row->expire_time, $timeZone))->format('Y-m-d H:i:s'), PHP_EOL;
                echo '    Saved to: ', $outpath, PHP_EOL;
                echo '    Content: ', PHP_EOL, PHP_EOL, $certs[$serialNo], PHP_EOL, PHP_EOL;
                echo '}', PHP_EOL;

                \file_put_contents($outpath, $certs[$serialNo]);
            }, $certs);

            return $response;
        })->otherwise(static function($exception) {
            $body = $exception->getResponse()->getBody();
            echo $body->getContents(), PHP_EOL, PHP_EOL, PHP_EOL;
            echo $exception->getTraceAsString(), PHP_EOL;
        })->wait();
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
        ];

        $shortopts = 'hV';
        $longopts = [ 'help', 'version' ];
        foreach ($opts as $opt) {
            list($key, $alias) = $opt;
            $shortopts .= $alias . ':';
            $longopts[] = $key . ':';
        }
        $parsed = \getopt($shortopts, $longopts);

        if (!$parsed) {
            return null;
        }

        $args = [];
        foreach ($opts as $opt) {
            list($key, $alias, $mandatory) = $opt;
            if (isset($parsed[$key]) || isset($parsed[$alias])) {
                $possiable = $parsed[$key] ?? $parsed[$alias] ?? '';
                $args[$key] = (string) (is_array($possiable) ? $possiable[0] : $possiable);
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
        echo <<<EOD
Usage: 微信支付平台证书下载工具 [-hV]
                    -f=<privateKeyFilePath> -k=<apiv3Key> -m=<merchantId>
                    -o=[outputFilePath] -s=<serialNo>
  -m, --mchid=<merchantId>   商户号
  -s, --serialno=<serialNo>  商户证书的序列号
  -f, --privatekey=<privateKeyFilePath>
                             商户的私钥文件
  -k, --key=<apiv3Key>       API v3密钥
  -o, --output=[outputFilePath]
                             下载成功后保存证书的路径，可选参数，默认为临时文件目录夹
  -V, --version              Print version information and exit.
  -h, --help                 Show this help message and exit.

EOD;
    }
}

// main
(new CertificateDownloader())->run();
