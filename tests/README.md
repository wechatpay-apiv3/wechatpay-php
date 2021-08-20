# 测试

本项目测试所依赖的`RSA私钥`、`RSA公钥`、`X509证书`、以及`证书序列号`，由根目录`Makefile`模拟生成，理论上每次/每轮/不同环境/不同版本上的测试依赖均不同，以此来模拟真实环境中的不可预测场景。

在项目根目录，`*nix`环境，执行如下命令；`Windows`环境，请使用`Cywin`来测试，详细可参考`GitHub Actions`上`Windows`环境测试：

```shell
make
```

类似将打印如下信息：

```
vendor/bin/phpstan analyse --no-progress
Note: Using configuration file ./wechatpay-php/phpstan.neon.dist.


 [OK] No errors


openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out ./tests/fixtures/mock.pkcs8.key
....................................................+++
.+++
openssl rsa -in ./tests/fixtures/mock.pkcs8.key -out ./tests/fixtures/mock.pkcs1.key
writing RSA key
openssl rsa -in ./tests/fixtures/mock.pkcs8.key -pubout -out ./tests/fixtures/mock.spki.pem
writing RSA key
openssl rsa -pubin -in ./tests/fixtures/mock.spki.pem -RSAPublicKey_out -out ./tests/fixtures/mock.pkcs1.pem
writing RSA key
fixtures="./tests/fixtures/" && serial=$(openssl rand -hex 20 | tr "[a-z]" "[A-Z]" | tee ${fixtures}mock.serial.txt) && \
	MSYS_NO_PATHCONV=1 openssl req -new -sha256 -key ${fixtures}mock.pkcs8.key \
		-subj "/C=CN/ST=Shanghai/O=WeChatPay Community/CN=WeChatPay Community CI" | \
	openssl x509 -req -sha256 -days 1 -set_serial "0x${serial}" \
		-signkey ${fixtures}mock.pkcs8.key -clrext -out ${fixtures}mock.sha256.crt \
	&& openssl x509 -in ${fixtures}mock.sha256.crt -noout -text
Signature ok
subject=/C=CN/ST=Shanghai/O=WeChatPay Community/CN=WeChatPay Community CI
Getting Private key
Certificate:
    Data:
        Version: 1 (0x0)
        Serial Number:
            e3:40:88:44:f3:46:43:50:98:b7:a3:c7:b0:ea:f8:05:f3:ea:5c:7f
    Signature Algorithm: sha256WithRSAEncryption
        Issuer: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Validity
            Not Before: Aug 19 01:22:16 2021 GMT
            Not After : Aug 20 01:22:16 2021 GMT
        Subject: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Subject Public Key Info:
            Public Key Algorithm: rsaEncryption
                Public-Key: (2048 bit)
                Modulus:
                    00:b2:2c:92:84:94:59:f0:41:cc:9e:78:ca:7a:b9:
                    ce:7b:c6:0f:3c:7d:74:64:51:d8:5f:2d:66:31:ce:
                    ff:78:30:64:38:bd:9b:05:f3:c8:50:1a:9e:69:a8:
                    96:20:93:5f:9a:61:7c:a5:d9:60:5a:88:40:6b:a3:
                    8a:7c:5e:a9:32:d4:20:d6:df:71:bd:73:e9:e7:50:
                    1e:fa:28:5e:f3:6e:c7:cf:bb:09:56:d5:14:fc:a0:
                    97:ff:8b:d6:48:82:6a:7e:a9:9b:50:f1:3f:35:84:
                    de:c5:4a:48:69:7a:30:0a:e2:d2:e4:f8:95:1e:67:
                    a6:b8:fa:2a:cb:4c:b1:bb:29:7f:35:87:21:79:9e:
                    e5:be:a8:9e:cf:cb:db:48:34:e6:75:02:76:d9:a6:
                    49:49:be:1e:7b:3e:37:f8:ab:92:44:95:5e:72:65:
                    aa:95:dd:93:7d:23:2c:45:2f:03:06:de:67:61:a4:
                    4d:17:fa:1f:55:60:33:f3:4f:54:4c:0a:73:56:a8:
                    ee:28:4c:4a:a7:06:82:44:e2:f1:66:be:78:2c:3d:
                    b3:b6:49:18:ca:1a:fa:c2:f1:da:44:85:b6:b3:76:
                    0a:b6:bf:c9:bb:1c:09:08:29:ba:6f:78:da:e6:12:
                    8a:e3:db:0e:d9:6f:9a:03:e9:9b:e3:8a:e4:48:b8:
                    bf:73
                Exponent: 65537 (0x10001)
    Signature Algorithm: sha256WithRSAEncryption
         44:22:e4:9b:42:be:75:a0:05:84:91:48:8a:f0:74:84:cf:b0:
         6d:95:f4:f5:98:e8:e9:e5:8f:45:69:fc:f0:ea:f0:c7:e1:15:
         98:78:d5:b6:86:ef:6e:5a:47:a8:f3:87:14:32:8f:71:93:e7:
         0c:3d:80:15:d8:2c:0f:fa:a6:34:a7:9f:3c:96:c3:35:b5:55:
         ca:9e:30:d6:5a:85:20:3f:05:ce:c5:d2:ad:a8:a3:76:fd:d3:
         72:40:7d:fe:84:71:c4:9e:82:ce:8d:80:f3:34:e0:ca:21:60:
         38:33:7e:0c:06:de:ac:ba:95:31:47:08:76:93:43:17:6d:30:
         2c:64:b8:58:e7:22:50:70:cd:ab:2c:05:e0:67:b2:24:0b:64:
         fe:43:09:d3:97:2a:56:22:75:ad:bc:81:54:c1:29:3c:15:5b:
         59:81:3b:8f:f8:b9:85:55:0a:fe:80:0d:d1:56:c2:48:c3:d4:
         9c:9b:0b:10:6c:ca:9d:42:b6:a4:80:d2:b5:e8:f4:76:60:2b:
         e3:23:6c:6a:35:a7:9f:92:f0:7c:db:84:70:8b:79:ea:27:26:
         7e:e1:6c:70:3f:68:6a:39:50:b2:6e:4d:40:f0:61:a7:eb:56:
         04:71:33:d9:d9:48:9d:84:54:bd:91:d1:ec:e8:05:16:a9:d9:
         25:7f:fd:a1
vendor/bin/phpunit
PHPUnit 9.5.6 by Sebastian Bergmann and contributors.

...............................................................  63 / 145 ( 43%)
............................................................... 126 / 145 ( 86%)
...................                                             145 / 145 (100%)

Time: 00:00.153, Memory: 10.00 MB

OK (145 tests, 901 assertions)
```

如果希望静态测试，或者无`cmake`环境，希望手动进行测试，则可以提供以下6个文件(文件名需相同)，来执行`make test(=vendor/bin/phpunit)`测试用例。

```
tests/fixtures
├── mock.pkcs1.key
├── mock.pkcs1.pem
├── mock.pkcs8.key
├── mock.serial.txt
├── mock.sha256.crt
├── mock.spki.pem
```

文件名释义如下：

|文件名|含义|
|---|---|
|mock.pkcs1.key|RSA私钥`PKCS#1`格式|
|mock.pkcs1.pem|RSA公钥`PKCS#1`格式|
|mock.pkcs8.key|RSA私钥`PKCS#8`格式|
|mock.serial.txt|X509`证书序列号`，16进制格式|
|mock.sha256.crt|`X509证书`，sha256签名格式|
|mock.spki.pem|RSA公钥`SPKI`格式|

手动执行 `vendor/bin/phpunit` 即可运行已覆盖的测试用例。
