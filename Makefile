encore:
	./node_modules/.bin/encore prod
	cd ../../.. && bin/console assets:install

pub:
	pwd
	cd ../../.. && bin/console assets:install

build-asset:
	npm install
	./node_modules/.bin/encore prod
    cd ../../.. && bin/console assets:install
