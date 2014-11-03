
RM = rm -rf
CHMOD = chmod
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit
COMPOSER_PREFER_SOURCE := $(shell echo $(COMPOSER_PREFER_SOURCE))


.PHONY: all install test test_phpcs test_phpunit test_clean release clean clean_nodes clean_data clean_all

all: install test

install: composer.phar
	./composer.phar install $(COMPOSER_PREFER_SOURCE) --no-interaction --dev

update: composer.phar
	./composer.phar selfupdate
	./composer.phar update

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 755 ./composer.phar

$(PHPCS): composer.phar

test: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s --report=full --report-width=160 --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	$(PHPUNIT)
	make test_clean

test_clean:
	$(RM) tests/testdir_*
	$(RM) tests/testfile_*
	$(RM) tests/*.yml

release: release.sh
	./release.sh

clean: test_clean
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor

clean_nodes:
	$(RM) data/table.yml
	$(RM) data/node_*.yml
	$(RM) data/nodesnewdb.yml
	$(RM) data/addressbook.yml

clean_data:
	$(RM) data/*
	$(RM) data

clean_all: clean clean_data
	$(CHMOD) 600 id_rsa.prv id_rsa.pub
	$(RM) id_rsa.prv id_rsa.pub
	$(RM) settings.yml
