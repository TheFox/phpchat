
RELEASE_VERSION = 0.3.0-dev
RELEASE_NAME = phpchat

RM = rm -rf
MKDIR = mkdir -p
GZIP = gzip
MV = mv -i
CP = cp -rp
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
	$(RM) tests/client{1,2}
	$(RM) tests/client{1,2}_tcp

release:
	$(MKDIR) releases $(RELEASE_NAME)-$(RELEASE_VERSION)
	
	$(CP) application.php $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) bootstrap.php $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) bootstrapCommon.php $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) composer.json $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) functions.php $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) README.md $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) src $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) start.sh $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(CP) stop.sh $(RELEASE_NAME)-$(RELEASE_VERSION)
	
	cd $(RELEASE_NAME)-$(RELEASE_VERSION); curl -sS https://getcomposer.org/installer | php; ./composer.phar install --no-dev
	find $(RELEASE_NAME)-$(RELEASE_VERSION) -name .DS_Store -exec rm -v {} \;
	tar -cpzf $(RELEASE_NAME)-$(RELEASE_VERSION).tar.gz $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(MV) $(RELEASE_NAME)-$(RELEASE_VERSION).tar.gz releases
	chmod -R 777 $(RELEASE_NAME)-$(RELEASE_VERSION)
	$(RM) $(RELEASE_NAME)-$(RELEASE_VERSION)

clean:
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
	$(RM) tests/*.yml
	$(RM) tests/*.log
	$(RM) tests/*.prv tests/*.pub
	$(RM) tests/client{1,2}
	$(RM) tests/client{1,2}_tcp
