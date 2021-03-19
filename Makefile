install:
	composer install
	npm install
	./node_modules/.bin/encore prod
	cd ../../.. && bin/console assets:install
	cd ../../.. && bin/console c:c

encore:
	./node_modules/.bin/encore prod
	cd ../../.. && bin/console assets:install

cc:
	cd ../../.. && bin/console c:c

pub:
	cd ../../.. && bin/console assets:install

debug:
	cd ../../.. && bin/console debug:router

build-asset:
	npm install
	./node_modules/.bin/encore prod
    cd ../../.. && bin/console assets:install

lintjs:
	npx eslint assets

formatjs:
	npx prettier assets/**/*{js,css} --write

testjs:
	npx jest

lint:
	./vendor/bin/phpcs
	./vendor/bin/phpstan analyse -c tests/phpstan.neon

format:
	./vendor/bin/phpcbf
