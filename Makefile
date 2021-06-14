install:
	composer update
	composer bin all update

test:
	vendor/bin/phpunit
	vendor/bin/phpstan analyze
