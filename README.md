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

如果你是使用 `Guzzle` 的商户开发者，可以使用 `WeChatPay\Builder::factory` 工厂方法直接创建一个 `GuzzleHttp\Client` 的链式调用封装器，
实例在执行请求时将自动携带身份认证信息，并检查应答的微信支付签名。


## 项目状态

当前版本为`1.2.2`测试版本。
请商户的专业技术人员在使用时注意系统和软件的正确性和兼容性，以及带来的风险。

**版本说明:** `开发版`指: `类库API`随时会变；`测试版`指: 少量`类库API`可能会变；`稳定版`指: `类库API`稳定持续；版本遵循[语义化版本号](https://semver.org/lang/zh-CN/)规则。

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
    "wechatpay/wechatpay": "^1.2.2"
}
```

添加配置后，执行安装

```shell
composer install
```

## 约定

本类库是以 `OpenAPI` 对应的接入点 `URL.pathname` 以`/`做切分，映射成`segments`<sup>[RFC3986](#note-rfc3986)</sup>，编码书写方式有如下约定：

1. 请求 `pathname` 切分后的每个`segment`，可直接以对象获取形式串接，例如 `v3/pay/transactions/native` 即串成 `v3->pay->transactions->native`;
2. 每个 `pathname` 所支持的 `HTTP METHOD`，即作为被串接对象的末尾执行方法，例如: `v3->pay->transactions->native->post(['json' => []])`;
3. 每个 `pathname` 所支持的 `HTTP METHOD`，同时支持`Async`语法糖，例如: `v3->pay->transactions->native->postAsync(['json' => []])`;
4. 每个 `segment` 有中线(dash)分隔符的，可以使用驼峰`camelCase`风格书写，例如: `merchant-service`可写成 `merchantService`，或如 `{'merchant-service'}`;
5. 每个 `segment` 中，若有`uri_template`动态参数<sup>[RFC6570](#note-rfc6570)</sup>，例如 `business_code/{business_code}` 推荐以`business_code->{'{business_code}'}`形式书写，其格式语义与`pathname`基本一致，阅读起来比较自然;
6. SDK内置以 `v2` 特殊标识为 `APIv2` 的起始 `segmemt`，之后串接切分后的 `segments`，如源 `pay/micropay` 即串成 `v2->pay->micropay->post(['xml' => []])` 即以XML形式请求远端接口；
7. 在IDE集成环境下，也可以按照内置的`chain($segment)`接口规范，直接以`pathname`作为变量`$segment`，来获取`OpenAPI`接入点的`endpoints`串接对象，驱动末尾执行方法(填入对应参数)，发起请求，例如 `chain('v3/pay/transactions/jsapi')->post(['json' => []])`；

以下示例用法，以`异步(Async/PromiseA+)`或`同步(Sync)`结合此种编码模式展开。

## 开始

首先，通过 `WeChatPay\Builder::factory` 工厂方法构建一个实例，然后如上述`约定`，链式`同步`或`异步`请求远端`OpenAPI`接口。

```php
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

// 商户号，假定为`1000100`
$merchantId = '1000100';
// 商户私钥，文件路径假定为 `/path/to/merchant/apiclient_key.pem`
$merchantPrivateKeyFilePath = 'file:///path/to/merchant/apiclient_key.pem';// 注意 `file://` 开头协议不能少
// 加载商户私钥
$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
$merchantCertificateSerial = '可以从商户平台直接获取到';// API证书不重置，商户证书序列号就是个常量
// // 也可以使用openssl命令行获取证书序列号
// // openssl x509 -in /path/to/merchant/apiclient_cert.pem -noout -serial | awk -F= '{print $2}'
// // 或者从以下代码也可以直接加载
// // 「商户证书」，文件路径假定为 `/path/to/merchant/apiclient_cert.pem`
// $merchantCertificateFilePath = 'file:///path/to/merchant/apiclient_cert.pem';// 注意 `file://` 开头协议不能少
// // 解析「商户证书」序列号
// $merchantCertificateSerial = PemUtil::parseCertificateSerialNo($merchantCertificateFilePath);

// 「平台证书」，可由下载器 `./bin/CertificateDownloader.php` 生成并假定保存为 `/path/to/wechatpay/cert.pem`
$platformCertificateFilePath = 'file:///path/to/wechatpay/cert.pem';// 注意 `file://` 开头协议不能少
// 加载「平台证书」公钥
$platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
// 解析「平台证书」序列号，「平台证书」当前五年一换，缓存后就是个常量
$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

