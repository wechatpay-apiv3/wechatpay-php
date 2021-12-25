# 升级指南

## 从 1.3 升级至 1.4

v1.4版，对`Guzzle6`提供了**有限**兼容支持，最低可兼容至**v6.5.0**，原因是测试依赖的前向兼容`GuzzleHttp\Handler\MockHandler::reset()`方法，在这个版本上才可用，相关见 [Guzzle#2143](https://github.com/guzzle/guzzle/pull/2143)；

`Guzzle6`的PHP版本要求是 **>=5.5**，而本类库前向兼容时，读取RSA证书序列号用到了PHP的[#7151 serialNumberHex support](http://bugs.php.net/71519)功能，顾PHP的最低版本可降级至**7.1.2**这个版本；

为**有限**兼容**Guzzle6**，类库放弃使用`Guzzle7`上的`\GuzzleHttp\Utils::jsonEncode`及`\GuzzleHttp\Utils::jsonDecode`封装方法，取而代之为PHP原生`json_encode`/`json_decode`方法，极端情况下(`meta`数据非法)可能会在`APIv3媒体文件上传`的几个接口上，本该抛送客户端异常而代之为返回服务端异常；这种场景下，会对调试带来部分困难，评估下来可控，遂放弃使用`\GuzzleHttp\Utils`的封装，待`Guzzle6 EOL`时，再择机回滚至使用这两个封装方法。

**警告**：PHP7.1已于*1 Dec 2019*完成其**PHP官方支持**生命周期，本类库在PHP7.1环境上也仅有限支持可用，请**商户/开发者**自行评估继续使用PHP7.1的风险。

同时，测试用例依赖的`PHPUnit8`调整最低版本至**v8.5.16**，原因是本类库的前向用例覆盖用到了`TestCase::expectError`方法，其在PHP7.4/8.0上有[bug #4663](https://github.com/sebastianbergmann/phpunit/issues/4663)，顾调整至这个版本。

Guzzle7+PHP7.2/7.3/7.4/8.0环境下，本次版本升级不受影响。

## 从 1.2 升级到 1.3

v1.3主要更新内容是为IDE增加`接口`及`参数`描述提示，以单独的安装包发行，建议仅在`composer --dev`即(`Add requirement to require-dev.`)，生产运行时环境完全无需。

## 从 1.1 升级至 1.2

v1.2 对 `RSA公/私钥`加载做了加强，释放出 `Rsa::from` 统一加载函数，以接替`PemUtil::loadPrivateKey`，同时释放出`Rsa::fromPkcs1`, `Rsa::fromPkcs8`, `Rsa::fromSpki`及`Rsa::pkcs1ToSpki`方法，在不丢失精度的前提下，支持`不落盘`从云端（如`公/私钥`存储在数据库/NoSQL等媒介中）加载。

- `Rsa::from` 支持从文件/字符串/完整RSA公私钥字符串/X509证书加载，对应的测试用例覆盖见[这里](tests/Crypto/RsaTest.php);
- `Rsa::fromPkcs1`是个语法糖，支持加载`PKCS#1`格式的公/私钥，入参是`base64`字符串；
- `Rsa::fromPkcs8`是个语法糖，支持加载`PKCS#8`格式的私钥，入参是`base64`字符串；
- `Rsa::fromSpki`是个语法糖，支持加载`SPKI`格式的公钥，入参是`base64`字符串；
- `Rsa::pkcs1ToSpki`是个`RSA公钥`格式转换函数，入参是`base64`字符串；

特别地，对于`APIv2` 付款到银行卡功能，现在可直接支持`加密敏感信息`了，即从[获取RSA加密公钥](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay_yhk.php?chapter=24_7&index=4)接口获取的`pub_key`字符串，经`Rsa::from($pub_key, Rsa::KEY_TYPE_PUBLIC)`加载，用于`Rsa::encrypt`加密，详细用法见README示例；

标记 `PemUtil::loadPrivateKey`及`PemUtil::loadPrivateKeyFromString`为`不推荐用法`，当前向下兼容v1.1及v1.0版本用法，预期在v2.0大版本上会移除这两个方法；

推荐升级加载`RSA公/私钥`为以下形式：

从文件加载「商户RSA私钥」，变化如下：

```diff
+use WeChatPay\Crypto\Rsa;

-$merchantPrivateKeyFilePath = '/path/to/merchant/apiclient_key.pem';
-$merchantPrivateKeyInstance = PemUtil::loadPrivateKey($merchantPrivateKeyFilePath);
+$merchantPrivateKeyFilePath = 'file:///path/to/merchant/apiclient_key.pem';// 注意 `file://` 开头协议不能少
+$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
```

从文件加载「平台证书」，变化如下：

```diff
-$platformCertificateFilePath = '/path/to/wechatpay/cert.pem';
-$platformCertificateInstance = PemUtil::loadCertificate($platformCertificateFilePath);
-// 解析平台证书序列号
-$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateInstance);
+$platformCertificateFilePath = 'file:///path/to/wechatpay/cert.pem';// 注意 `file://` 开头协议不能少
+$platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
+// 解析「平台证书」序列号，「平台证书」当前五年一换，缓存后就是个常量
+$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
```

相对应地初始化工厂方法，平台证书相关入参初始化变化如下：

```diff
     'certs'      => [
-        $platformCertificateSerial => $platformCertificateInstance,
+        $platformCertificateSerial => $platformPublicKeyInstance,
     ],
```

APIv3相关「RSA数据签名」，变化如下：

```diff
-use WeChatPay\Util\PemUtil;
-$merchantPrivateKeyFilePath = '/path/to/merchant/apiclient_key.pem';
-$merchantPrivateKeyInstance = PemUtil::loadPrivateKey($merchantPrivateKeyFilePath);
+$merchantPrivateKeyFilePath = 'file:///path/to/merchant/apiclient_key.pem';
+$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);
```

APIv3回调通知「验签」，变化如下：

```diff
-use WeChatPay\Util\PemUtil;
 // 根据通知的平台证书序列号，查询本地平台证书文件，
 // 假定为 `/path/to/wechatpay/inWechatpaySerial.pem`
-$certInstance = PemUtil::loadCertificate('/path/to/wechatpay/inWechatpaySerial.pem');
+$platformPublicKeyInstance = Rsa::from('file:///path/to/wechatpay/inWechatpaySerial.pem', Rsa::KEY_TYPE_PUBLIC);

 // 检查通知时间偏移量，允许5分钟之内的偏移
 $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
 $verifiedStatus = Rsa::verify(
     // 构造验签名串
     Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
     $inWechatpaySignature,
-    $certInstance
+    $platformPublicKeyInstance
 );
```

更高级的加载`RSA公/私钥`方式，如从`Rsa::fromPkcs1`， `Rsa::fromPkcs8`, `Rsa::fromSpki`等语法糖加载，可查询参考测试用例[RsaTest.php](tests/Crypto/RsaTest.php)做法，请按需自行拓展使用。

## 从 1.0 升级至 1.1

v1.1 版本对内部中间件实现做了微调，对`APIv3的异常`做了部分调整，调整内容如下：

1. 对中间件栈顺序，做了微调，从原先的栈顶调整至必要位置，即：
    1. 请求签名中间件 `signer` 从栈顶调整至 `prepare_body` 之前，`请求签名`仅须发生在请求发送体准备阶段之前，这个顺序调整对应用端无感知;
    2. 返回验签中间件 `verifier` 从栈顶调整至 `http_errors` 之前(默认实际仍旧在栈顶)，对异常(HTTP 4XX, 5XX)返回交由`Guzzle`内置的`\GuzzleHttp\Middleware::httpErrors`进行处理，`返回验签`仅对正常(HTTP 20X)结果验签；
2. 重构了 `verifier` 实现，调整内容如下：
    1. 异常类型从 `\UnexpectedValueException` 调整成 `\GuzzleHttp\Exception\RequestException`；因由是，请求/响应已经完成，响应内容有(HTTP 20X)结果，调整后，SDK客户端异常时，可以从`RequestException::getResponse()`获取到这个响应对象，进而可甄别出`返回体`具体内容；
    2. 正常响应结果在验签时，有可能从 `\WeChatPay\Crypto\Rsa::verify` 内部抛出`UnexpectedValueException`异常，调整后，一并把这个异常交由`RequestException`抛出，应用侧可以从`RequestException::getPrevious()`获取到这个异常实例；

以上调整，对于正常业务逻辑(HTTP 20X)无影响，对于应用侧异常捕获，需要做如下适配调整：

同步模型，建议从捕获`UnexpectedValueException`调整为`\GuzzleHttp\Exception\RequestException`，如下：

```diff
 try {
     $instance
     ->v3->pay->transactions->native
     ->post(['json' => []]);
- } catch (\UnexpectedValueException $e) {
+ } catch (\GuzzleHttp\Exception\RequestException $e) {
    // do something
 }
```

异步模型，建议始终判断当前异常是否实例于`\GuzzleHttp\Exception\RequestException`，判断方法见[README](README.md)示例代码。

## 从 wechatpay-guzzle-middleware 0.2 迁移至 1.0

如 [变更历史](CHANGELOG.md) 所述，本类库自1.0不兼容`wechatpay/wechatpay-guzzle-middleware:~0.2`，原因如下：

1. 升级`Guzzle`大版本至`7`, `Guzzle7`做了许多不兼容更新，相关讨论可见[Laravel8依赖变更](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/54)；`Guzzle7`要求PHP最低版本为`7.2.5`，重要特性是加入了`函数参数类型签名`以及`函数返回值类型签名`功能，从开发语言层面，使类库健壮性有了显著提升；
2. 重构并修正了原[敏感信息加解密](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/25)过度设计问题；
3. 重新设计了类库函数及方案，以提供[回调通知签名](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/42)所需方法；
4. 调整`composer.json`移动`guzzlehttp/guzzle`从`require-dev`弱依赖至`require`强依赖，开发者无须再手动添加；
5. 缩减初始化手动拼接客户端参数至`Builder::factory`，统一由SDK来构建客户端；
6. 新增链式调用封装器，原生提供对`APIv3`的链式调用；
7. 新增`APIv2`支持，推荐商户可以先升级至本类库支持的`APIv2`能力，然后再按需升级至相对应的`APIv3`能力；
8. 增加类库单元测试覆盖`Linux`,`macOS`及`Windows`运行时；
9. 调整命名空间`namespace`为`WeChatPay`;

### 迁移指南

PHP版本最低要求为`7.2.5`，请商户的技术开发人员**先评估**运行时环境是否支持**再决定**按如下步骤迁移。
### composer.json 调整

依赖调整

```diff
     "require": {
-         "guzzlehttp/guzzle": "^6.3",
-         "wechatpay/wechatpay-guzzle-middleware": "^0.2.0"
+         "wechatpay/wechatpay": "^1.0"
     }
```

### 初始化方法调整

```diff
 use GuzzleHttp\Exception\RequestException;
- use WechatPay\GuzzleMiddleware\WechatPayMiddleware;
+ use WeChatPay\Builder;
- use WechatPay\GuzzleMiddleware\Util\PemUtil;
+ use WeChatPay\Util\PemUtil;

 $merchantId = '1000100';
 $merchantSerialNumber = 'XXXXXXXXXX';
 $merchantPrivateKey = PemUtil::loadPrivateKey('/path/to/mch/private/key.pem');
 $wechatpayCertificate = PemUtil::loadCertificate('/path/to/wechatpay/cert.pem');
+$wechatpayCertificateSerialNumber = PemUtil::parseCertificateSerialNo($wechatpayCertificate);

- $wechatpayMiddleware = WechatPayMiddleware::builder()
-     ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
-     ->withWechatPay([ $wechatpayCertificate ])
-     ->build();
- $stack = GuzzleHttp\HandlerStack::create();
- $stack->push($wechatpayMiddleware, 'wechatpay');
- $client = new GuzzleHttp\Client(['handler' => $stack]);
+ $instance = Builder::factory([
+     'mchid' => $merchantId,
+     'serial' => $merchantSerialNumber,
+     'privateKey' => $merchantPrivateKey,
+     'certs' => [$wechatpayCertificateSerialNumber => $wechatpayCertificate],
+ ]);
```

### 调用方法调整

#### **GET**请求

可以使用本SDK提供的语法糖，缩减请求代码结构如下：

```diff
 try {
-    $resp = $client->request('GET', 'https://api.mch.weixin.qq.com/v3/...', [
+    $resp = $instance->chain('v3/...')->get([
-         'headers' => [ 'Accept' => 'application/json' ]
     ]);
 } catch (RequestException $e) {
     //do something
 }
```

#### **POST**请求

缩减请求代码如下：

```diff
 try {
-    $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/...', [
+    $resp = $instance->chain('v3/...')->post([
          'json' => [ // JSON请求体
              'field1' => 'value1',
              'field2' => 'value2'
          ],
-         'headers' => [ 'Accept' => 'application/json' ]
     ]);
 } catch (RequestException $e) {
     //do something
 }
```

#### 上传媒体文件

```diff
- use WechatPay\GuzzleMiddleware\Util\MediaUtil;
+ use WeChatPay\Util\MediaUtil;
 $media = new MediaUtil('/your/file/path/with.extension');
 try {
-     $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/[merchant/media/video_upload|marketing/favor/media/image-upload]', [
+     $resp = $instance->chain('v3/marketing/favor/media/image-upload')->post([
         'body'    => $media->getStream(),
         'headers' => [
-             'Accept'       => 'application/json',
             'content-type' => $media->getContentType(),
         ]
     ]);
 } catch (Exception $e) {
     // do something
 }
```

```diff
 try {
-     $resp = $client->post('merchant/media/upload', [
+     $resp = $instance->chain('v3/merchant/media/upload')->post([
         'body'    => $media->getStream(),
         'headers' => [
-             'Accept'       => 'application/json',
             'content-type' => $media->getContentType(),
         ]
     ]);
 } catch (Exception $e) {
     // do something
 }
```

#### 敏感信息加/解密

```diff
- use WechatPay\GuzzleMiddleware\Util\SensitiveInfoCrypto;
+ use WeChatPay\Crypto\Rsa;
- $encryptor = new SensitiveInfoCrypto(PemUtil::loadCertificate('/path/to/wechatpay/cert.pem'));
+ $encryptor = function($msg) use ($wechatpayCertificate) { return Rsa::encrypt($msg, $wechatpayCertificate); };

 try {
-     $resp = $client->post('/v3/applyment4sub/applyment/', [
+     $resp = $instance->chain('v3/applyment4sub/applyment/')->post([
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
-             'Wechatpay-Serial' => 'must be the serial number via the downloaded pem file of `/v3/certificates`',
+             'Wechatpay-Serial' => $wechatpayCertificateSerialNumber,
-             'Accept'           => 'application/json',
         ],
     ]);
 } catch (Exception $e) {
     // do something
 }
```

#### 平台证书下载工具

在第一次下载平台证书时，本类库充分利用了`\GuzzleHttp\HandlerStack`中间件管理器能力，按照栈执行顺序，在返回结果验签中间件`verifier`之前注册`certsInjector`，之后注册`certsRecorder`来 **"解开"** "死循环"问题。
本类库提供的下载工具**未改变** `返回结果验签` 逻辑，完整实现可参考[bin/CertificateDownloader.php](bin/CertificateDownloader.php)。

#### AesGcm平台证书解密

```diff
- use WechatPay\GuzzleMiddleware\Util\AesUtil;
+ use WeChatPay\Crypto\AesGcm;
- $decrypter = new AesUtil($opts['key']);
- $plain = $decrypter->decryptToString($encCert['associated_data'], $encCert['nonce'], $encCert['ciphertext']);
+ $plain = AesGcm::decrypt($encCert['ciphertext'], $opts['key'], $encCert['nonce'], $encCert['associated_data']);
```

## 从 php_sdk_v3.0.10 迁移至 1.0

这个`php_sdk_v3.0.10`版的SDK，是在`APIv2`版的文档上有下载，这里提供一份迁移指南，抛砖引玉如何迁移。
### 初始化

从手动文件模式调整参数，变更为实例初始化方式:

```diff
- // ③、修改lib/WxPay.Config.php为自己申请的商户号的信息（配置详见说明）
+ use WeChatPay/Builder;
+ $instance = new Builder([
+   'mchid'      => $mchid,
+   'serial'     => 'nop',
+   'privateKey' => 'any',
+   'secret'     => $apiv2Key,
+   'certs'      => ['any' => null],
+   'merchant'   => ['key' => '/path/to/cert/apiclient_key.pem', 'cert' => '/path/to/cert/apiclient_cert.pem'],
+ ]);
```

### 统一下单-JSAPI下单及数据二次签名

```diff
- require_once "../lib/WxPay.Api.php";
- require_once "WxPay.JsApiPay.php";
- require_once "WxPay.Config.php";

- $tools = new JsApiPay();
- $openId = $tools->GetOpenid();
- $input = new WxPayUnifiedOrder();
- $input->SetBody("test");
- $input->SetAttach("test");
- $input->SetOut_trade_no("sdkphp".date("YmdHis"));
- $input->SetTotal_fee("1");
- $input->SetTime_start(date("YmdHis"));
- $input->SetTime_expire(date("YmdHis", time() + 600));
- $input->SetGoods_tag("test");
- $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
- $input->SetTrade_type("JSAPI");
- $input->SetOpenid($openId);
- $config = new WxPayConfig();
- $order = WxPayApi::unifiedOrder($config, $input);
- printf_info($order);
- // 数据签名
- $jsapi = new WxPayJsApiPay();
- $jsapi->SetAppid($order["appid"]);
- $timeStamp = time();
- $jsapi->SetTimeStamp("$timeStamp");
- $jsapi->SetNonceStr(WxPayApi::getNonceStr());
- $jsapi->SetPackage("prepay_id=" . $order['prepay_id']);
- $config = new WxPayConfig();
- $jsapi->SetPaySign($jsapi->MakeSign($config));
- $parameters = json_encode($jsapi->GetValues());
+ use WeChatPay\Formatter;
+ use WeChatPay\Transformer;
+ use WeChatPay\Crypto\Hash;
+ // 直接构造请求数组参数
+ $input = [
+     'appid'        => $appid, // 从config拿到当前请求参数上
+     'mch_id'       => $mchid, // 从config拿到当前请求参数上
+     'body'         => 'test',
+     'attach'       => 'test',
+     'out_trade_no' => 'sdkphp' . date('YmdHis'),
+     'total_fee'    => '1',
+     'time_start'   => date('YmdHis'),
+     'time_expire'  => date('YmdHis, time() + 600),
+     'goods_tag'    => 'test',
+     'notify_url'   => 'http://paysdk.weixin.qq.com/notify.php',
+     'trade_type'   => 'JSAPI',
+     'openid'       => $openId, // 有太多优秀解决方案能够获取到这个值，这里假定已经有了
+     'sign_type'    => Hash::ALGO_HMAC_SHA256, // 以下二次数据签名「签名类型」需与预下单数据「签名类型」一致
+ ];
+ // 发起请求并取得结果，抑制`E_USER_DEPRECATED`提示
+ $resp  = @$instance->chain('v2/pay/unifiedorder')->post(['xml' => $input]);
+ $order = Transformer::toArray((string)$resp->getBody());
+ // print_r($order);
+ // 数据签名
+ $params = [
+     'appId'     => $appid,
+     'timeStamp' => (string)Formatter::timestamp(),
+     'nonceStr'  => Formatter::nonce(),
+     'package'   => 'prepay_id=' . $order['prepay_id'],
+     'signType'  => Hash::ALGO_HMAC_SHA256,
+ ];
+ // 二次数据签名「签名类型」需与预下单数据「签名类型」一致
+ $params['paySign'] = Hash::sign(Hash::ALGO_HMAC_SHA256, Formatter::queryStringLike(Formatter::ksort($parameters)), $apiv2Key);
+ $parameters = json_encode($params);
```

### 付款码支付

```diff
- require_once "../lib/WxPay.Api.php";
- require_once "WxPay.MicroPay.php";
-
- $auth_code = $_REQUEST["auth_code"];
- $input = new WxPayMicroPay();
- $input->SetAuth_code($auth_code);
- $input->SetBody("刷卡测试样例-支付");
- $input->SetTotal_fee("1");
- $input->SetOut_trade_no("sdkphp".date("YmdHis"));
-
- $microPay = new MicroPay();
- printf_info($microPay->pay($input));
+ use WeChatPay\Formatter;
+ use WeChatPay\Transformer;
+ // 直接构造请求数组参数
+ $input = [
+     'appid'            => $appid, // 从config拿到当前请求参数上
+     'mch_id'           => $mchid, // 从config拿到当前请求参数上
+     'auth_code'        => $auth_code,
+     'body'             => '刷卡测试样例-支付',
+     'total_fee'        => '1',
+     'out_trade_no'     => 'sdkphp' . date('YmdHis'),
+     'spbill_create_ip' => $mechineIp,
+ ];
+ // 发起请求并取得结果，抑制`E_USER_DEPRECATED`提示
+ $resp  = @$instance->chain('v2/pay/micropay')->post(['xml' => $input]);
+ $order = Transformer::toArray((string)$resp->getBody());
+ // print_r($order);
```

### 撤销订单

```diff
+ $input = [
+     'appid'        => $appid, // 从config拿到当前请求参数上
+     'mch_id'       => $mchid, // 从config拿到当前请求参数上
+     'out_trade_no' => $outTradeNo,
+ ];
+ // 发起请求并取得结果，抑制`E_USER_DEPRECATED`提示
+ $resp   = @$instance->chain('v2/secapi/pay/reverse')->postAsync(['xml' => $input, 'security' => true])->wait();
+ $result = Transformer::toArray((string)$resp->getBody());
+ // print_r($result);
```

其他`APIv2`迁移及接口请求类似如上，示例仅做了正常返回样例，**程序缜密性，需要加入`try catch`/`otherwise`结构捕获异常情况**。

至此，迁移后，`Chainable`、`PromiseA+`以及强劲的`PHP8`运行时，均可愉快地调用微信支付官方接口了。
