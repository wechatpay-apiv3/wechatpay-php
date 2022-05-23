# 微信支付 WeChatPay OpenAPI SDK

[A]Sync Chainable WeChatPay v2&v3's OpenAPI SDK for PHP

[![GitHub actions](https://github.com/wechatpay-apiv3/wechatpay-php/workflows/CI/badge.svg)](https://github.com/wechatpay-apiv3/wechatpay-php/actions)
[![Packagist Stars](https://img.shields.io/packagist/stars/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist Downloads](https://img.shields.io/packagist/dm/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist Version](https://img.shields.io/packagist/v/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist License](https://img.shields.io/packagist/l/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)

## 概览

基于 [Guzzle HTTP Client](http://docs.guzzlephp.org/) 的微信支付 PHP 开发库。

### 功能介绍

1. 微信支付 APIv2 和 APIv3 的 Guzzle HTTP 客户端，支持 [同步](#同步请求) 或[异步](#异步请求) 发送请求，并自动进行请求签名和应答验签

1. [链式实现的 URI Template](#链式-uri-template)

1. [敏感信息加解密](#敏感信息加解密)

1. [回调通知](#回调通知)的验签和解密

## 项目状态

当前版本为 `1.4.5` 测试版本。
项目版本遵循 [语义化版本号](https://semver.org/lang/zh-CN/)。
如果你使用的版本 `<=v1.3.2`，升级前请参考 [升级指南](UPGRADING.md)。

## 环境要求

项目支持的环境如下：

+ Guzzle 7.0，PHP >= 7.2.5
+ Guzzle 6.5，PHP >= 7.1.2

项目已支持 PHP 8。我们推荐使用目前处于 [Active Support](https://www.php.net/supported-versions.php) 阶段的 PHP 8.0 和 Guzzle 7。

## 安装

推荐使用 PHP 包管理工具 [Composer](https://getcomposer.org/) 安装 SDK：

```shell
composer require wechatpay/wechatpay
```

## 开始

ℹ️ 以下是 [微信支付 API v3](https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay-1.shtml) 的指引。如果你是 API v2 的使用者，请看 [README_APIv2](README_APIv2.md)。

### 概念

+ **商户 API 证书**，是用来证实商户身份的。证书中包含商户号、证书序列号、证书有效期等信息，由证书授权机构（Certificate Authority ，简称 CA）签发，以防证书被伪造或篡改。详情见 [什么是商户API证书？如何获取商户API证书？](https://kf.qq.com/faq/161222NneAJf161222U7fARv.html) 。

+ **商户 API 私钥**。你申请商户 API 证书时，会生成商户私钥，并保存在本地证书文件夹的文件 apiclient_key.pem 中。为了证明 API 请求是由你发送的，你应使用商户 API 私钥对请求进行签名。

> :warning: 不要把私钥文件暴露在公共场合，如上传到 Github，写在 App 代码中等。

+ **微信支付平台证书**。微信支付平台证书是指：由微信支付负责申请，包含微信支付平台标识、公钥信息的证书。你需使用微信支付平台证书中的公钥验证 API 应答和回调通知的签名。

> ℹ️ 你需要先手工 [下载平台证书](#如何下载平台证书) 才能使用 SDK 发起请求。

+ **证书序列号**。每个证书都有一个由 CA 颁发的唯一编号，即证书序列号。

### 示例程序：微信支付平台证书下载

```php
<?php

require_once('vendor/autoload.php');

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

// 设置参数

// 商户号
$merchantId = '190000****';

// 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
$merchantPrivateKeyFilePath = 'file:///path/to/merchant/apiclient_key.pem';
$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

// 「商户API证书」的「证书序列号」
$merchantCertificateSerial = '3775B6A45ACD588826D15E583A95F5DD********';

// 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
$platformCertificateFilePath = 'file:///path/to/wechatpay/cert.pem';
$platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

// 从「微信支付平台证书」中获取「证书序列号」
$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

// 构造一个 APIv3 客户端实例
$instance = Builder::factory([
    'mchid'      => $merchantId,
    'serial'     => $merchantCertificateSerial,
    'privateKey' => $merchantPrivateKeyInstance,
    'certs'      => [
        $platformCertificateSerial => $platformPublicKeyInstance,
    ],
]);

// 发送请求
$resp = $instance->chain('v3/certificates')->get(
    ['debug' => true] // 调试模式，https://docs.guzzlephp.org/en/stable/request-options.html#debug
);
echo $resp->getBody(), PHP_EOL;
```

## 文档

### 同步请求

使用客户端提供的 `get`、`put`、`post`、`patch` 或 `delete` 方法发送同步请求。以 [Native支付下单](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_1.shtml) 为例。

```php
try {
    $resp = $instance
    ->chain('v3/pay/transactions/native')
    ->post(['json' => [
        'mchid'        => '1900006XXX',
        'out_trade_no' => 'native12177525012014070332333',
        'appid'        => 'wxdace645e0bc2cXXX',
        'description'  => 'Image形象店-深圳腾大-QQ公仔',
        'notify_url'   => 'https://weixin.qq.com/',
        'amount'       => [
            'total'    => 1,
            'currency' => 'CNY'
        ],
    ]]);

    echo $resp->getStatusCode(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (\Exception $e) {
    // 进行错误处理
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        $r = $e->getResponse();
        echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
        echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $e->getTraceAsString(), PHP_EOL;
}
```

请求成功后，你会获得一个 `GuzzleHttp\Psr7\Response` 的应答对象。
阅读 Guzzle 文档 [Using Response](https://docs.guzzlephp.org/en/stable/quickstart.html#using-responses) 进一步了解如何访问应答内的信息。

### 异步请求

使用客户端提供的 `getAsync`、`putAsync`、`postAsync`、`patchAsync` 或 `deleteAsync` 方法发送异步请求。以 [退款](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_9.shtml) 为例。

```php
$promise = $instance
->chain('v3/refund/domestic/refunds')
->postAsync([
    'json' => [
        'transaction_id' => '1217752501201407033233368018',
        'out_refund_no'  => '1217752501201407033233368018',
        'amount'         => [
            'refund'   => 888,
            'total'    => 888,
            'currency' => 'CNY',
        ],
    ],
])
->then(static function($response) {
    // 正常逻辑回调处理
    echo $response->getBody(), PHP_EOL;
    return $response;
})
->otherwise(static function($e) {
    // 异常错误处理
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        $r = $e->getResponse();
        echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
        echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $e->getTraceAsString(), PHP_EOL;
});
// 同步等待
promise->wait();

```

`[get|post|put|patch|delete]Async` 返回的是 [Guzzle Promises](https://github.com/guzzle/promises)。你可以做两件事：

+ 成功时使用 `then()` 处理得到的 `Psr\Http\Message\ResponseInterface`，（可选地）将它传给下一个 `then()`
+ 失败时使用 `otherwise()` 处理异常

最后使用 `wait()` 等待请求执行完成。

### 同步还是异步

对于大部分开发者，我们建议使用同步的模式，因为它更加易于理解。

如果你是具有异步编程基础的开发者，在某些连续调用 API 的场景，将多个操作通过 `then()` 流式串联起来会是一种优雅的实现方式。例如， [以函数链的形式流式下载交易帐单](https://developers.weixin.qq.com/community/pay/article/doc/000ec4521086b85fb81d6472a51013)。

## 链式 URI Template

[URI Template](https://www.rfc-editor.org/rfc/rfc6570.html) 是表达 URI 中变量的一种方式。微信支付 API 使用这种方式表示 URL Path 中的单号或者 ID。

```
# 使用微信支付订单号查询订单
GET /v3/pay/transactions/id/{transaction_id}

# 使用商户订单号查询订单
GET /v3/pay/transactions/out-trade-no/{out_trade_no}
```

使用 [链式](https://en.wikipedia.org/wiki/Method_chaining) URI Template，你能像书写代码一样流畅地书写 URL，轻松地输入路径并传递 URL 参数。配置接口描述包后还能开启 [IDE提示](https://github.com/TheNorthMemory/wechatpay-openapi)。

链式串联的基本单元是 URI Path 中的 [segments](https://www.rfc-editor.org/rfc/rfc3986.html#section-3.3)，`segments` 之间以 `->` 连接。连接的规则如下：

+ 普通 segment
  + 直接书写。例如 `v3->pay->transactions->native`
  + 使用 `chain()`。例如 `chain('v3/pay/transactions/native')`
+ 包含连字号(-)的 segment
  + 使用驼峰 camelCase 风格书写。例如 `merchant-service` 可写成 `merchantService`
  + 使用 `{'foo-bar'}` 方式书写。例如 `{'merchant-service'}`
+ Path 变量。URL 中的 Path 变量应使用这种写法，避免自行组装或者使用 `chain()`，导致大小写处理错误
  + **推荐使用** `_variable_name_` 方式书写，支持 IDE 提示。例如 `v3->pay->transactions->id->_transaction_id_`。
  + 使用 `{'{variable_name}'}` 方式书写。例如 `v3->pay->transactions->id->{'{transaction_id}'}`
+ 请求的 `HTTP METHOD` 作为链式最后的执行方法。例如 `v3->pay->transactions->native->post([ ... ])`
+ Path 变量的值，以同名参数传入执行方法
+ Query 参数，以名为 `query` 的参数传入执行方法

以[查询订单](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_2.shtml) `GET` 方法为例：

```php
$promise = $instance
->v3->pay->transactions->id->_transaction_id_
->getAsync([
    // Query 参数
    'query' => ['mchid' => '1230000109'],
    // 变量名 => 变量值
    'transaction_id' => '1217752501201407033233368018',
]);
```

以 [关闭订单](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_3.shtml) `POST` 方法为例：

```php
$promise = $instance
->v3->pay->transactions->outTradeNo->_out_trade_no_->close
->postAsync([
    // 请求消息
    'json' => ['mchid' => '1230000109'],
    // 变量名 => 变量值
    'out_trade_no' => '1217752501201407033233368018',
]);
```

## 更多例子

### 视频文件上传

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter2_1_2.shtml)

```php
// 参考上述指引说明，并引入 `MediaUtil` 正常初始化，无额外条件
use WeChatPay\Util\MediaUtil;
// 实例化一个媒体文件流，注意文件后缀名需符合接口要求
$media = new MediaUtil('/your/file/path/video.mp4');

$resp = $instance-
>chain('v3/merchant/media/video_upload')
->post([
    'body'    => $media->getStream(),
    'headers' => [
        'content-type' => $media->getContentType(),
    ]
]);
```

### 营销图片上传

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter9_0_1.shtml)

```php
use WeChatPay\Util\MediaUtil;
$media = new MediaUtil('/your/file/path/image.jpg');
$resp = $instance
->v3->marketing->favor->media->imageUpload
->post([
    'body'    => $media->getStream(),
    'headers' => [
        'Content-Type' => $media->getContentType(),
    ]
]);
```

## 敏感信息加/解密

为了保证通信过程中敏感信息字段（如用户的住址、银行卡号、手机号码等）的机密性，

+ 微信支付要求加密上送的敏感信息
+ 微信支付会加密下行的敏感信息

下面以 [特约商户进件](https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter11_1_1.shtml) 为例，演示如何进行 [敏感信息加解密](https://wechatpay-api.gitbook.io/wechatpay-api-v3/qian-ming-zhi-nan-1/min-gan-xin-xi-jia-mi)。

```php
use WeChatPay\Crypto\Rsa;
// 做一个匿名方法，供后续方便使用，$platformPublicKeyInstance 见初始化章节
$encryptor = static function(string $msg) use ($platformPublicKeyInstance): string {
    return Rsa::encrypt($msg, $platformPublicKeyInstance);
};

$resp = $instance
->chain('v3/applyment4sub/applyment/')
->post([
    'json' => [
        'business_code' => 'APL_98761234',
        'contact_info'  => [
            'contact_name'      => $encryptor('张三'),
            'contact_id_number' => $encryptor('110102YYMMDD888X'),
            'mobile_phone'      => $encryptor('13000000000'),
            'contact_email'     => $encryptor('abc123@example.com'),
        ],
        //...
    ],
    'headers' => [
        // $platformCertificateSerial 见初始化章节
        'Wechatpay-Serial' => $platformCertificateSerial,
    ],
]);
```

## 签名

你可以使用 `Rsa::sign()` 计算调起支付时所需参数签名。以 [JSAPI支付](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_4.shtml) 为例。

```php
use WeChatPay\Formatter;
use WeChatPay\Crypto\Rsa;

$merchantPrivateKeyFilePath = 'file:///path/to/merchant/apiclient_key.pem';
$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);

$params = [
    'appId'     => 'wx8888888888888888',
    'timeStamp' => (string)Formatter::timestamp(),
    'nonceStr'  => Formatter::nonce(),
    'package'   => 'prepay_id=wx201410272009395522657a690389285100',
];
$params += ['paySign' => Rsa::sign(
    Formatter::joinedByLineFeed(...array_values($params)),
    $merchantPrivateKeyInstance
), 'signType' => 'RSA'];

echo json_encode($params);
```

## 回调通知

回调通知受限于开发者/商户所使用的`WebServer`有很大差异，这里只给出开发指导步骤，供参考实现。

1. 从请求头部`Headers`，拿到`Wechatpay-Signature`、`Wechatpay-Nonce`、`Wechatpay-Timestamp`、`Wechatpay-Serial`及`Request-ID`，商户侧`Web`解决方案可能有差异，请求头可能大小写不敏感，请根据自身应用来定；
2. 获取请求`body`体的`JSON`纯文本；
3. 检查通知消息头标记的`Wechatpay-Timestamp`偏移量是否在5分钟之内；
4. 调用`SDK`内置方法，[构造验签名串](https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_1.shtml)然后经`Rsa::verfify`验签；
5. 消息体需要解密的，调用`SDK`内置方法解密；
6. 如遇到问题，请拿`Request-ID`点击[这里](https://support.pay.weixin.qq.com/online-service?utm_source=github&utm_medium=wechatpay-php&utm_content=apiv3)，联系官方在线技术支持；

样例代码如下：

```php
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Formatter;

$inWechatpaySignature = '';// 请根据实际情况获取
$inWechatpayTimestamp = '';// 请根据实际情况获取
$inWechatpaySerial = '';// 请根据实际情况获取
$inWechatpayNonce = '';// 请根据实际情况获取
$inBody = '';// 请根据实际情况获取，例如: file_get_contents('php://input');

$apiv3Key = '';// 在商户平台上设置的APIv3密钥

// 根据通知的平台证书序列号，查询本地平台证书文件，
// 假定为 `/path/to/wechatpay/inWechatpaySerial.pem`
$platformPublicKeyInstance = Rsa::from('file:///path/to/wechatpay/inWechatpaySerial.pem', Rsa::KEY_TYPE_PUBLIC);

// 检查通知时间偏移量，允许5分钟之内的偏移
$timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
$verifiedStatus = Rsa::verify(
    // 构造验签名串
    Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
    $inWechatpaySignature,
    $platformPublicKeyInstance
);
if ($timeOffsetStatus && $verifiedStatus) {
    // 转换通知的JSON文本消息为PHP Array数组
    $inBodyArray = (array)json_decode($inBody, true);
    // 使用PHP7的数据解构语法，从Array中解构并赋值变量
    ['resource' => [
        'ciphertext'      => $ciphertext,
        'nonce'           => $nonce,
        'associated_data' => $aad
    ]] = $inBodyArray;
    // 加密文本消息解密
    $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
    // 把解密后的文本转换为PHP Array数组
    $inBodyResourceArray = (array)json_decode($inBodyResource, true);
    // print_r($inBodyResourceArray);// 打印解密后的结果
}
```

## 异常处理

`Guzzle` 默认已提供基础中间件`\GuzzleHttp\Middleware::httpErrors`来处理异常，文档可见[这里](https://docs.guzzlephp.org/en/stable/quickstart.html#exceptions)。
本SDK自`v1.1`对异常处理做了微调，各场景抛送出的异常如下：

+ `HTTP`网络错误，如网络连接超时、DNS解析失败等，送出`\GuzzleHttp\Exception\RequestException`；
+ 服务器端返回了 `5xx HTTP` 状态码，送出`\GuzzleHttp\Exception\ServerException`;
+ 服务器端返回了 `4xx HTTP` 状态码，送出`\GuzzleHttp\Exception\ClientException`;
+ 服务器端返回了 `30x HTTP` 状态码，如超出SDK客户端重定向设置阈值，送出`\GuzzleHttp\Exception\TooManyRedirectsException`;
+ 服务器端返回了 `20x HTTP` 状态码，如SDK客户端逻辑处理失败，例如应答签名验证失败，送出`\GuzzleHttp\Exception\RequestException`；
+ 请求签名准备阶段，`HTTP`请求未发生之前，如PHP环境异常、商户私钥异常等，送出`\UnexpectedValueException`;
+ 初始化时，如把`商户证书序列号`配置成`平台证书序列号`，送出`\InvalidArgumentException`;

以上示例代码，均含有`catch`及`otherwise`错误处理场景示例，测试用例也覆盖了[5xx/4xx/20x异常](tests/ClientDecoratorTest.php)，开发者可参考这些代码逻辑进行错误处理。

## 定制

当默认的本地签名和验签方式不适合你的系统时，你可以通过实现`signer`或者`verifier`中间件来定制签名和验签，比如，你的系统把商户私钥集中存储，业务系统需通过远程调用进行签名。
以下示例用来演示如何替换SDK内置中间件，来实现远程`请求签名`及`结果验签`，供商户参考实现。

```php
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// 假设集中管理服务器接入点为内网`http://192.168.169.170:8080/`地址，并提供两个URI供签名及验签
// - `/wechatpay-merchant-request-signature` 为请求签名
// - `/wechatpay-response-merchant-validation` 为响应验签
$client = new Client(['base_uri' => 'http://192.168.169.170:8080/']);

// 请求参数签名，返回字符串形如`\WeChatPay\Formatter::authorization`返回的字符串
$remoteSigner = function (RequestInterface $request) use ($client, $merchantId): string {
    return (string)$client->post('/wechatpay-merchant-request-signature', ['json' => [
        'mchid' => $merchantId,
        'verb'  => $request->getMethod(),
        'uri'   => $request->getRequestTarget(),
        'body'  => (string)$request->getBody(),
    ]])->getBody();
};

// 返回结果验签，返回可以是4xx,5xx，与远程验签应用约定返回字符串'OK'为验签通过
$remoteVerifier = function (ResponseInterface $response) use ($client, $merchantId): string {
    [$nonce]     = $response->getHeader('Wechatpay-Nonce');
    [$serial]    = $response->getHeader('Wechatpay-Serial');
    [$signature] = $response->getHeader('Wechatpay-Signature');
    [$timestamp] = $response->getHeader('Wechatpay-Timestamp');
    return (string)$client->post('/wechatpay-response-merchant-validation', ['json' => [
        'mchid'     => $merchantId,
        'nonce'     => $nonce,
        'serial'    => $serial,
        'signature' => $signature,
        'timestamp' => $timestamp,
        'body'      => (string)$response->getBody(),
    ]])->getBody();
};

$stack = $instance->getDriver()->select()->getConfig('handler');
// 卸载SDK内置签名中间件
$stack->remove('signer');
// 注册内网远程请求签名中间件
$stack->before('prepare_body', Middleware::mapRequest(
    static function (RequestInterface $request) use ($remoteSigner): RequestInterface {
        return $request->withHeader('Authorization', $remoteSigner($request));
    }
), 'signer');
// 卸载SDK内置验签中间件
$stack->remove('verifier');
// 注册内网远程请求验签中间件
$stack->before('http_errors', static function (callable $handler) use ($remoteVerifier): callable {
    return static function (RequestInterface $request, array $options = []) use ($remoteVerifier, $handler) {
        return $handler($request, $options)->then(
            static function(ResponseInterface $response) use ($remoteVerifier, $request): ResponseInterface {
                $verified = '';
                try {
                    $verified = $remoteVerifier($response);
                } catch (\Throwable $exception) {}
                if ($verified === 'OK') { //远程验签约定，返回字符串`OK`作为验签通过
                    throw new RequestException('签名验签失败', $request, $response, $exception ?? null);
                }
                return $response;
            }
        );
    };
}, 'verifier');

// 链式/同步/异步请求APIv3即可，例如:
$instance->v3->certificates->getAsync()->then(static function($res) { return $res->getBody(); })->wait();
```

## 常见问题

### 如何下载平台证书？

使用内置的[微信支付平台证书下载器](bin/README.md)。

```bash
composer exec CertificateDownloader.php -- -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

微信支付平台证书下载后，下载器会用获得的`平台证书`对返回的消息进行验签。下载器同时开启了 `Guzzle` 的 `debug => true` 参数，方便查询请求/响应消息的基础调试信息。

ℹ️ [什么是APIv3密钥？如何设置？](https://kf.qq.com/faq/180830E36vyQ180830AZFZvu.html)

### 证书和回调解密需要的AesGcm解密在哪里？

请参考[AesGcm.php](src/Crypto/AesGcm.php)，例如内置的`平台证书`下载工具解密代码如下:

```php
AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
```

### 配合swoole使用时，上传文件接口报错

建议升级至swoole 4.6+，swoole在 4.6.0 中增加了native-curl([swoole/swoole-src#3863](https://github.com/swoole/swoole-src/pull/3863))支持，我们测试能正常使用了。
更详细的信息，请参考[#36](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/36)。

### 如何加载公/私钥和证书

`v1.2`提供了统一的加载函数 `Rsa::from($thing, $type)`。

- `Rsa::from($thing, $type)` 支持从文件/字符串加载公/私钥和证书，使用方法可参考 [RsaTest.php](tests/Crypto/RsaTest.php)
- `Rsa::fromPkcs1`是个语法糖，支持加载 `PKCS#1` 格式的公/私钥，入参是 `base64` 字符串
- `Rsa::fromPkcs8`是个语法糖，支持加载 `PKCS#8` 格式的私钥，入参是 `base64` 字符串
- `Rsa::fromSpki`是个语法糖，支持加载 `SPKI` 格式的公钥，入参是 `base64` 字符串
- `Rsa::pkcs1ToSpki`是个 `RSA公钥` 格式转换函数，入参是 `base64` 字符串

### 如何计算商家券发券 API 的签名

使用 `Hash::sign()`计算 APIv2 的签名，示例请参考 APIv2 文档的 [数据签名](README_APIv2.md#数据签名)。

### 为什么 URL 上的变量 OpenID，请求时被替换成小写了？

本 SDK 把 URL 中的大写视为包含连字号的 segment。请求时， `camelCase` 会替换为 `camel-case`。相关 issue 可参考 [#56](https://github.com/wechatpay-apiv3/wechatpay-php/issues/56)、 [#69](https://github.com/wechatpay-apiv3/wechatpay-php/issues/69)。

为了避免大小写错乱，URL 中存在变量时的正确做法是：使用 [链式 URI Template](#%E9%93%BE%E5%BC%8F-uri-template) 的 Path 变量。比如：

- **推荐写法** `->v3->marketing->favor->users->_openid_->coupons->post(['openid' => 'AbcdEF12345'])`
- `->v3->marketing->favor->users->{'{openid}'}->coupons->post(['openid' => 'AbcdEF12345'])`
- `->chain('{+myurl}')->post(['myurl' => 'v3/marketing/favor/users/AbcdEF12345/coupons'])`
- `->{'{+myurl}'}->post(['myurl' => 'v3/marketing/favor/users/AbcdEF12345/coupons'])`

## 联系我们

如果你发现了**BUG**或者有任何疑问、建议，请通过issue进行反馈。

也欢迎访问我们的[开发者社区](https://developers.weixin.qq.com/community/pay)。

## 链接

+ [GuzzleHttp官方版本支持](https://docs.guzzlephp.org/en/stable/overview.html#requirements)
+ [PHP官方版本支持](https://www.php.net/supported-versions.php)
+ [变更历史](CHANGELOG.md)
+ [升级指南](UPGRADING.md)
+ <a name="note-rfc3986"></a> [RFC3986](https://www.rfc-editor.org/rfc/rfc3986.html#section-3.3)
  > section-3.3 `segments`: A path consists of a sequence of path segments separated by a slash ("/") character.
+ <a name="note-rfc6570"><a> [RFC6570](https://www.rfc-editor.org/rfc/rfc6570.html)
+ [PHP密钥/证书参数 相关说明](https://www.php.net/manual/zh/openssl.certparams.php)

## License

[Apache-2.0 License](LICENSE)
