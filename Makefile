.PHONY: reinstall test test-sw svn lint autofix

PLUGIN_DIR = wp-offline-content

WP_CLI = tools/wp-cli.phar
WP_CODE_STANDARDS = tools/wpcs
PHPUNIT = tools/phpunit.phar
PHPCS = tools/phpcs.phar
PHPCBF = tools/phpcbf.phar
PLUGIN_ZIP = $(PLUGIN_DIR).zip
CODE_STANDARD = ./code-guidelines.xml
LINTER_IGNORE = vendor

reinstall: $(WP_CLI)
	$(WP_CLI) plugin uninstall --deactivate $(PLUGIN_DIR) --path=$(WORDPRESS_PATH)
	rm -f $(PLUGIN_ZIP)
	zip $(PLUGIN_ZIP) -r $(PLUGIN_DIR)
	$(WP_CLI) plugin install --activate $(PLUGIN_ZIP) --path=$(WORDPRESS_PATH)

svn:
	@echo "Copying $(PLUGIN_DIR) contents to svn/trunk"
	@rsync -a --delete $(PLUGIN_DIR)/* svn/trunk
	@echo "Removing .git repositories from bundle"
	@find svn/trunk \( -name ".git" \) -prune -exec rm -rf {} \;

ci-lint: configure_linter $(WP_CODE_STANDARDS)
	$(PHPCS) -i
	$(PHPCS) --standard=$(CODE_STANDARD) --extensions=php -n $(PLUGIN_DIR)

lint: configure_linter $(WP_CODE_STANDARDS)
	$(PHPCS) -i
	$(PHPCS) --standard=$(CODE_STANDARD) --extensions=php --colors $(PLUGIN_DIR)

autofix: configure_linter $(PHPCBF) $(WP_CODE_STANDARDS)
	$(PHPCBF) -i
	$(PHPCBF) --standard=$(CODE_STANDARD) --extensions=php $(PLUGIN_DIR)


configure_linter: $(PHPCS)
	$(PHPCS) --config-set installed_paths $(WP_CODE_STANDARDS)

test: $(PHPUNIT)
	$(PHPUNIT)

test-sw: node_modules
	$(NODE) node_modules/karma/bin/karma start karma.conf

node_modules:
	npm install

tools/wp-cli.phar:
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

tools/wpcs:
	git clone -b master https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git $(WP_CODE_STANDARDS)

tools/phpunit.phar:
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar tools/phpunit.phar
	chmod +x $(PHPUNIT)

tools/phpcs.phar:
	mkdir -p tools
	wget -P tools -N https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
	chmod +x $(PHPCS)

tools/phpcbf.phar:
	mkdir -p tools
	wget -P tools -N https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar
	chmod +x $(PHPCBF)
