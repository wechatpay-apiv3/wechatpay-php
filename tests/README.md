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
            2a:0d:4e:bf:bd:8d:e2:44:d3:ae:97:1a:a3:d7:a1:3a:e7:61:5b:03
    Signature Algorithm: sha256WithRSAEncryption
        Issuer: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Validity
            Not Before: Sep  2 11:09:29 2021 GMT
            Not After : Sep  3 11:09:29 2021 GMT
        Subject: C=CN, ST=Shanghai, O=WeChatPay Community, CN=WeChatPay Community CI
        Subject Public Key Info:
            Public Key Algorithm: rsaEncryption
                Public-Key: (2048 bit)
                Modulus:
                    00:c9:da:cc:db:92:df:1c:6f:16:9c:44:11:27:dc:
                    19:d2:93:32:72:92:41:52:5e:f5:c7:49:a2:8a:90:
                    95:cf:6a:16:6c:89:af:46:35:e3:c6:da:ee:14:76:
                    07:d8:23:c5:35:ac:39:be:69:ca:b4:3d:4e:cc:d3:
                    0d:ea:f6:38:cc:8c:8f:1c:38:58:ed:93:3f:06:83:
                    48:e0:ab:e0:e0:a8:d4:8a:6c:1f:96:34:28:4f:21:
                    59:a6:95:84:14:c9:e0:74:66:b6:5e:d6:0b:56:93:
                    a4:6f:02:eb:4d:d2:1c:84:f8:43:1a:87:04:46:eb:
                    e7:12:81:05:a7:13:a0:11:79:18:82:2c:dd:22:62:
                    f1:b3:67:70:fb:08:78:33:55:36:b2:6d:2b:01:79:
                    bf:69:ed:36:69:fc:a8:16:9f:6f:5f:69:53:e2:25:
                    ff:63:bd:76:be:b5:da:1f:bc:42:8e:ec:4a:6e:f5:
                    99:24:ba:1d:f2:a1:27:76:54:b8:e6:5d:6b:5d:5f:
                    65:a9:23:53:f0:1b:4f:3c:8a:0e:84:65:74:85:65:
                    3e:91:d6:b2:13:1b:b8:91:13:c1:7b:9f:81:71:48:
                    fb:75:ce:47:6c:38:d9:45:15:2c:b2:8c:12:4e:57:
                    29:68:f4:ab:d1:88:89:15:ab:93:65:54:33:49:e7:
                    05:41
                Exponent: 65537 (0x10001)
    Signature Algorithm: sha256WithRSAEncryption
         05:30:8a:25:26:8d:9a:75:06:f1:4d:af:e6:69:71:ec:c5:a8:
         61:92:71:ab:1f:aa:ca:f6:cc:75:7f:1c:cd:bf:6f:fc:5f:46:
         5c:72:db:cf:67:79:cc:a8:62:a3:3c:d4:be:2a:e9:58:d6:da:
         9e:28:a8:7f:b5:d4:62:74:67:c1:9d:4a:5c:38:a7:52:48:04:
         d4:b8:ce:87:bb:31:fa:f4:b8:9c:24:df:88:48:10:0a:e7:71:
         e8:17:80:dc:1d:dc:14:36:13:2d:6e:3b:6a:e5:15:f3:f3:f9:
         13:8e:f2:1e:28:5e:70:1e:04:b4:f5:35:e9:ec:9b:b9:bc:1b:
         a5:37:86:e3:9d:7f:e7:d0:d7:e7:9b:68:7e:7e:9f:8e:6c:00:
         63:96:25:8d:d5:c4:a6:67:e4:78:d9:22:67:d3:1c:8d:7e:a9:
         a6:af:0b:e9:fd:d2:ae:e2:d4:0b:87:dc:38:fe:4d:ca:cf:fa:
         8a:45:04:56:8d:c6:55:da:b9:1d:33:cb:89:8d:eb:f8:01:12:
         de:62:05:a1:f4:a1:49:f8:3b:e8:39:bf:eb:2c:9d:0a:c1:3b:
         f4:a2:62:13:db:2c:b0:67:be:70:e2:69:1d:fd:b0:9c:9f:e0:
         52:5a:df:d5:a6:1a:82:13:f1:00:da:70:32:b4:43:95:33:1f:
         f8:8d:63:54
vendor/bin/phpunit
PHPUnit 9.5.8 by Sebastian Bergmann and contributors.

...............................................................  63 / 480 ( 13%)
............................................................... 126 / 480 ( 26%)
............................................................... 189 / 480 ( 39%)
............................................................... 252 / 480 ( 52%)
............................................................... 315 / 480 ( 65%)
............................................................... 378 / 480 ( 78%)
............................................................... 441 / 480 ( 91%)
.......................................                         480 / 480 (100%)

Time: 00:00.590, Memory: 12.00 MB

OK (480 tests, 2145 assertions)
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
