.PHONY: build update validate autoload reset coveralls

build: update

update:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Update project \033[0m"
	@composer update --optimize-autoloader --no-interaction
	@echo ""

validate:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Composer validate \033[0m"
	@composer validate --no-interaction
	@echo ""

autoload:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Composer autoload \033[0m"
	@composer dump-autoload --optimize --no-interaction
	@echo ""

reset:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Hard reset \033[0m"
	@git reset --hard

clean:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Hard reset \033[0m"
	@rm -fr     ./build
	@mkdir -vp  ./build
	@rm -fr     ./vendor
	@rm -vf     ./composer.lock

coveralls:
	@echo "\033[0;33m>>> >>> >>> >>> >>> >>> >>> >>> \033[0;30;46m Send coverage to coveralls.io \033[0m"
	@echo "Process Completed sucessfully"
	@echo ""
