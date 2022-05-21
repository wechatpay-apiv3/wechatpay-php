# API v2

本类库可单独用于`APIv2`的开发，希望能给商户提供一个过渡，可先平滑迁移至本类库以承接`APIv2`对接，然后再按需替换升级至`APIv3`上。

以下代码以单独使用展开示例，供商户参考。关于链式 `->`，请先阅读 [链式 URI Template](README.md#链式-uri-template)。

## V2初始化

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

## 企业付款到零钱

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
      'amount'           => '10099',
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
    // 更多`$e`异常类型判断是必须的，这里仅列出一种可能情况，请根据实际对接过程调整并增加
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

## 企业付款到银行卡-获取RSA公钥

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
->then(static function($response) {
    return Transformer::toArray((string)$response->getBody());
})
->wait();
print_r($res);
```

## 付款到银行卡

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
->wait();
print_r($res);
```

## 刷脸支付-人脸识别-获取调用凭证

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
->wait();
print_r($res);
```

## v2沙箱环境-获取验签密钥API

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
->then(static function($response) {
    return Transformer::toArray((string)$response->getBody());
})
->wait();
print_r($res);
```

## v2通知应答

```php
use WeChatPay\Transformer;

$xml = Transformer::toXml([
  'return_code' => 'SUCCESS',
  'return_msg' => 'OK',
]);

echo $xml;
```

## 数据签名

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

echo json_encode($busiFavor);
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

## v2回调通知

回调通知受限于开发者/商户所使用的`WebServer`有很大差异，这里只给出开发指导步骤，供参考实现。

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
