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


use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;
use WechatPay\GuzzleMiddleware\Validator;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\Util\AesUtil;
use WechatPay\GuzzleMiddleware\Auth\CertificateVerifier;
use WechatPay\GuzzleMiddleware\Auth\WechatPay2Validator;

class CertificateDownloader
{
    const VERSION = '0.1.0';

    public function run()
    {
        $opts = $this->parseOpts();
        if (!$opts) {
            $this->printHelp();
            exit(1);
        }

        if (isset($opts['help'])) {
            $this->printHelp();
            exit(0);
        }
        if (isset($opts['version'])) {
            echo self::VERSION . "\n";
            exit(0);
        }

        $this->downloadCert($opts);
    }

    private function downloadCert($opts)
    {
        try {
            // 构造一个WechatPayMiddleware
            $builder = WechatPayMiddleware::builder()
                ->withMerchant($opts['mchid'], $opts['serialno'], PemUtil::loadPrivateKey($opts['privatekey'])); // 传入商户相关配置
            if (isset($opts['wechatpay-cert'])) {
                $builder->withWechatPay([ PemUtil::loadCertificate($opts['wechatpay-cert']) ]); // 使用平台证书验证
            }
            else {
                $builder->withValidator(new NoopValidator); // 临时"跳过”应答签名的验证
            }
            $wechatpayMiddleware = $builder->build();

            // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
            $stack = HandlerStack::create();
            $stack->push($wechatpayMiddleware, 'wechatpay');

            // 创建Guzzle HTTP Client时，将HandlerStack传入
            $client = new GuzzleHttp\Client(['handler' => $stack]);

            // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
            $resp = $client->request('GET', 'https://api.mch.weixin.qq.com/v3/certificates', [
                'headers' => [ 'Accept' => 'application/json' ]
            ]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() > 299) {
                echo "download failed, code={$resp->getStatusCode()}, body=[{$resp->getBody()}]\n";
                return;
            }

            $list = json_decode($resp->getBody(), true);

            $plainCerts = [];
            $x509Certs = [];

            $decrypter = new AesUtil($opts['key']);
            foreach ($list['data'] as $item) {
                $encCert = $item['encrypt_certificate'];
                $plain = $decrypter->decryptToString($encCert['associated_data'],
                    $encCert['nonce'], $encCert['ciphertext']);
                if (!$plain) {
                    echo "encrypted certificate decrypt fail!\n";
                    exit(1);
                }
                // 通过加载对证书进行简单合法性检验
                $cert = \openssl_x509_read($plain); // 从字符串中加载证书
                if (!$cert) {
                    echo "downloaded certificate check fail!\n";
                    exit(1);
                }
                $plainCerts[] = $plain;
                $x509Certs[] = $cert;
            }
            // 使用下载的证书再来验证一次应答的签名
            $validator = new WechatPay2Validator(new CertificateVerifier($x509Certs));
            if (!$validator->validate($resp)) {
                echo "validate response fail using downloaded certificates!";
                exit(1);
            }
            // 输出证书信息，并保存到文件
            foreach ($list['data'] as $index => $item) {
                echo "Certificate {\n";
                echo "    Serial Number: ".$item['serial_no']."\n";
                echo "    Not Before: ".(new DateTime($item['effective_time']))->format('Y-m-d H:i:s')."\n";
                echo "    Not After: ".(new DateTime($item['expire_time']))->format('Y-m-d H:i:s')."\n";
                echo "    Text: \n    ".str_replace("\n", "\n    ", $plainCerts[$index])."\n";
                echo "}\n";

                $outpath = $opts['output'].DIRECTORY_SEPARATOR.'wechatpay_'.$item['serial_no'].'.pem';
                file_put_contents($outpath, $plainCerts[$index]);
            }
        }
        catch (RequestException $e) {
            echo "download failed, message=[{$e->getMessage()}] ";
            if ($e->hasResponse()) {
                echo "code={$e->getResponse()->getStatusCode()}, body=[{$e->getResponse()->getBody()}]\n";
            }
            exit(1);
        }
        catch (Exception $e) {
            echo "download failed, message=[{$e->getMessage()}]\n";
            echo $e;
            exit(1);
        }
    }

    private function parseOpts()
    {
        $opts = [
            [ 'key', 'k', true ],
            [ 'mchid', 'm', true ],
            [ 'privatekey', 'f', true ],
            [ 'serialno', 's', true ],
            [ 'output', 'o', true ],
            [ 'wechatpay-cert', 'c', false ],
        ];

        $shortopts = 'hV';
        $longopts = [ 'help', 'version' ];
        foreach ($opts as $opt) {
            $shortopts .= $opt[1].':';
            $longopts[] = $opt[0].':';
        }
        $parsed = getopt($shortopts, $longopts);
        if (!$parsed) {
            return false;
        }

        $args = [];
        foreach ($opts as $opt) {
            if (isset($parsed[$opt[0]])) {
                $args[$opt[0]] = $parsed[$opt[0]];
            }
            else if (isset($parsed[$opt[1]])) {
                $args[$opt[0]] = $parsed[$opt[1]];
            }
            else if ($opt[2]) {
                return false;
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

    private function printHelp()
    {
        echo <<<EOD
Usage: 微信支付平台证书下载工具 [-hV] [-c=<wechatpayCertificatePath>]
                    -f=<privateKeyFilePath> -k=<apiV3key> -m=<merchantId>
                    -o=<outputFilePath> -s=<serialNo>
  -m, --mchid=<merchantId>   商户号
  -s, --serialno=<serialNo>  商户证书的序列号
  -f, --privatekey=<privateKeyFilePath>
                             商户的私钥文件
  -k, --key=<apiV3key>       ApiV3Key
  -c, --wechatpay-cert=<wechatpayCertificatePath>
                             微信支付平台证书，验证签名
  -o, --output=<outputFilePath>
                             下载成功后保存证书的路径
  -V, --version              Print version information and exit.
  -h, --help                 Show this help message and exit.

EOD;
    }
}

class NoopValidator implements Validator
{
    public function validate(\Psr\Http\Message\ResponseInterface $response)
    {
        return true;
    }
}


// main
(new CertificateDownloader())->run();
