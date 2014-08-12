
RELEASE_VERSION = 0.3.0-dev
RELEASE_NAME = phpchat

RM = rm -rf
MKDIR = mkdir -p
TAR = tar
GZIP = gzip
MV = mv -i
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit


.PHONY: all install tests test_phpcs test_phpunit release clean

all: install tests

install: composer.phar

update: composer.phar
	./composer.phar selfupdate
	./composer.phar update
	php bootstrap.php

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	./composer.phar install
	php bootstrap.php

$(PHPCS): composer.phar

tests: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s --report=full --report-width=160 --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	$(PHPUNIT)
	$(RM) tests/*.yml
	$(RM) tests/*.log
	$(RM) tests/*.prv tests/*.pub

release:
	find . -name .DS_Store -exec rm {} \;
	$(MKDIR) releases
	$(TAR) -cpf $(RELEASE_NAME)-$(RELEASE_VERSION).tar \
		README.md \
		application.php \
		composer.json \
		bootstrap.php \
		functions.php \
		src \
		vendor/autoload.php \
		vendor/composer \
		vendor/liip \
		vendor/rhumsaa \
		vendor/sebastian \
		vendor/symfony \
		vendor/thefox
	$(GZIP) -9 -f $(RELEASE_NAME)-$(RELEASE_VERSION).tar
	$(MV) ${RELEASE_NAME}-${RELEASE_VERSION}.tar.gz releases

clean:
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
	$(RM) tests/*.yml
	$(RM) tests/*.log
	$(RM) tests/*.prv tests/*.pub
