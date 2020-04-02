# Certificate Downloader

Certificate Downloader 是 PHP版 微信支付 APIv3 平台证书的命令行下载工具。该工具可从 `https://api.mch.weixin.qq.com/v3/certificates` 接口获取商户可用证书，并使用 [APIv3 密钥](https://wechatpay-api.gitbook.io/wechatpay-api-v3/ren-zheng/api-v3-mi-yao) 和 AES_256_GCM 算法进行解密，并把解密后证书下载到指定位置。

## 使用
使用方法与 [Java版Certificate Downloader](https://github.com/wechatpay-apiv3/CertificateDownloader) 一致，参数与常见问题请参考[其文档](https://github.com/wechatpay-apiv3/CertificateDownloader/blob/master/README.md)。

```shell
> php tool/CertificateDownloader.php
Usage: 微信支付平台证书下载工具 [-hV] [-c=<wechatpayCertificatePath>]
                    -f=<privateKeyFilePath> -k=<apiV3key> -m=<merchantId>
                    -o=<outputFilePath> -s=<serialNo>
  -m, --mchid=<merchantId>   商户号
  -s, --serialno=<serialNo>  商户证书的序列号
  -f, --privatekey=<privateKeyFilePath>
                             商户的私钥文件
  -k, --key=<apiV3key>       ApiV3Key
  -c, --wechatpay-cert=<wechatpayCertificatePath>
                             微信支付平台证书，验证签名
  -o, --output=<outputFilePath>
                             下载成功后保存证书的路径
  -V, --version              Print version information and exit.
  -h, --help                 Show this help message and exit.
```

完整命令示例：

```shell
php tool/CertificateDownloader.php -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath} -c ${wechatpayCertificateFilePath}
```



## 常见问题

### 如何保证证书正确
请参见CertificateDownloader文档中[关于如何保证证书正确的说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E5%A6%82%E4%BD%95%E4%BF%9D%E8%AF%81%E8%AF%81%E4%B9%A6%E6%AD%A3%E7%A1%AE)。

### 如何使用信任链验证平台证书
请参见CertificateDownloader文档中[关于如何使用信任链验证平台证书的说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E5%A6%82%E4%BD%95%E4%BD%BF%E7%94%A8%E4%BF%A1%E4%BB%BB%E9%93%BE%E9%AA%8C%E8%AF%81%E5%B9%B3%E5%8F%B0%E8%AF%81%E4%B9%A6)。

### 第一次下载证书

请参见CertificateDownloader文档中[相关说明](https://github.com/wechatpay-apiv3/CertificateDownloader#%E7%AC%AC%E4%B8%80%E6%AC%A1%E4%B8%8B%E8%BD%BD%E8%AF%81%E4%B9%A6)。

