encore:
	./node_modules/.bin/encore prod
	cd ../../.. && bin/console assets:install

cc:
	cd ../../.. && bin/console c:c

pub:
	cd ../../.. && bin/console assets:install

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
