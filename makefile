# makefile

.PHONY: all vendor phpunit

all:
	$(MAKE) clean
	$(MAKE) vendor
	$(MAKE) tools-parallel
	$(MAKE) phpunit-ci
	$(MAKE) phpcb

# Parallel task
tools-parallel: tools-sequential phpcpd phpcs phploc lint

# Sequential task
tools-sequential:
	$(MAKE) pdepend
	$(MAKE) phpmd

clean:
	rm -rf build/api/*
	rm -rf build/code-browser
	rm -rf build/coverage
	rm -rf build/logs
	rm -rf build/pdepend
	rm -rf app/cache/*
	rm -rf app/logs/test.log
	mkdir -p build/api
	mkdir -p build/code-browser
	mkdir -p build/coverage
	mkdir -p build/logs
	mkdir -p build/pdepend

vendor:
	rm -rf app/cache/dev
	composer install
	$(MAKE) dump-autoload

dump-autoload:
	composer dump-autoload --optimize

cache-clear:
	app/console cache:clear --env=prod --no-debug

# Perform syntax check of sourcecode files
lint:
	find src -name "*.php" -print0 |\
		xargs -0 -n1 -P8 php -l |\
		grep -v 'No syntax errors' || echo 'No syntax errors'

phpunit:
	rm -rf app/cache/test/test*.db
	bin/phpunit -c app

# Generates junit.xml and clover.xml
phpunit-ci:
	bin/phpunit -c app\
		--log-junit build/logs/junit.xml\
		--coverage-clover build/logs/clover.xml\
		--coverage-html build/coverage/

# Generate jdepend.xml and software metrics charts using PHP_Depend
pdepend:
	bin/pdepend\
		--jdepend-xml=build/logs/jdepend.xml\
		--jdepend-chart=build/pdepend/dependencies.svg\
		--overview-pyramid=build/pdepend/overview-pyramid.svg\
		src

# Generate pmd.xml using PHPMD (PHP Mess Detector)
phpmd:
	bin/phpmd src xml phpmd.xml --reportfile build/logs/pmd.xml || echo 'done'

# Generate pmd-cpd.xml using PHPCPD (PHP Copy/Paste Detector)
phpcpd:
	php -d memory_limit=640M bin/phpcpd\
		--quiet\
		--log-pmd build/logs/pmd-cpd.xml\
		src || echo 'done'

# Generate phploc.csv using phploc (PHP Lines of code)
phploc:
	bin/phploc --log-csv build/logs/phploc.csv src

# Generate checkstyle.xml using PHP_CodeSniffer
phpcs:
	bin/phpcs -d memory_limit=256M\
		--report=checkstyle\
		--report-file=build/logs/checkstyle.xml\
		--standard=PSR2\
		src || echo 'done'

# Aggregate tool output with PHP_CodeBrowser
phpcb:
	bin/phpcb\
		--log build/logs\
		--source src\
		--output build/code-browser