// 工厂方法构造一个实例
$instance = Builder::factory([
    'mchid'      => $merchantId,
    'serial'     => $merchantCertificateSerial,
    'privateKey' => $merchantPrivateKeyInstance,
    'certs'      => [
        $platformCertificateSerial => $platformPublicKeyInstance,
    ],
    // APIv2密钥(32字节)--不使用APIv2可选
    // 'secret' => 'exposed_your_key_here_have_risks',// 值为占位符，如需使用APIv2请替换为实际值
    // 'merchant' => [// --不使用APIv2可选
    //     // 商户证书 文件路径 --不使用APIv2可选
    //     'cert' => $merchantCertificateFilePath,
    //     // 商户API私钥 文件路径 --不使用APIv2可选
    //     'key' => $merchantPrivateKeyFilePath,
    // ],
]);
```

初始化字典说明如下：

- `mchid` 为你的`商户号`，一般是10字节纯数字
- `serial` 为你的`商户证书序列号`，一般是40字节字符串
- `privateKey` 为你的`商户API私钥`，一般是通过官方证书生成工具生成的文件名是`apiclient_key.pem`文件，支持纯字符串或者文件`resource`格式
- `certs[$serial_number => #resource]` 为通过下载工具下载的`平台证书序列号`及`平台公钥`键值对，键为`平台证书序列号`，值为`平台证书`内置的`平台公钥`，推荐由`Rsa::from`函数加载后的`对象`或`资源`对象
- `secret` 为APIv2版的`密钥`，商户平台上设置的32字节字符串
- `merchant[cert => $path]` 为你的`商户证书`,一般是文件名为`apiclient_cert.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为证书密码
- `merchant[key => $path]` 为你的`商户API私钥`，一般是通过官方证书生成工具生成的文件名是`apiclient_key.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为私钥密码

**注：** `APIv3`, `APIv2` 以及 `GuzzleHttp\Client` 的 `$config = []` 初始化参数，均融合在一个型参上; 另外初始化参数说明中的`平台证书下载器`可阅读[使用说明文档](bin/README.md)。

## APIv3

### Native下单

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_1.shtml)

```php
try {
    $resp = $instance
    ->v3->pay->transactions->native
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

### 查单

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_2.shtml)

```php
$res = $instance
->v3->pay->transactions->id->{'{transaction_id}'}
->getAsync([
    // 查询参数结构
    'query' => ['mchid' => '1230000109'],
    // uri_template 字面量参数
    'transaction_id' => '1217752501201407033233368018',
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
})
->wait();
```

### 关单

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_3.shtml)

```php
$res = $instance
->v3->pay->transactions->outTradeNo->{'{out_trade_no}'}->close
->postAsync([
    // 请求参数结构
    'json' => ['mchid' => '1230000109'],
    // uri_template 字面量参数
    'out_trade_no' => '1217752501201407033233368018',
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
})
->wait();
```

### 退款

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_9.shtml)

```php
$res = $instance
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
})
->wait();
```

### 视频文件上传

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter2_1_2.shtml)

```php
// 参考上述指引说明，并引入 `MediaUtil` 正常初始化，无额外条件
use WeChatPay\Util\MediaUtil;
// 实例化一个媒体文件流，注意文件后缀名需符合接口要求
$media = new MediaUtil('/your/file/path/video.mp4');

