# 变更历史

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
