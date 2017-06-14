
RM = rm -rf
MKDIR = mkdir -p
GZIP = gzip
MV = mv -i
CP = cp -rp
CHMOD = chmod
MKDIR = mkdir -p
VENDOR = vendor
PHPCS = vendor/bin/phpcs
PHPCS_STANDARD = vendor/thefox/phpcsrs/Standards/TheFox
PHPCS_REPORT = --report=full --report-width=160 $(PHPCS_REPORT_XML)
PHPUNIT = vendor/bin/phpunit
PHPDOX = vendor/bin/phpdox
PHPLOC = vendor/bin/phploc
PHPMD = vendor/bin/phpmd
COMPOSER = ./composer.phar
COMPOSER_DEV ?= 
COMPOSER_INTERACTION ?= --no-interaction
COMPOSER_PREFER_SOURCE ?= 
SECURITY_CHECKER = vendor/bin/security-checker


.PHONY: all update install_release release test test_phpcs test_phpunit test_phpunit_cc test_security test_phpmd test_clean clean clean_nodes clean_data clean_release clean_all docs

all: install test_phpunit

install: $(VENDOR)
	$(CHMOD) 700 ./application.php

install_release: $(COMPOSER)
	./composer.phar selfupdate
	$(MAKE) install COMPOSER_DEV=--no-dev
	php bootstrap.php

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 700 ./composer.phar
	./composer.phar install
	php bootstrap.php

release: release.sh
	./release.sh

test: test_phpcs test_phpunit test_security
	
test_phpcs: $(PHPCS) $(PHPCS_STANDARD)
	$(PHPCS) -v -s -p $(PHPCS_REPORT) --standard=$(PHPCS_STANDARD) src tests bootstrap.php

test_phpunit: $(PHPUNIT) phpunit.xml test_data
	TEST=true $(PHPUNIT) $(PHPUNIT_COVERAGE_HTML) $(PHPUNIT_COVERAGE_XML) $(PHPUNIT_COVERAGE_CLOVER)
	$(MAKE) test_clean

test_phpunit_cc: build
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_HTML="--coverage-html build/report"

test_clean:
	$(RM) tests/testdir_*
	$(RM) tests/testfile_*
	$(RM) tests/*.yml

test_phpmd:
	$(PHPMD) src,tests text phpmd.xml

test_clean:
	$(RM) test_data

clean: test_clean
	$(RM) composer.lock $(COMPOSER)
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
	$(CHMOD) a-rwx,u+rw id_rsa.prv id_rsa.pub
	$(RM) id_rsa.prv id_rsa.pub
	$(RM) settings.yml
	$(RM) composer.lock $(COMPOSER)
	$(RM) log pid

clean_all: clean clean_data clean_release

docs: build test_phpcs
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_XML="--coverage-xml build/coverage"
	$(PHPLOC) --count-tests --progress --log-xml=build/logs/phploc.xml src
	$(PHPDOX)

$(VENDOR): $(COMPOSER)
	$(COMPOSER) install $(COMPOSER_PREFER_SOURCE) $(COMPOSER_INTERACTION) $(COMPOSER_DEV)

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) u=rwx,go=rx $(COMPOSER)

$(PHPCS): $(VENDOR)

$(PHPUNIT): $(VENDOR)

$(SECURITY_CHECKER): $(VENDOR)

test_data:
	$(MKDIR) test_data

build:
	$(MKDIR) build
	$(MKDIR) build/logs
	$(CHMOD) u=rwx,go-rwx build
