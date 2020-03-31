# Certificate Downloader

Certificate Downloader 是 PHP版 微信支付 APIv3 平台证书的命令行下载工具。该工具可从 `https://api.mch.weixin.qq.com/v3/certificates` 接口获取商户可用证书，并使用 [APIv3 密钥](https://wechatpay-api.gitbook.io/wechatpay-api-v3/ren-zheng/api-v3-mi-yao) 和 AES_256_GCM 算法进行解密，并把解密后证书下载到指定位置。

使用方法与 [Java版Certificate Downloader](https://github.com/wechatpay-apiv3/CertificateDownloader) 一致，常见问题与指南请参见[Java版文档](https://github.com/wechatpay-apiv3/CertificateDownloader/blob/master/README.md)。
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

