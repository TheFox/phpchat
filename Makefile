
RM = rm -rf
CHMOD = chmod
MKDIR = mkdir -p
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit
PHPDOX = vendor/bin/phpdox
PHPLOC = vendor/bin/phploc
COMPOSER = ./composer.phar


.PHONY: all install test test_phpcs test_phpunit test_phpunit_cc test_clean release docs build clean clean_nodes clean_data clean_release clean_all

all: install test

install: $(COMPOSER)
	$(COMPOSER) install $(COMPOSER_PREFER_SOURCE) --no-interaction --dev

update: $(COMPOSER)
	$(COMPOSER) selfupdate
	$(COMPOSER) update

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 755 $(COMPOSER)

$(PHPCS): $(COMPOSER)

test: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s -p --report=full --report-width=160 --report-xml=build/logs/phpcs.xml --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests bootstrap.php

test_phpunit: $(PHPUNIT) phpunit.xml
	mkdir -p test_data
	TEST=true $(PHPUNIT) $(PHPUNIT_COVERAGE_HTML) $(PHPUNIT_COVERAGE_XML) $(PHPUNIT_COVERAGE_CLOVER)
	$(MAKE) test_clean

test_phpunit_cc:
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_HTML="--coverage-html build/report"

test_clean:
	$(RM) test_data

release: release.sh
	./release.sh

docs:
	$(MKDIR) build
	$(MKDIR) build/logs
	$(CHMOD) 0700 build
	#$(MAKE) test_phpcs
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_XML="--coverage-xml build/coverage"
	$(PHPLOC) --count-tests --progress --log-xml=build/logs/phploc.xml src
	$(PHPDOX)

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

clean_release: clean_data
	$(RM) composer.lock composer.phar
	$(RM) build log pid

clean_all: clean clean_data
	$(CHMOD) 600 id_rsa.prv id_rsa.pub
	$(RM) id_rsa.prv id_rsa.pub
	$(RM) settings.yml
	$(RM) build