try {
    $resp = $instance['v3/merchant/media/video_upload']
    ->post([
        'body'    => $media->getStream(),
        'headers' => [
            'content-type' => $media->getContentType(),
        ]
    ]);
    echo $resp->getStatusCode(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (\Exception $e) {
    // 异常错误处理
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        $r = $e->getResponse();
        echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
        echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $e->getTraceAsString(), PHP_EOL;
}
```

### 营销图片上传

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter9_0_1.shtml)

```php
use WeChatPay\Util\MediaUtil;
$media = new MediaUtil('/your/file/path/image.jpg');
$resp = $instance
->v3->marketing->favor->media->imageUpload
->postAsync([
    'body'    => $media->getStream(),
    'headers' => [
        'Content-Type' => $media->getContentType(),
    ]
])
->then(static function($response) {
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
})
->wait();
```

### 敏感信息加/解密

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter11_1_1.shtml)

```php
// 参考上上述说明，引入 `WeChatPay\Crypto\Rsa`
use WeChatPay\Crypto\Rsa;
// 做一个匿名方法，供后续方便使用，$platformCertificateInstance 见初始化章节
$encryptor = static function(string $msg) use ($platformCertificateInstance): string {
    return Rsa::encrypt($msg, $platformCertificateInstance);
};

try {
    $resp = $instance
    ->chain('v3/applyment4sub/applyment/')
    ->post([
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
            // $platformCertificateSerial 见初始化章节
            'Wechatpay-Serial' => $platformCertificateSerial,
        ],
    ]);
    echo $resp->getStatusCode(), PHP_EOL;
    echo $resp->getBody(), PHP_EOL;
} catch (\Exception $e) {
    // 异常错误处理
    echo $e->getMessage(), PHP_EOL;
    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        $r = $e->getResponse();
        echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
        echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
    }
    echo $e->getTraceAsString(), PHP_EOL;
}
```

## APIv2

本类库可单独用于`APIv2`的开发，希望能给商户提供一个过渡，可先平滑迁移至本类库以承接`APIv2`对接，然后再按需替换升级至`APIv3`上。
以下代码以单独使用展开示例，供商户参考。

**提醒：** 本SDK在调用`APIv2`接口时， *特意在错误通道(E_USER_DEPRECATED)* 打出提示 `\WeChatPay\Exception\DEP_XML_PROTOCOL_IS_REACHABLE_EOL` :

**New features are all in `APIv3`, there's no reason to continue use this kind client since v2.0.**

**新功能均已在`APIv3`接口服务上，已没有理由继续使用`APIv2`接口服务了，本SDK将在v2.0版移除对`APIv2`的默认支持。**

商户在平滑迁移时，务必调整`php.ini`的`display_errors=Off`或者`error_reporting`错误级别，来防止把这条**提醒**信息打送至前台业务系统。

**注:** `v1.2.2`版调整了上述提示，直至`APIv2`生命周期结束，不再强提示。

### 初始化

```php
use WeChatPay\Builder;

// 商户号，假定为`1000100`
$merchantId = '1000100';
// APIv2密钥(32字节) 假定为`exposed_your_key_here_have_risks`，使用请替换为实际值
$apiv2Key = 'exposed_your_key_here_have_risks';
// 商户私钥，文件路径假定为 `/path/to/merchant/apiclient_key.pem`
$merchantPrivateKeyFilePath = '/path/to/merchant/apiclient_key.pem';
// 商户证书，文件路径假定为 `/path/to/merchant/apiclient_cert.pem`
$merchantCertificateFilePath = '/path/to/merchant/apiclient_cert.pem';

