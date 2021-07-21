# 微信支付 WeChatPay OpenAPI SDK

[A]Sync Chainable WeChatPay v2&v3's OpenAPI SDK for PHP

[![GitHub actions](https://github.com/wechatpay-apiv3/wechatpay-php/workflows/CI/badge.svg)](https://github.com/wechatpay-apiv3/wechatpay-php/actions)
[![Packagist Stars](https://img.shields.io/packagist/stars/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist Downloads](https://img.shields.io/packagist/dm/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist Version](https://img.shields.io/packagist/v/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)
[![Packagist License](https://img.shields.io/packagist/l/wechatpay/wechatpay)](https://packagist.org/packages/wechatpay/wechatpay)

## 概览

微信支付 APIv2&APIv3 的[Guzzle HttpClient](http://docs.guzzlephp.org/)封装组合，
APIv2已内置请求数据签名及`XML`转换器，应答做了数据`签名验签`，转换提供有`WeChatPay\Transformer::toArray`静态方法，按需转换；
APIv3已内置 `请求签名` 和 `应答验签` 两个middleware中间件，创新性地实现了链式面向对象同步/异步调用远程接口。

如果你是使用 `Guzzle` 的商户开发者，可以使用 `WeChatPay\Builder` 工厂方法直接创建一个 `GuzzleHttp\Client` 的链式调用封装器，
实例在执行请求时将自动携带身份认证信息，并检查应答的微信支付签名。


## 项目状态

当前版本为`1.0.6`测试版本。请商户的专业技术人员在使用时注意系统和软件的正确性和兼容性，以及带来的风险。

**版本说明:** `开发版`指: `类库API`随时会变；`测试版`指: 少量`类库API`可能会变；`稳定版`指: `类库API`稳定持续；版本号我们遵循[语义化版本号](https://semver.org/lang/zh-CN/)。

为了向广大开发者提供更好的使用体验，微信支付诚挚邀请您将**使用微信支付 API v3 SDK**中的感受反馈给我们。本问卷可能会占用您不超过2分钟的时间，感谢您的支持。

问卷系统使用的腾讯问卷，您可以点击[这里](https://wj.qq.com/s2/8779987/8dae/)，或者扫描以下二维码参与调查。

[![PHP SDK Questionnaire](https://user-images.githubusercontent.com/1812516/126434257-834ef6ab-e66b-4aa2-9104-8e37d7a14b93.png)](https://wj.qq.com/s2/8779987/8dae/)

## 环境要求

我们开发和测试使用的环境如下：

+ PHP >=7.2
+ guzzlehttp/guzzle ^7.0

**注:** 随`Guzzle7`支持的PHP版本最低为`7.2.5`，另PHP官方已于`30 Nov 2020`停止维护`PHP7.2`，详见附注链接。
## 安装

推荐使用PHP包管理工具`composer`引入SDK到项目中：


### 方式一

在项目目录中，通过composer命令行添加：

```shell
composer require wechatpay/wechatpay
```

### 方式二

在项目的`composer.json`中加入以下配置：

```json
"require": {
    "wechatpay/wechatpay": "^1.0.6"
}
```

添加配置后，执行安装

```shell
composer install
```

## 约定

本类库是以 `OpenAPI` 对应的接入点 `URL.pathname` 以`/`做切分，映射成`segments`，编码书写方式有如下约定：

1. 请求 `pathname` 切分后的每个`segment`，可直接以对象获取形式串接，例如 `v3/pay/transactions/native` 即串成 `v3->pay->transactions->native`;
2. 每个 `pathname` 所支持的 `HTTP METHOD`，即作为被串接对象的末尾执行方法，例如: `v3->pay->transactions->native->post(['json' => []])`;
3. 每个 `pathname` 所支持的 `HTTP METHOD`，同时支持`Async`语法糖，例如: `v3->pay->transactions->native->postAsync(['json' => []])`;
4. 每个 `segment` 有中线(dash)分隔符的，可以使用驼峰`camelCase`风格书写，例如: `merchant-service`可写成 `merchantService`，或如 `{'merchant-service'}`;
5. 每个 `segment` 中，若有`uri_template`动态参数，例如 `business_code/{business_code}` 推荐以`business_code->{'{business_code}'}`形式书写，其格式语义与`pathname`基本一致，阅读起来比较自然;
6. SDK内置以 `v2` 特殊标识为 `APIv2` 的起始 `segmemt`，之后串接切分后的 `segments`，如源 `pay/micropay` 即串成 `v2->pay->micropay->post(['xml' => []])` 即以XML形式请求远端接口；
7. 在IDE集成环境下，也可以按照内置的`chain($segment)`接口规范，直接以`pathname`作为变量`$segment`，来获取`OpenAPI`接入点的`endpoints`串接对象，驱动末尾执行方法(填入对应参数)，发起请求，例如 `chain('v3/pay/transactions/jsapi')->post(['json' => []])`；

以下示例用法，以`异步(Async/PromiseA+)`或`同步(Sync)`结合此种编码模式展开。

> Note of the `segments`: See [RFC3986 #section-3.3](https://www.rfc-editor.org/rfc/rfc3986.html#section-3.3)
> > A path consists of a sequence of path segments separated by a slash ("/") character.
>
> Note of the `uri_template`: See [RFC6570](https://www.rfc-editor.org/rfc/rfc6570.html)

## 开始

首先，通过 `WeChatPay\Builder` 工厂方法构建一个实例，然后如上述`约定`，链式`同步`或`异步`请求远端`OpenAPI`接口。

```php
use WeChatPay\Builder;
use WeChatPay\Util\PemUtil;

// 工厂方法构造一个实例
$instance = Builder::factory([
    // 商户号
    'mchid' => '1000100',
    // 商户证书序列号
    'serial' => 'XXXXXXXXXX',
    // 商户API私钥 PEM格式的文本字符串或者文件resource
    'privateKey' => PemUtil::loadPrivateKey('/path/to/mch/apiclient_key.pem'),
    'certs' => [
        // 可由内置的平台证书下载器 `./bin/CertificateDownloader.php` 生成
        'YYYYYYYYYY' => PemUtil::loadCertificate('/path/to/wechatpay/cert.pem')
    ],
    // APIv2密钥(32字节)--不使用APIv2可选
    'secret' => 'ZZZZZZZZZZ',
    'merchant' => [// --不使用APIv2可选
        // 商户证书 文件路径 --不使用APIv2可选
        'cert' => '/path/to/mch/apiclient_cert.pem',
        // 商户API私钥 文件路径 --不使用APIv2可选
        'key' => '/path/to/mch/apiclient_key.pem',
    ],
]);
```

初始化字典说明如下：

- `mchid` 为你的`商户号`，一般是10字节纯数字
- `serial` 为你的`商户证书序列号`，一般是40字节字符串
- `privateKey` 为你的`商户API私钥`，一般是通过官方证书生成工具生成的文件名是`apiclient_key.pem`文件，支持纯字符串或者文件`resource`格式
- `certs[$serial_number => #resource]` 为通过下载工具下载的平台证书`key/value`键值对，键为`平台证书序列号`，值为`平台证书`pem格式的纯字符串或者文件`resource`格式
- `secret` 为APIv2版的`密钥`，商户平台上设置的32字节字符串
- `merchant[cert => $path]` 为你的`商户证书`,一般是文件名为`apiclient_cert.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为证书密码
- `merchant[key => $path]` 为你的`商户API私钥`，一般是通过官方证书生成工具生成的文件名是`apiclient_key.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为私钥密码

**注：** `APIv3`, `APIv2` 以及 `GuzzleHttp\Client` 的 `$config = []` 初始化参数，均融合在一个型参上; 另外初始化参数说明中的`平台证书下载器`可阅读[使用说明文档](bin/README.md)。

## APIv3

### Native下单

```php
try {
    $resp = $instance->v3->pay->transactions->native->post(['json' => [
        'mchid' => '1900006XXX',
        'out_trade_no' => 'native12177525012014070332333',
        'appid' => 'wxdace645e0bc2cXXX',
        'description' => 'Image形象店-深圳腾大-QQ公仔',
        'notify_url' => 'https://weixin.qq.com/',
        'amount' => [
            'total' => 1,
            'currency' => 'CNY'
        ],
    ]]);

    echo $resp->getStatusCode() . ' ' . $resp->getReasonPhrase(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (Exception $e) {
    // 进行错误处理
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \Psr\Http\Message\ResponseInterface && $e->hasResponse()) {
        echo $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase(), PHP_EOL;
        echo $e->getResponse()->getBody();
    }
}
```

### 查单

```php
$res = $instance->v3->pay->transactions->id->{'{transaction_id}'}
->getAsync([
    // 查询参数结构
    'query' => ['mchid' => '1230000109'],
    // uri_template 字面量参数
    'transaction_id' => '1217752501201407033233368018',
])
->then(static function($response) {
    // 正常逻辑回调处理
    echo $response->getBody()->getContents(), PHP_EOL;
    return $response;
})
->otherwise(static function($exception) {
    // 异常错误处理
    if ($exception instanceof \Psr\Http\Message\ResponseInterface) {
        $body = $exception->getResponse()->getBody();
        echo $body->getContents(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $exception->getTraceAsString(), PHP_EOL;
})
->wait();
```

### 关单

```php
$res = $instance->v3->pay->transactions->outTradeNo->{'{out_trade_no}'}->close
->postAsync([
    // 请求参数结构
    'json' => ['mchid' => '1230000109'],
    // uri_template 字面量参数
    'out_trade_no' => '1217752501201407033233368018',
])
->then(static function($response) {
    // 正常逻辑回调处理
    echo $response->getBody()->getContents(), PHP_EOL;
    return $response;
})
->otherwise(static function($exception) {
    // 异常错误处理
    if ($exception instanceof \Psr\Http\Message\ResponseInterface) {
        $body = $exception->getResponse()->getBody();
        echo $body->getContents(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $exception->getTraceAsString(), PHP_EOL;
})
->wait();
```

### 退款

```php
$res = $instance->chain('v3/refund/domestic/refunds')
->postAsync([
    'json' => [
        'transaction_id' => '1217752501201407033233368018',
        'out_refund_no' => '1217752501201407033233368018',
        'amount' => [
            'refund' => 888,
            'total' => 888,
            'currency' => 'CNY',
        ],
    ],
])
->then(static function($response) {
    // 正常逻辑回调处理
    echo $response->getBody()->getContents(), PHP_EOL;
    return $response;
})
->otherwise(static function($exception) {
    // 异常错误处理
    if ($exception instanceof \Psr\Http\Message\ResponseInterface) {
        $body = $exception->getResponse()->getBody();
        echo $body->getContents(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $exception->getTraceAsString(), PHP_EOL;
})
->wait();
```

### 视频文件上传

```php
// 参考上述指引说明，并引入 `MediaUtil` 正常初始化，无额外条件
use WeChatPay\Util\MediaUtil;
// 实例化一个媒体文件流，注意文件后缀名需符合接口要求
$media = new MediaUtil('/your/file/path/video.mp4');

try {
    $resp = $instance['v3/merchant/media/video_upload']->post([
        'body'    => $media->getStream(),
        'headers' => [
            'content-type' => $media->getContentType(),
        ]
    ]);
    echo $resp->getStatusCode() . ' ' . $resp->getReasonPhrase(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \Psr\Http\Message\ResponseInterface && $e->hasResponse()) {
        echo $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase(), PHP_EOL;
        echo $e->getResponse()->getBody();
    }
}
```

### 图片上传

```php
use WeChatPay\Util\MediaUtil;
$media = new MediaUtil('/your/file/path/image.jpg');
$resp = $instance->v3->marketing->favor->media->imageUpload
->postAsync([
    'body'    => $media->getStream(),
    'headers' => [
        'content-type' => $media->getContentType(),
    ]
])
->then(static function($response) {
    echo $response->getBody()->getContents(), PHP_EOL;
    return $response;
})
->otherwise(static function($exception) {
    if ($exception instanceof \Psr\Http\Message\ResponseInterface) {
        $body = $exception->getResponse()->getBody();
        echo $body->getContents(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $exception->getTraceAsString(), PHP_EOL;
})
->wait();
```

### 敏感信息加/解密

```php
// 参考上上述说明，引入 `WeChatPay\Crypto\Rsa`
use WeChatPay\Crypto\Rsa;
// 加载最新的平台证书
$publicKey = PemUtil::loadCertificate('/path/to/wechatpay/cert.pem');
// 做一个匿名方法，供后续方便使用
$encryptor = function($msg) use ($publicKey) { return Rsa::encrypt($msg, $publicKey); };

// 正常使用Guzzle发起API请求
try {
    // POST 语法糖
    $resp = $instance->chain('v3/applyment4sub/applyment/')->post([
        'json' => [
            'business_code' => 'APL_98761234',
            'contact_info'  => [
                'contact_name'      => $encryptor('value of `contact_name`'),
                'contact_id_number' => $encryptor('value of `contact_id_number'),
                'mobile_phone'      => $encryptor('value of `mobile_phone`'),
                'contact_email'     => $encryptor('value of `contact_email`'),
            ],
            //...
        ],
        'headers' => [
            // 命令行获取证书序列号
            // openssl x509 -in /path/to/wechatpay/cert.pem -noout -serial | awk -F= '{print $2}'
            // 或者使用工具类获取证书序列号 `PemUtil::parseCertificateSerialNo($certificate)`
            'Wechatpay-Serial' => '下载的平台证书序列号',
        ],
    ]);
    echo $resp->getStatusCode() . ' ' . $resp->getReasonPhrase(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \Psr\Http\Message\ResponseInterface && $e->hasResponse()) {
        echo $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase(), PHP_EOL;
        echo $e->getResponse()->getBody();
    }
    return;
}
```

## APIv2

末尾驱动的 `HTTP METHOD(POST)` 方法入参 `array $options`，接受两个自定义参数，释义如下：

- `$options['nonceless']` - 标量 `scalar` 任意值，语义上即，本次请求不用自动添加`nonce_str`参数，推荐 `boolean(True)`
- `$options['security']` - 布尔量`True`，语义上即，本次请求需要加载ssl证书，对应的是初始化 `array $config['merchant']` 结构体

### 企业付款到零钱

```php
use WeChatPay\Transformer;
$res = $instance->v2->mmpaymkttransfers->promotion->transfers
->postAsync([
    'xml' => [
      'appid' => 'wx8888888888888888',
      'mch_id' => '1900000109',
      'partner_trade_no' => '10000098201411111234567890',
      'openid' => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
      'check_name' => 'FORCE_CHECK',
      're_user_name' => '王小王',
      'amount' => 10099,
      'desc' => '理赔',
      'spbill_create_ip' => '192.168.0.1',
    ],
    'security' => true,
    'debug' => true //开启调试模式
])
->then(static function($response) { return Transformer::toArray($response->getBody()->getContents()); })
->otherwise(static function($exception) { return Transformer::toArray($exception->getResponse()->getBody()->getContents()); })
->wait();
print_r($res);
```

## 常见问题

### 如何下载平台证书？

使用内置的[平台证书下载器](bin/README.md) `./bin/CertificateDownloader.php` ，验签逻辑与有`平台证书`请求其他接口一致，即在请求完成后，立即用获得的`平台证书`对返回的消息进行验签，下载器同时开启了 `Guzzle` 的 `debug => true` 参数，方便查询请求/响应消息的基础调试信息。


### 证书和回调解密需要的AesGcm解密在哪里？

请参考[AesGcm.php](src/Crypto/AesGcm.php)。

### 配合swoole使用时，上传文件接口报错

建议升级至swoole 4.6+，swoole在 4.6.0 中增加了native-curl([swoole/swoole-src#3863](https://github.com/swoole/swoole-src/pull/3863))支持，我们测试能正常使用了。
更详细的信息，请参考[#36](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/36)。

## 联系我们

如果你发现了**BUG**或者有任何疑问、建议，请通过issue进行反馈。

也欢迎访问我们的[开发者社区](https://developers.weixin.qq.com/community/pay)。

## 链接

- [GuzzleHttp官方版本支持](https://docs.guzzlephp.org/en/stable/overview.html#requirements)
- [PHP官方版本支持](https://www.php.net/supported-versions.php)
- [变更历史](CHANGELOG.md)

## License

[Apache-2.0 License](LICENSE)
