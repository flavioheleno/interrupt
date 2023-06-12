.DEFAULT_GOAL := help

composer.lock:
	@composer validate --strict
	@composer update

install: composer.json composer.lock  ## Install PHP dependencies

update: composer.json ## Update PHP dependencies
	@composer update -W

lint: install ## Run php code linter
	@./vendor/bin/parallel-lint -j $(shell nproc) --exclude ./vendor .

phpcs: install ruleset.xml ## Run phpcs coding standards check
	@./vendor/bin/phpcs --standard=./ruleset.xml ./src ./tests

phpcbf: install ruleset.xml ## Run phpcbf coding standards fixer
	@./vendor/bin/phpcbf --standard=./ruleset.xml ./src ./tests

phpstan: install ## Run phpstan static code analysis
	@./vendor/bin/phpstan analyse --level=max --autoload-file=./vendor/autoload.php ./src

phpunit: install ## Run phpunit test suite
	@./vendor/bin/phpunit ./tests

coverage: install ## Generate code-coverage
	@php -dopcache.enable=0 -dpcov.enabled=1 ./vendor/bin/phpunit ./tests/Unit/ --coverage-html ./coverage

help: ## Show this help
	@printf "\033[37mUsage:\033[0m\n"
	@printf "  \033[37mmake [target]\033[0m\n\n"
	@printf "\033[34mAvailable targets:\033[0m\n"
	@grep -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[0;36m%-12s\033[m %s\n", $$1, $$2}'
	@printf "\n"
.PHONY: help
