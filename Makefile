PORT ?= 8000
start:
	php -S 0.0.0.0:$(PORT) 	-t public

install:
	composer install

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 public src