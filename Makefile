
RM = rm -rf
MKDIR = mkdir -p
GZIP = gzip
MV = mv -i
CP = cp -rp
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit


.PHONY: all install tests test_phpcs test_phpunit test_clean release clean

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
	make test_clean

test_clean:
	$(RM) tests/testdir_*
	$(RM) tests/testfile_*
	$(RM) tests/bucket_*.yml
	$(RM) tests/node_*.yml

release:
	./release.sh

clean: test_clean
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