// 工厂方法构造一个实例
$instance = Builder::factory([
    'mchid'      => $merchantId,
    'serial'     => 'nop',
    'privateKey' => 'any',
    'certs'      => ['any' => null],
    'secret'     => $apiv2Key,
    'merchant' => [
        'cert' => $merchantCertificateFilePath,
        'key'  => $merchantPrivateKeyFilePath,
    ],
]);
```

初始化字典说明如下：

- `mchid` 为你的`商户号`，一般是10字节纯数字
- `serial` 为你的`商户证书序列号`，不使用APIv3可填任意值
- `privateKey` 为你的`商户API私钥`，不使用APIv3可填任意值
- `certs[$serial_number => #resource]` 不使用APIv3可填任意值, `$serial_number` 注意不要与商户证书序列号`serial`相同
- `secret` 为APIv2版的`密钥`，商户平台上设置的32字节字符串
- `merchant[cert => $path]` 为你的`商户证书`,一般是文件名为`apiclient_cert.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为证书密码
- `merchant[key => $path]` 为你的`商户API私钥`，一般是通过官方证书生成工具生成的文件名是`apiclient_key.pem`文件路径，接受`[$path, $passphrase]` 格式，其中`$passphrase`为私钥密码

**注：** `APIv3`, `APIv2` 以及 `GuzzleHttp\Client` 的 `$config = []` 初始化参数，均融合在一个型参上。

### 企业付款到零钱

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2)

```php
use WeChatPay\Transformer;
$res = $instance
->v2->mmpaymkttransfers->promotion->transfers
->postAsync([
    'xml' => [
      'mch_appid'        => 'wx8888888888888888',
      'mchid'            => '1900000109',// 注意这个商户号，key是`mchid`非`mch_id`
      'partner_trade_no' => '10000098201411111234567890',
      'openid'           => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
      'check_name'       => 'FORCE_CHECK',
      're_user_name'     => '王小王',
      'amount'           => 10099,
      'desc'             => '理赔',
      'spbill_create_ip' => '192.168.0.1',
    ],
    'security' => true, //请求需要双向证书
    'debug' => true //开启调试模式
])
->then(static function($response) {
    return Transformer::toArray((string)$response->getBody());
})
->otherwise(static function($e) {
    if ($e instanceof \GuzzleHttp\Promise\RejectionException) {
        return Transformer::toArray((string)$e->getReason()->getBody());
    }
    return [];
})
->wait();
print_r($res);
```

`APIv2`末尾驱动的 `HTTP METHOD(POST)` 方法入参 `array $options`，可接受类库定义的两个参数，释义如下：

- `$options['nonceless']` - 标量 `scalar` 任意值，语义上即，本次请求不用自动添加`nonce_str`参数，推荐 `boolean(True)`
- `$options['security']` - 布尔量`True`，语义上即，本次请求需要加载ssl证书，对应的是初始化 `array $config['merchant']` 结构体

### 企业付款到银行卡-获取RSA公钥

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay_yhk.php?chapter=24_7&index=4)

```php
use WeChatPay\Transformer;
$res = $instance
->v2->risk->getpublickey
->postAsync([
    'xml' => [
        'mch_id' => '1900000109',
        'sign_type' => 'MD5',
    ],
    'security' => true, //请求需要双向证书
    // 特殊接入点，仅对本次请求有效
    'base_uri' => 'https://fraud.mch.weixin.qq.com/',
])
// 返回无sign字典，只能从异常通道获取返回值
->otherwise(static function($e) {
    if ($e instanceof \GuzzleHttp\Promise\RejectionException) {
        return Transformer::toArray((string)$e->getReason()->getBody());
    }
    return [];
})
->wait();
print_r($res);
```

### 付款到银行卡

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay_yhk.php?chapter=24_2)

```php
use WeChatPay\Transformer;
use WeChatPay\Crypto\Rsa;
// 做一个匿名方法，供后续方便使用，$rsaPubKeyString 是`risk/getpublickey` 的返回值'pub_key'字符串
$rsaPublicKeyInstance = Rsa::from($rsaPubKeyString, Rsa::KEY_TYPE_PUBLIC);
$encryptor = static function(string $msg) use ($rsaPublicKeyInstance): string {
    return Rsa::encrypt($msg, $rsaPublicKeyInstance);
};
$res = $instance
->v2->mmpaysptrans->pay_bank
->postAsync([
    'xml' => [
        'mch_id'           => '1900000109',
        'partner_trade_no' => '1212121221227',
        'enc_bank_no'      => $encryptor('6225............'),
        'enc_true_name'    => $encryptor('张三'),
        'bank_code'        => '1001',
        'amount'           => '100000',
        'desc'             => '理财',
    ],
    'security' => true, //请求需要双向证书
])
->then(static function($response) {
    return Transformer::toArray((string)$response->getBody());
})
->otherwise(static function($e) {
    if ($e instanceof \GuzzleHttp\Promise\RejectionException) {
        return Transformer::toArray((string)$e->getReason()->getBody());
    }
    return [];
})
->wait();
print_r($res);
```

### 刷脸支付-人脸识别-获取调用凭证

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/wxfacepay/develop/android/faceuser.html)

```php
use WeChatPay\Formatter;
use WeChatPay\Transformer;

$res = $instance
->v2->face->get_wxpayface_authinfo
->postAsync([
    'xml' => [
        'store_id'   => '1234567',
        'store_name' => '云店(广州白云机场店)',
        'device_id'  => 'abcdef',
        'rawdata'    => '从客户端`getWxpayfaceRawdata`方法取得的数据',
        'appid'      => 'wx8888888888888888',
        'mch_id'     => '1900000109',
        'now'        => (string)Formatter::timestamp(),
        'version'    => '1',
        'sign_type'  => 'HMAC-SHA256',
    ],
    // 特殊接入点，仅对本次请求有效
    'base_uri' => 'https://payapp.weixin.qq.com/',
])
->then(static function($response) {
    return Transformer::toArray((string)$response->getBody());
})
->otherwise(static function($e) {
    if ($e instanceof \GuzzleHttp\Promise\RejectionException) {
        return Transformer::toArray((string)$e->getReason()->getBody());
    }
    return [];
})
->wait();
print_r($res);
```

### v2沙箱环境-获取验签密钥API

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=23_1&index=2)

```php
use WeChatPay\Transformer;
$res = $instance
->v2->sandboxnew->pay->getsignkey
->postAsync([
    'xml' => [
        'mch_id' => '1900000109',
    ],
    // 通知SDK不接受沙箱环境重定向，仅对本次请求有效
    'allow_redirects' => false,
])
// 返回无sign字典，只能从异常通道获取返回值
->otherwise(static function($e) {
    if ($e instanceof \GuzzleHttp\Promise\RejectionException) {
        return Transformer::toArray((string)$e->getReason()->getBody());
    }
    return [];
})
->wait();
print_r($res);
```

### v2通知应答

```php
use WeChatPay\Transformer;

$xml = Transformer::toXml([
  'return_code' => 'SUCCESS',
  'return_msg' => 'OK',
]);

echo $xml;
```

## 数据签名

### APIv3小程序/JSAPI调起支付数据签名

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_4.shtml)

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

### 商家券-小程序发券APIv2密钥签名

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter9_3_1.shtml)

```php
use WeChatPay\Formatter;
use WeChatPay\Crypto\Hash;

$apiv2Key = 'exposed_your_key_here_have_risks';

$busiFavorFlat = static function (array $params): array {
    $result = ['send_coupon_merchant' => $params['send_coupon_merchant']];
    foreach ($params['send_coupon_params'] as $index => $item) {
        foreach ($item as $key => $value) {
            $result["{$key}{$index}"] = $value;
        }
    }
    return $result;
};

// 发券小程序所需数据结构
$busiFavor = [
    'send_coupon_params' => [
        ['out_request_no' => '1234567', 'stock_id' => 'abc123'],
        ['out_request_no' => '7654321', 'stock_id' => '321cba'],
    ],
    'send_coupon_merchant' => '10016226'
];

$busiFavor += ['sign' => Hash::sign(
    Hash::ALGO_HMAC_SHA256,
    Formatter::queryStringLike(Formatter::ksort($busiFavorFlat($busiFavor))),
    $apiv2Key
)];

echo json_encode($params);
```

### 商家券-H5发券APIv2密钥签名

[官方开发文档地址](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter9_4_1.shtml)

```php
use WeChatPay\Formatter;
use WeChatPay\Crypto\Hash;

$apiv2Key = 'exposed_your_key_here_have_risks';

$params = [
  'stock_id'             => '12111100000001',
  'out_request_no'       => '20191204550002',
  'send_coupon_merchant' => '10016226',
  'open_id'              => 'oVvBvwEurkeUJpBzX90-6MfCHbec',
  'coupon_code'          => '75345199',
];

$params += ['sign' => Hash::sign(
    Hash::ALGO_HMAC_SHA256,
    Formatter::queryStringLike(Formatter::ksort($params)),
    $apiv2Key
)];

echo json_encode($params);
```

## 回调通知

回调通知受限于开发者/商户所使用的`WebServer`有很大差异，这里只给出开发指导步骤，供参考实现。

### APIv3回调通知

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
    $inBodyArray = (array)json_decode($inBody, true);
    ['resource' => [
        'ciphertext'      => $ciphertext,
        'nonce'           => $nonce,
        'associated_data' => $aad
    ]] = $inBodyArray;
    $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
    $inBodyResourceArray = (array)json_decode($inBodyResource, true);
    // print_r($inBodyResourceArray);// 打印解密后的结果
}
```

### APIv2回调通知

1. 从请求头`Headers`获取`Request-ID`，商户侧`Web`解决方案可能有差异，请求头的`Request-ID`可能大小写不敏感，请根据自身应用来定；
2. 获取请求`body`体的`XML`纯文本；
3. 调用`SDK`内置方法，根据[签名算法](https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_3)做本地数据签名计算，然后与通知文本的`sign`做`Hash::equals`对比验签；
4. 消息体需要解密的，调用`SDK`内置方法解密；
5. 如遇到问题，请拿`Request-ID`点击[这里](https://support.pay.weixin.qq.com/online-service?utm_source=github&utm_medium=wechatpay-php&utm_content=apiv2)，联系官方在线技术支持；

样例代码如下：

```php
use WeChatPay\Transformer;
use WeChatPay\Crypto\Hash;
use WeChatPay\Crypto\AesEcb;
use WeChatPay\Formatter;

$inBody = '';// 请根据实际情况获取，例如: file_get_contents('php://input');

$apiv2Key = '';// 在商户平台上设置的APIv2密钥

$inBodyArray = Transformer::toArray($inBody);

// 部分通知体无`sign_type`，部分`sign_type`默认为`MD5`，部分`sign_type`默认为`HMAC-SHA256`
// 部分通知无`sign`字典
// 请根据官方开发文档确定
['sign_type' => $signType, 'sign' => $sign] = $inBodyArray;

$calculated = Hash::sign(
    $signType ?? Hash::ALGO_MD5,// 如没获取到`sign_type`，假定默认为`MD5`
    Formatter::queryStringLike(Formatter::ksort($inBodyArray)),
    $apiv2Key
);

$signatureStatus = Hash::equals($calculated, $sign);

if ($signatureStatus) {
    // 如需要解密的
    ['req_info' => $reqInfo] = $inBodyArray;
    $inBodyReqInfoXml = AesEcb::decrypt($reqInfo, Hash::md5($apiv2Key));
    $inBodyReqInfoArray = Transformer::toArray($inBodyReqInfoXml);
    // print_r($inBodyReqInfoArray);// 打印解密后的结果
}
```

## 异常处理

`Guzzle` 默认已提供基础中间件`\GuzzleHttp\Middleware::httpErrors`来处理异常，文档可见[这里](https://docs.guzzlephp.org/en/stable/quickstart.html#exceptions)。
本SDK自`v1.1`对异常处理做了微调，各场景抛送出的异常如下：

- `HTTP`网络错误，如网络连接超时、DNS解析失败等，送出`\GuzzleHttp\Exception\RequestException`；
- 服务器端返回了 `5xx HTTP` 状态码，送出`\GuzzleHttp\Exception\ServerException`;
- 服务器端返回了 `4xx HTTP` 状态码，送出`\GuzzleHttp\Exception\ClientException`;
- 服务器端返回了 `30x HTTP` 状态码，如超出SDK客户端重定向设置阈值，送出`\GuzzleHttp\Exception\TooManyRedirectsException`;
- 服务器端返回了 `20x HTTP` 状态码，如SDK客户端逻辑处理失败，例如应答签名验证失败，送出`\GuzzleHttp\Exception\RequestException`；
- 请求签名准备阶段，`HTTP`请求未发生之前，如PHP环境异常、商户私钥异常等，送出`\UnexpectedValueException`;
- 初始化时，如把`商户证书序列号`配置成`平台证书序列号`，送出`\InvalidArgumentException`;
- `APIv2`上的异常，返回值无签可验及验签失败均送出`\GuzzleHttp\Promise\RejectionException`;

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

使用内置的[平台证书下载器](bin/README.md) `./bin/CertificateDownloader.php` ，验签逻辑与有`平台证书`请求其他接口一致，即在请求完成后，立即用获得的`平台证书`对返回的消息进行验签，下载器同时开启了 `Guzzle` 的 `debug => true` 参数，方便查询请求/响应消息的基础调试信息。


### 证书和回调解密需要的AesGcm解密在哪里？

请参考[AesGcm.php](src/Crypto/AesGcm.php)，例如内置的`平台证书`下载工具解密代码如下:

```php
AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
```

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
- [升级指南](UPGRADING.md)
- <a name="note-rfc3986"></a> [RFC3986](https://www.rfc-editor.org/rfc/rfc3986.html#section-3.3)
  > section-3.3 `segments`: A path consists of a sequence of path segments separated by a slash ("/") character.
- <a name="note-rfc6570"><a> [RFC6570](https://www.rfc-editor.org/rfc/rfc6570.html)
- [PHP密钥/证书参数 相关说明](https://www.php.net/manual/zh/openssl.certparams.php)

## License

[Apache-2.0 License](LICENSE)
