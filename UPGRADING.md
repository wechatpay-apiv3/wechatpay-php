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
