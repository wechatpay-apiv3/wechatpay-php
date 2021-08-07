# 升级指南

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

## 从 0.2 迁移至 1.0

如 [变更历史](CHANGELOG.md) 所述，本类库自1.0不兼容`wechatpay/wechatpay-guzzle-middleware:~0.2`，原因如下：

1. 升级`Guzzle`大版本至`7`, `Guzzle7`做了许多不兼容更新，相关讨论可见[#54](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/54)，其推移系统要求PHP最低版本至`7.2.5`，重要特性是加入了`函数参数类型签名`以及`函数返回值类型签名`功能，从开发语言层面，使类库健壮性有了显著提升；
2. 重构并修正了原[敏感信息加解密](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/25)过度设计问题；
3. 重新设计了类库函数及方案，以提供[回调通知签名](https://github.com/wechatpay-apiv3/wechatpay-guzzle-middleware/issues/42)所需方法；
4. 调整`composer.json`移动`guzzlehttp/guzzle`从`require-dev`弱依赖至`require`强依赖，开发者无须再手动添加；
5. 缩减初始化手动拼接客户端参数至`Builder::factory`，统一由SDK来构建客户端；
6. 新增链式调用封装器，原生提供对`APIv3`的链式调用；
7. 新增`APIv2`支持，推荐商户可以先升级至本类库支持的`APIv2`能力，然后再按需升级至相对应的`APIv3`能力；
8. 增加类库单元测试覆盖`Linux`,`macOs`及`Windows`运行时；
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
+    $resp = $instance->chain('/v3/...')->get([
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
+    $resp = $instance->chain('/v3/...')->post([
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
+     $resp = $instance->chain('v3/applyment4sub/applyment/', [
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
- use WechatPay\Util\AesUtil;
+ use WeChatPay\Crypto\AesGcm;
- $decrypter = new AesUtil($opts['key']);
- $plain = $decrypter->decryptToString($encCert['associated_data'], $encCert['nonce'], $encCert['ciphertext']);
+ $plain = AesGcm::decrypt($encCert['ciphertext'], $opts['key'], $encCert['nonce'], $encCert['associated_data']);
```

至此，迁移后，`Chainable`、`PromiseA+`以及强劲的`PHP8`运行时，均可愉快地调用微信支付官方接口了。
