install:
	composer update

test:
	vendor/bin/phpunit
	vendor/bin/phpcs

fix:
	vendor/bin/phpcbf
