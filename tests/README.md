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
openssl rand -hex 16 -out ./tests/fixtures/mock.pwd.txt
openssl pkcs8 -in ./tests/fixtures/mock.pkcs8.key -passout file:./tests/fixtures/mock.pwd.txt -topk8 -out ./tests/fixtures/mock.encrypted.pkcs8.key
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
            71:32:d7:2a:03:e9:3c:dd:f3:e2:5b:87:68:c0:3b:bd:1f:37:ee:df
    Signature Algorithm: sha256WithRSAEncryption
        Issuer: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Validity
            Not Before: Sep  3 07:01:04 2021 GMT
            Not After : Sep  4 07:01:04 2021 GMT
        Subject: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Subject Public Key Info:
            Public Key Algorithm: rsaEncryption
                Public-Key: (2048 bit)
                Modulus:
                    00:b8:44:6f:fa:cc:4d:38:99:f9:35:26:23:17:d4:
                    9c:b5:61:f1:bb:64:6e:4f:b5:7c:04:49:91:56:c6:
                    63:7a:cd:4e:a0:fe:5b:bf:b2:ba:32:67:c6:c9:65:
                    ca:80:9a:16:b1:06:0e:4b:f5:92:47:5f:a5:52:b1:
                    a0:b7:19:65:4d:c9:49:a6:7a:41:f1:af:a5:01:f0:
                    26:3b:4a:0b:37:3d:5e:7a:03:5f:a3:06:aa:a2:e3:
                    ad:ad:0d:08:1b:c0:5a:7d:02:16:92:7b:9b:c3:04:
                    9b:3f:7a:91:8c:7c:2a:72:a4:51:43:55:b0:53:29:
                    01:0f:54:36:71:db:0e:ca:46:05:3c:14:25:83:9f:
                    b7:89:6e:42:0e:d9:a9:c0:95:fa:52:89:61:64:02:
                    c5:ec:9c:c7:01:bb:bf:a1:4d:c1:f2:41:92:38:cb:
                    52:f4:ff:aa:f0:b8:0c:0e:f0:ac:ec:2f:f8:07:35:
                    05:21:1d:67:ea:8b:92:70:15:6d:6b:2e:59:65:89:
                    5b:a0:75:ce:02:cb:d4:db:c2:dd:01:3d:07:d0:51:
                    bf:0f:03:f1:8a:dd:23:72:92:22:5a:29:f2:04:a7:
                    85:70:cb:9a:18:85:98:a1:aa:b9:d6:e5:55:01:b1:
                    47:90:8b:de:68:d8:55:45:6a:17:b3:c3:40:1d:cf:
                    38:87
                Exponent: 65537 (0x10001)
    Signature Algorithm: sha256WithRSAEncryption
         24:cd:eb:63:20:92:05:67:3a:eb:04:9b:89:fb:6b:3c:3b:d3:
         6c:1e:6c:c4:5a:1f:ac:4d:0a:1a:49:2f:35:86:95:d0:95:dd:
         f2:1c:53:a0:5f:09:eb:18:c0:be:8e:69:6e:0b:21:d8:a5:2b:
         cf:ea:e6:01:2a:d0:e6:c4:e7:14:77:27:c9:92:89:c6:b5:58:
         74:e2:02:be:e4:76:32:45:99:0a:d0:93:65:1c:29:2f:48:ad:
         39:34:b5:af:98:74:d9:51:b5:17:07:9a:44:48:86:83:82:7f:
         19:ad:da:76:bf:3b:45:f4:38:a8:19:2d:d8:83:ba:f9:67:3e:
         aa:e7:da:09:ed:c1:c3:bc:dd:48:31:63:3c:42:42:38:02:1a:
         da:cb:5d:a8:c8:36:01:bb:82:c6:3e:e0:8e:da:78:3f:8f:94:
         d3:c9:7e:5d:08:95:fe:d5:3e:fa:9a:2c:ca:fb:4a:d9:3a:12:
         0d:e7:32:5b:d8:cf:bc:af:e2:50:d4:7b:30:fe:c4:92:6c:57:
         3a:42:91:d7:bd:be:1d:d5:7c:d1:5c:29:7c:f2:8f:2b:3b:81:
         44:21:f3:84:b2:e1:e0:10:02:83:7e:20:29:f3:5c:92:19:2b:
         0d:64:59:0a:d7:76:ef:35:5a:66:bc:82:bf:db:5c:9c:c3:51:
         d4:5e:b7:bb
vendor/bin/phpunit
PHPUnit 9.5.9 by Sebastian Bergmann and contributors.

...............................................................  63 / 500 ( 12%)
............................................................... 126 / 500 ( 25%)
............................................................... 189 / 500 ( 37%)
............................................................... 252 / 500 ( 50%)
............................................................... 315 / 500 ( 63%)
............................................................... 378 / 500 ( 75%)
............................................................... 441 / 500 ( 88%)
...........................................................     500 / 500 (100%)

Time: 00:00.641, Memory: 12.00 MB

OK (500 tests, 2258 assertions)
rm -rf ./tests/fixtures/mock.*
```

如果希望静态测试，或者无`make`环境，希望手动进行测试，则可以提供以下8个文件(文件名需相同)，来代替测试用例准备工作。

```
tests/fixtures
├── mock.encrypted.pkcs8.key
├── mock.pkcs1.key
├── mock.pkcs1.pem
├── mock.pkcs8.key
├── mock.pwd.txt
├── mock.serial.txt
├── mock.sha256.crt
├── mock.spki.pem
```

文件名释义如下：

|文件名|含义|
|---|---|
|mock.encrypted.pkcs8.key|RSA私钥`PKCS#8`加密格式|
|mock.pkcs1.key|RSA私钥`PKCS#1`格式|
|mock.pkcs1.pem|RSA公钥`PKCS#1`格式|
|mock.pkcs8.key|RSA私钥`PKCS#8`格式|
|mock.pwd.txt|RSA私钥`PKCS#8`加密格式的密码|
|mock.serial.txt|X509`证书序列号`，16进制格式|
|mock.sha256.crt|`X509证书`，sha256签名格式|
|mock.spki.pem|RSA公钥`SPKI`格式|

手动执行 `vendor/bin/phpunit` 即可运行已覆盖的测试用例。
