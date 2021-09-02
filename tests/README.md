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
fixtures="./tests/fixtures/" && serial=$(openssl rand -hex 20 | awk '{ if (match($0,/^00/)) s="01"substr($0,3,length($0)); else s=$0; print toupper(s) }' | tee ${fixtures}mock.serial.txt) && \
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
            a4:e9:56:1e:40:44:8f:a3:1f:10:35:21:65:b4:f1:cc:70:fd:32:be
    Signature Algorithm: sha256WithRSAEncryption
        Issuer: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Validity
            Not Before: Sep  2 02:34:19 2021 GMT
            Not After : Sep  3 02:34:19 2021 GMT
        Subject: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Subject Public Key Info:
            Public Key Algorithm: rsaEncryption
                Public-Key: (2048 bit)
                Modulus:
                    00:d7:1b:ac:64:38:cd:cc:58:df:29:f8:6b:e6:42:
                    f4:f5:ce:eb:ed:59:5e:e5:0c:44:c4:c8:16:6e:10:
                    24:b4:a2:63:a4:4e:74:46:fa:bd:9f:b3:d3:e5:e4:
                    0d:f0:0d:1f:9e:30:23:a6:04:d4:db:60:01:1e:e7:
                    60:81:ab:00:b6:7a:2d:91:f7:a4:d6:67:c5:8a:e4:
                    94:7c:f3:6e:34:51:f5:64:aa:40:50:d9:38:16:88:
                    b0:a3:bc:10:92:f7:27:2c:38:b9:e9:db:8c:b3:85:
                    ac:6e:56:27:e0:48:92:20:5a:23:91:91:ca:57:16:
                    5a:91:0a:83:1c:c7:fb:f2:9d:0b:96:e2:ea:52:9c:
                    4b:e0:2c:e4:e7:b8:71:b4:40:82:2f:48:3b:3c:d6:
                    f0:1c:29:4a:19:55:6b:28:44:a7:79:c7:16:c8:0a:
                    51:37:dd:01:9b:4a:40:8c:4c:25:08:88:ba:fa:19:
                    3d:ce:0b:8e:4a:7a:ed:a2:25:ff:a0:97:92:9e:40:
                    17:a0:af:41:de:fa:42:26:eb:ec:b7:f3:b0:7a:c8:
                    db:18:f0:68:cd:03:b9:4a:24:74:48:c4:46:dd:28:
                    6a:5d:87:2b:a7:46:02:1b:ce:d4:fa:7e:23:19:58:
                    71:6d:c8:cc:d0:4a:3e:1e:1b:80:2b:5f:07:5e:14:
                    83:53
                Exponent: 65537 (0x10001)
    Signature Algorithm: sha256WithRSAEncryption
         4e:e9:eb:92:f8:d5:b3:d8:43:e1:be:10:40:68:a4:5c:fa:e4:
         04:d8:ae:8c:00:de:98:c7:97:8a:81:dd:33:df:1f:28:bc:4e:
         b7:ec:f7:35:10:90:1f:d0:ff:3a:4a:ca:1e:b4:cd:84:9c:30:
         f2:e7:a4:4f:6c:c5:c1:ae:7c:46:a1:3f:df:62:36:74:04:4d:
         9a:12:51:1c:4d:57:85:fa:77:47:e2:5d:75:75:34:36:26:e2:
         d2:de:11:15:f4:48:8e:ec:5e:14:1c:6e:a4:72:81:0c:1d:4f:
         b4:21:37:de:59:29:9f:e3:e7:2f:08:30:ff:31:cc:13:0d:ec:
         a9:94:32:19:8e:c6:95:25:2c:f6:d0:d1:d2:22:a0:37:dd:92:
         76:9f:8a:8c:e1:15:0f:36:fb:80:2d:21:2c:be:1b:31:41:93:
         7e:ca:98:8c:f1:1b:5e:1d:1c:e0:54:77:45:a4:d6:81:e2:ae:
         f5:6c:8f:71:fc:42:87:5e:3d:3f:74:27:37:b9:6d:17:31:63:
         c5:70:c0:e7:ab:8f:10:f1:76:9c:cd:6a:0d:90:39:db:43:0f:
         d7:3a:92:b4:72:d4:3f:50:2e:9f:3f:20:a4:ea:57:0b:ae:29:
         1b:a7:31:de:1f:e9:92:ed:09:a5:3d:84:ef:64:0b:3d:59:d7:
         17:21:a5:94
vendor/bin/phpunit
PHPUnit 9.5.8 by Sebastian Bergmann and contributors.

...............................................................  63 / 471 ( 13%)
............................................................... 126 / 471 ( 26%)
............................................................... 189 / 471 ( 40%)
............................................................... 252 / 471 ( 53%)
............................................................... 315 / 471 ( 66%)
............................................................... 378 / 471 ( 80%)
............................................................... 441 / 471 ( 93%)
..............................                                  471 / 471 (100%)

Time: 00:00.582, Memory: 12.00 MB

OK (471 tests, 2113 assertions)
rm -rf ./tests/fixtures/mock.*
```

如果希望静态测试，或者无`make`环境，希望手动进行测试，则可以提供以下6个文件(文件名需相同)，来代替测试用例准备工作。

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
