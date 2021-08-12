# 变更历史

## 1.1.1 - 2021-08-12

[变更细节](../../compare/v1.1.0...v1.1.1)

- 优化内部中间件始终从`\GuzzleHttp\Psr7\Stream::__toString`取值，并在取值后，判断如果影响了`Stream`指针，则回滚至开始位;
- 增加`APIv2`上一些特殊用法示例，增加`数据签名`样例；
- 增加`APIv2`文档提示说明`DEP_XML_PROTOCOL_IS_REACHABLE_EOL`;
- 修正`APIv2`上，转账至用户零钱接口，`xml`入参是`mchid`引发的不适问题；

## 1.1.0 - 2021-08-07

[变更细节](../../compare/v1.0.9...v1.1.0)

- 调整内部中间件栈顺序，并对`APIv3`的正常返回内容(`20X`)做精细判断，逻辑异常时使用`\GuzzleHttp\Exception\RequestException`抛出，应用端可捕获源返回内容;
- 对于`30X`及`4XX`,`5XX`返回，`Guzzle`基础中间件默认已处理，具体用法及使用，可参考`\GuzzleHttp\RedirectMiddleware`及`\GuzzleHttp\Middleware::httpErrors`说明；
- 详细变化可见[1.0至1.1升级指南](UPGRADING.md)

## 1.0.9 - 2021-08-05

[变更细节](../../compare/v1.0.8...v1.0.9)

- 优化平台证书下载器`CertificateDownloader`异常处理逻辑部分，详见[#22](https://github.com/wechatpay-apiv3/wechatpay-php/issues/22);
- 优化`README`使用示例的异常处理部分；

## 1.0.8 - 2021-07-26

[变更细节](../../compare/v1.0.7...v1.0.8)

- 增加`WeChatPay\Crypto\Hash::equals`方法，用于比较`APIv2`哈希签名值是否相等;
- 建议使用`APIv2`的商户，在回调通知场景中，使用此方法来验签，相关说明见PHP[hash_equals](https://www.php.net/manual/zh/function.hash-equals.php)说明；

## 1.0.7 - 2021-07-22

[变更细节](../../compare/v1.0.6...v1.0.7)

- 完善`APIv3`及`APIv2`工厂方法初始化说明，推荐优先使用`APIv3`;

## 1.0.6 - 2021-07-21

[变更细节](../../compare/v1.0.5...v1.0.6)

- 调整 `Formatter::nonce` 算法，使用密码学安全的`random_bytes`生产`BASE62`随机字符串;

## 1.0.5 - 2021-07-08

[变更细节](../../compare/v1.0.4...v1.0.5)

- 核心代码全部转入严格类型`declare(strict_types=1)`校验模式;
- 调整 `Authorization` 头格式顺序，debug时优先展示关键信息;
- 调整 媒体文件`MediaUtil`类读取文件时，严格二进制读，避免跨平台干扰问题;
- 增加 测试用例覆盖`APIv2`版用法；

## 1.0.4 - 2021-07-05

[变更细节](../../compare/v1.0.3...v1.0.4)

- 修正 `segments` 首字符大写时异常问题;
- 调整 初始入参如果有提供`handler`，透传给了下游客户端问题;
- 增加 `PHP`最低版本说明，相关问题 [#10](https://github.com/wechatpay-apiv3/wechatpay-php/issues/10);
- 增加 测试用例已基本全覆盖`APIv3`版用法；

## 1.0.3 - 2021-06-28

[变更细节](../../compare/v1.0.2...v1.0.3)

- 初始化`jsonBased`入参判断，`平台证书及序列号`结构体内不能含`商户序列号`，相关问题 [#8](https://github.com/wechatpay-apiv3/wechatpay-php/issues/8);
- 修复文档错误，相关 [#7](https://github.com/wechatpay-apiv3/wechatpay-php/issues/7);
- 优化 `github actions`，针对PHP7.2单独缓存依赖(`PHP7.2`下只能跑`PHPUnit8`，`PHP7.3`以上均可跑`PHPUnit9`);
- 增加 `composer test` 命令并集成进 `CI` 内（测试用例持续增加中）；
- 修复 `PHPStan` 所有遗留问题；

## 1.0.2 - 2021-06-24

[变更细节](../../compare/v1.0.1...v1.0.2)

- 优化了一些性能；
- 增加 `github actions` 覆盖 PHP7.2/7.3/7.4/8.0 + Linux/macOs/Windows环境；
- 提升 `phpstan` 至 `level8` 最严谨级别，并修复大量遗留问题；
- 优化 `\WeChatPay\Exception\WeChatPayException` 异常类接口；
- 完善文档及平台证书下载器用法说明；

## 1.0.1 - 2021-06-21

[变更细节](../../compare/v1.0.0...v1.0.1)

- 优化了一些性能；
- 修复了大量 `phpstan level6` 静态分析遗留问题；
- 新增`\WeChatPay\Exception\WeChatPayException`异常类接口；
- 完善文档及方法类型签名；

## 1.0.0 - 2021-06-18

源自 `wechatpay-guzzle-middleware`，不兼容源版，顾自 `v1.0.0` 开始。

- `APIv2` & `APIv3` 同质化调用SDK，默认为 `APIv3` 版；
- 标记 `APIv2` 为不推荐调用，预期 `v2.0` 会移除掉；
- 支持 `同步(sync)`（默认）及 `异步(async)` 请求服务端接口；
- 支持 `链式(chain)` 请求服务端接口；
