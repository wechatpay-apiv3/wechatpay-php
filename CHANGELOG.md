# 变更历史

## [1.4.5](../../compare/v1.4.4...v1.4.5) - 2022-05-21

- 新增`APIv3`请求/响应特殊验签逻辑，国内两个下载接口自动忽略验签，海外商户账单下载仅验RSA签名，详见 [#94](https://github.com/wechatpay-apiv3/wechatpay-php/issues/94)；
- 新增`APIv3`[海外商户账单下载](https://pay.weixin.qq.com/wiki/doc/api/wxpay/ch/fusion_wallet_ch/QuickPay/chapter8_5.shtml)测试用例，示例说明如何验证流`SHA1`摘要;

## [1.4.4](../../compare/v1.4.3...v1.4.4) - 2022-05-19

- 新增`APIv3`[客诉图片下载](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_18.shtml)测试用例，示例说明如何避免[double pct-encoded](https://github.com/guzzle/uri-template/issues/18)问题;
- PHP内置函数`hash`方法在`PHP8`变更了返回值逻辑，代之为抛送`ValueError`异常，优化`MediaUtilTest`测试用例，以兼容`PHP7`;
- 新增`APIv2`请求/响应白名单`URL`及调整验签逻辑，对于白名单内的请求，已知无`sign`返回，应用侧自动忽略验签，详见 [#92](https://github.com/wechatpay-apiv3/wechatpay-php/issues/92)；

## [1.4.3](../../compare/v1.4.2...v1.4.3) - 2022-01-04

- 优化，严格限定初始化时`mchid`为字符串;
- 优化，严格限定`chain`接口函数入参为字符串；
- 优化`README`，增加`常见问题`示例说明`URL template`用法；

## [1.4.2](../../compare/v1.4.1...v1.4.2) - 2021-12-02

- 优化`Rsa::parse`代码逻辑，去除`is_resource`/`is_object`检测;
- 调整`Rsa::from[Pkcs8|Pkcs1|Spki]`加载语法糖实现，以`Rsa::from`为统一入口；
- 优化`ClientDecorator::request[Async]`处理逻辑，优先替换`URI Template`变量，可支持短链模式调用接口；

## [1.4.1](../../compare/v1.4.0...v1.4.1) - 2021-11-03

- 新增`phpstan/phpstan:^1.0`支持；
- 优化代码，消除函数内部不安全的`Unsafe call to private method|property ... through static::`调用隐患；

## [1.4.0](../../compare/v1.3.2...v1.4.0) - 2021-10-24

- 调整`Guzzle`最低版本支持至v6.5.0，相应降低PHP版本要求至7.1.2，相关见[#71519](http://bugs.php.net/71519);
- 调整`PHPUnit`最低版本至v7.5.0||v8.5.16||v9.3.5，相关问题见[#4663](https://github.com/sebastianbergmann/phpunit/issues/4663);

详细说明可见[1.3至1.4升级指南](UPGRADING.md)

## [1.3.2](../../compare/v1.3.1...v1.3.2) - 2021-09-30

- 增加`MediaUtil::setMeta`函数，以支持特殊场景(API)下`meta`数据结构的特殊需求；

## [1.3.1](../../compare/v1.3.0...v1.3.1) - 2021-09-22

- 修正`APIv2`上，合单支付产品`xml`入参是`combine_mch_id`引发的不适问题；

## [1.3.0](../../compare/v1.2.2...v1.3.0) - 2021-09-18

- 增加IDE提示`OpenAPI\V2`&`OpenAPI\V3`的两个入口，接口描述文件拆分为单独的包发行，生产环境无需安装（没必要），仅面向开发环境；
- 优化`userAgent`方法，使拼接`User-Agent`字典清晰可读；
- 优化`README`，增加`V3`通知验签注释说明，增加`v2`链式`otherwise`处理逻辑说明；

## [1.2.2](../../compare/v1.2.1...v1.2.2) - 2021-09-09

- 以`at sign`形式，温和提示`APIv2`的`DEP_XML_PROTOCOL_IS_REACHABLE_EOL`，相关[#38](https://github.com/wechatpay-apiv3/wechatpay-php/issues/38)；
- 优化`Transformer::toArray`函数，对入参`xml`非法时，返回空`array`，并把最后一条错误信息温和地打入`E_USER_NOTICE`通道；
- 修正`Formatter::ksort`排列键值时兼容问题，使用`字典序(dictionary order)`排序，相关[#41](https://github.com/wechatpay-apiv3/wechatpay-php/issues/41), 感谢 @suiaiyun 报告此问题；

## [1.2.1](../../compare/v1.2.0...v1.2.1) - 2021-09-06

- 增加`加密RSA私钥`的测试用例覆盖；
- 优化文档样例及升级指南，修正错别字；
- 优化内部`withDefaults`函数，使用变长参数合并初始化参数；
- 优化`Rsa::encrypt`及`Rsa::decrpt`方法，增加第三可选参数，以支持`OPENSSL_PKCS1_PADDING`填充模式的加解密；

## [1.2.0](../../compare/v1.1.4...v1.2.0) - 2021-09-02

- 新增`Rsa::from`统一加载函数，以接替`PemUtil::loadPrivateKey`函数功能；
- 新增`Rsa::fromPkcs1`, `Rsa::fromPkcs8`, `Rsa::fromSpki`语法糖，以支持从云端加载RSA公/私钥；
- 新增RSA公钥`Rsa::pkcs1ToSpki`格式转换函数，入参是`base64`字符串；
- 标记 `PemUtil::loadPrivateKey`及`PemUtil::loadPrivateKeyFromString`为`不推荐用法`;
- 详细变化可见[1.1至1.2升级指南](UPGRADING.md)

## [1.1.4](../../compare/v1.1.3...v1.1.4) - 2021-08-26

- 优化`平台证书下载工具`使用说明，增加`composer exec`执行方法说明；
- 优化了一点点代码结构，使逻辑更清晰了一些；

## [1.1.3](../../compare/v1.1.2...v1.1.3) - 2021-08-22

- 优化`README`，增加`回调通知`处理说明及样本代码；
- 优化测试用例，使用`严格限定名称`方式引用系统内置函数；
- 优化`Makefile`，在生成模拟证书时，避免产生`0x00`开头的证书序列号；
- 调整`composer.json`，新增`guzzlehttp/uri-template:^1.0`支持；

## [1.1.2](../../compare/V1.1.1...v1.1.2) - 2021-08-19

- 优化`README`，`密钥`、`证书`等相关术语保持一致；
- 优化`UPGRADING`，增加从`php_sdk_v3.0.10`迁移指南；
- 优化测试用例，完整覆盖`PHP7.2/7.3/7.4/8.0 + Linux/macOS/Windows`运行时；
- 调整`composer.json`，去除`test`, `phpstan`命令，面向生产环境可用；

## [1.1.1](../../compare/v1.1.0...V1.1.1) - 2021-08-13

- 优化内部中间件始终从`\GuzzleHttp\Psr7\Stream::__toString`取值，并在取值后，判断如果影响了`Stream`指针，则回滚至开始位;
- 增加`APIv2`上一些特殊用法示例，增加`数据签名`样例；
- 增加`APIv2`文档提示说明`DEP_XML_PROTOCOL_IS_REACHABLE_EOL`;
- 修正`APIv2`上，转账至用户零钱接口，`xml`入参是`mchid`引发的不适问题；
- 增加`APIv2`上转账至用户零钱接口测试用例，样例说明如何进行异常捕获；

## [1.1.0](../../compare/v1.0.9...v1.1.0) - 2021-08-07

- 调整内部中间件栈顺序，并对`APIv3`的正常返回内容(`20X`)做精细判断，逻辑异常时使用`\GuzzleHttp\Exception\RequestException`抛出，应用端可捕获源返回内容;
- 对于`30X`及`4XX`,`5XX`返回，`Guzzle`基础中间件默认已处理，具体用法及使用，可参考`\GuzzleHttp\RedirectMiddleware`及`\GuzzleHttp\Middleware::httpErrors`说明；
- 详细变化可见[1.0至1.1升级指南](UPGRADING.md)

## [1.0.9](../../compare/v1.0.8...v1.0.9) - 2021-08-05

- 优化平台证书下载器`CertificateDownloader`异常处理逻辑部分，详见[#22](https://github.com/wechatpay-apiv3/wechatpay-php/issues/22);
- 优化`README`使用示例的异常处理部分；

## [1.0.8](../../compare/v1.0.7...v1.0.8) - 2021-07-26

- 增加`WeChatPay\Crypto\Hash::equals`方法，用于比较`APIv2`哈希签名值是否相等;
- 建议使用`APIv2`的商户，在回调通知场景中，使用此方法来验签，相关说明见PHP[hash_equals](https://www.php.net/manual/zh/function.hash-equals.php)说明；

## [1.0.7](../../compare/v1.0.6...v1.0.7) - 2021-07-22

- 完善`APIv3`及`APIv2`工厂方法初始化说明，推荐优先使用`APIv3`;

## [1.0.6](../../compare/v1.0.5...v1.0.6) - 2021-07-21

- 调整 `Formatter::nonce` 算法，使用密码学安全的`random_bytes`生产`BASE62`随机字符串;

## [1.0.5](../../compare/v1.0.4...v1.0.5) - 2021-07-08

- 核心代码全部转入严格类型`declare(strict_types=1)`校验模式;
- 调整 `Authorization` 头格式顺序，debug时优先展示关键信息;
- 调整 媒体文件`MediaUtil`类读取文件时，严格二进制读，避免跨平台干扰问题;
- 增加 测试用例覆盖`APIv2`版用法；

## [1.0.4](../../compare/v1.0.3...v1.0.4) - 2021-07-05

- 修正 `segments` 首字符大写时异常问题;
- 调整 初始入参如果有提供`handler`，透传给了下游客户端问题;
- 增加 `PHP`最低版本说明，相关问题 [#10](https://github.com/wechatpay-apiv3/wechatpay-php/issues/10);
- 增加 测试用例已基本全覆盖`APIv3`版用法；

## [1.0.3](../../compare/v1.0.2...v1.0.3) - 2021-06-28

- 初始化`jsonBased`入参判断，`平台证书及序列号`结构体内不能含`商户序列号`，相关问题 [#8](https://github.com/wechatpay-apiv3/wechatpay-php/issues/8);
- 修复文档错误，相关 [#7](https://github.com/wechatpay-apiv3/wechatpay-php/issues/7);
- 优化 `github actions`，针对PHP7.2单独缓存依赖(`PHP7.2`下只能跑`PHPUnit8`，`PHP7.3`以上均可跑`PHPUnit9`);
- 增加 `composer test` 命令并集成进 `CI` 内（测试用例持续增加中）；
- 修复 `PHPStan` 所有遗留问题；

## [1.0.2](../../compare/v1.0.1...v1.0.2) - 2021-06-24

- 优化了一些性能；
- 增加 `github actions` 覆盖 PHP7.2/7.3/7.4/8.0 + Linux/macOS/Windows环境；
- 提升 `phpstan` 至 `level8` 最严谨级别，并修复大量遗留问题；
- 优化 `\WeChatPay\Exception\WeChatPayException` 异常类接口；
- 完善文档及平台证书下载器用法说明；

## [1.0.1](../../compare/v1.0.0...v1.0.1) - 2021-06-21

- 优化了一些性能；
- 修复了大量 `phpstan level6` 静态分析遗留问题；
- 新增`\WeChatPay\Exception\WeChatPayException`异常类接口；
- 完善文档及方法类型签名；

## [1.0.0](../../compare/6782ac3..v1.0.0) - 2021-06-18

源自 `wechatpay-guzzle-middleware`，不兼容源版，顾自 `v1.0.0` 开始。

- `APIv2` & `APIv3` 同质化调用SDK，默认为 `APIv3` 版；
- 标记 `APIv2` 为不推荐调用，预期 `v2.0` 会移除掉；
- 支持 `同步(sync)`（默认）及 `异步(async)` 请求服务端接口；
- 支持 `链式(chain)` 请求服务端接口；
