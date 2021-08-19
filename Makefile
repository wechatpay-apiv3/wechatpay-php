all: stats keygen x509crt test clean

keygen:
	openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out ./tests/fixtures/mock.pkcs8.key
	openssl rsa -in ./tests/fixtures/mock.pkcs8.key -out ./tests/fixtures/mock.pkcs1.key
	openssl rsa -in ./tests/fixtures/mock.pkcs8.key -pubout -out ./tests/fixtures/mock.spki.pem
	openssl rsa -pubin -in ./tests/fixtures/mock.spki.pem -RSAPublicKey_out -out ./tests/fixtures/mock.pkcs1.pem

x509crt:
	fixtures="./tests/fixtures/" && serial=$$(openssl rand -hex 20 | tr "[a-z]" "[A-Z]" | tee $${fixtures}mock.serial.txt) && \
	MSYS_NO_PATHCONV=1 openssl req -new -sha256 -key $${fixtures}mock.pkcs8.key \
		-subj "/C=CN/ST=Shanghai/O=WeChatPay Community/CN=WeChatPay Community CI" | \
	openssl x509 -req -sha256 -days 1 -set_serial "0x$${serial}" \
		-signkey $${fixtures}mock.pkcs8.key -clrext -out $${fixtures}mock.sha256.crt \
	&& openssl x509 -in $${fixtures}mock.sha256.crt -noout -text

stats:
	vendor/bin/phpstan analyse --no-progress

test:
	vendor/bin/phpunit

clean:
	rm -rf ./tests/fixtures/mock.*

.PHONY: all
