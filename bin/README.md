# Certificate Downloader

Certificate Downloader 是 PHP版 微信支付 APIv3 平台证书的命令行下载工具。该工具可从 `https://api.mch.weixin.qq.com/v3/certificates` 接口获取商户可用证书，并使用 [APIv3 密钥](https://wechatpay-api.gitbook.io/wechatpay-api-v3/ren-zheng/api-v3-mi-yao) 和 AES_256_GCM 算法进行解密，并把解密后证书下载到指定位置。

## 使用
使用方法与 [Java版Certificate Downloader](https://github.com/wechatpay-apiv3/CertificateDownloader) 一致，参数与常见问题请参考[其文档](https://github.com/wechatpay-apiv3/CertificateDownloader/blob/master/README.md)。

```shell
> bin/CertificateDownloader.php

Usage: 微信支付平台证书下载工具 [-hV]
                    -f=<privateKeyFilePath> -k=<apiV3key> -m=<merchantId>
                    -s=<serialNo> -o=[outputFilePath] -u=[baseUri]
Options:
  -m, --mchid=<merchantId>   商户号
  -s, --serialno=<serialNo>  商户证书的序列号
  -f, --privatekey=<privateKeyFilePath>
                             商户的私钥文件
  -k, --key=<apiV3key>       ApiV3Key
  -o, --output=[outputFilePath]
                             下载成功后保存证书的路径，可选参数，默认为临时文件目录夹
  -u, --baseuri=[baseUri]    接入点，默认为 https://api.mch.weixin.qq.com/
  -V, --version              Print version information and exit.
  -h, --help                 Show this help message and exit.
```

完整命令示例：

```shell
./bin/CertificateDownloader.php -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

或

```shell
php -f ./bin/CertificateDownloader.php -- -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

或

```shell
php ./bin/CertificateDownloader.php -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

使用`composer`安装的软件包，可以通过如下命令下载：

```shell
vendor/bin/CertificateDownloader.php -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

或

```shell
composer exec CertificateDownloader.php -- -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

使用源码克隆版本，也可以使用`composer`通过以下命令下载：

```shell
composer v3-certificates -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
```

支持从海外接入点下载，命令如下：

```shell
composer v3-certificates -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath} -u https://apihk.mch.weixin.qq.com/
```

**注:** 示例命令行上的`${}`是变量表达方法，运行时请替换(包括`${}`)为对应的实际值。

## 常见问题

### 如何保证证书正确
请参见CertificateDownloader文档中[关于如何保证证书正确的说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E5%A6%82%E4%BD%95%E4%BF%9D%E8%AF%81%E8%AF%81%E4%B9%A6%E6%AD%A3%E7%A1%AE)。

### 如何使用信任链验证平台证书
请参见CertificateDownloader文档中[关于如何使用信任链验证平台证书的说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E5%A6%82%E4%BD%95%E4%BD%BF%E7%94%A8%E4%BF%A1%E4%BB%BB%E9%93%BE%E9%AA%8C%E8%AF%81%E5%B9%B3%E5%8F%B0%E8%AF%81%E4%B9%A6)。

### 第一次下载证书

请参见CertificateDownloader文档中[相关说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E7%AC%AC%E4%B8%80%E6%AC%A1%E4%B8%8B%E8%BD%BD%E8%AF%81%E4%B9%A6)。
