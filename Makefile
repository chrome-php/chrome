install:
	composer update
	composer bin all update

test:
	vendor/bin/phpunit
	vendor/bin/phpstan analyze
	vendor/bin/phpcs

fix:
	vendor/bin/phpcbf
