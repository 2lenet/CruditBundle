# Test application

`tests/apps/crudit` is a Symfony application in which the bundle is installed as a project does. It checks, at each
lot of the redesign, that what has been reworked works in a real container, in a browser, and is accessible.

- **What it has**: SQLite (`app:seed` creates the schema and 25 clients), the entity `Client` with its `CrudConfig`
  (list, show, forms), a menu with one of each kind of element (`src/Crudit/CrudMenu/AppMenuProvider.php`: links
  with icon and badge, sub-menus, category, title, header, separator, external link), empty pages for the entries.
- **PHPUnit** (`tests/apps/crudit/tests/Functional`): the layout (landmarks, skip links, menu markup, state of the
  menu read from the cookie) and the CRUD pages (list, show, create). `AppTestCase` rebuilds the database for each
  test.
- **Browser** (`tests/apps/smoke.mjs`, Puppeteer and axe-core): the menu (full, rail, drawer, filter, sub-menus,
  tooltips, cookie), keyboard and focus, right to left, light and dark, 360 to 1500 px. `EXCLUDED` lists what
  axe does not check yet because its lot is not done (header: 2.2.a, footer: 2.1.c): remove an entry when its lot is
  done.

The CI (`.github/workflows/app.yml`) runs both, in dev and prod (with `asset-map:compile`).

## A new lot

Add the case where the screen is shown (a page of the application, an entity if the lot needs data), its PHPUnit
test, its steps in `smoke.mjs` (and its page in the axe checks), and remove from `EXCLUDED` what the lot reworks.

## Run it locally

Docker, no local PHP needed (the bundle is linked, not copied, so a change is seen at once):

```bash
cd tests/apps/crudit
# a copy of composer.json that links the bundle (composer.local.json is ignored by Git)
sed 's#"url": "../../../"#"url": "/data/bundles/CruditBundle"#; s#"symlink": false#"symlink": true#' composer.json > composer.local.json
docker run --rm -u $(id -u) -e COMPOSER_HOME=/tmp/c -e COMPOSER=composer.local.json -v /data/bundles:/data/bundles \
    -w $PWD composer:2 composer update --ignore-platform-reqs --no-scripts
alias app='docker run --rm -u $(id -u) -e HOME=/tmp -e APP_SECRET=ci -v /data/bundles:/data/bundles -w $PWD php-intl php'
app bin/console ux:icons:lock && app bin/console importmap:install && app bin/console assets:install public
app bin/console app:seed
APP_ENV=test app bin/console app:seed && app vendor/bin/phpunit
docker run -d --rm --name crudit-app -u $(id -u) --network host -e HOME=/tmp -e APP_SECRET=ci \
    -v /data/bundles:/data/bundles -w $PWD php-intl php -S 127.0.0.1:8080 -t public
cd .. && npm install && node smoke.mjs http://127.0.0.1:8080/
```

Signed in, the header has the user menu: open `/login` and give `alice` / `alice` (name, role, email, can impersonate:
`/?_switch_user=bob`) or `bob` / `bob` (name only). The application lets anonymous visitors in otherwise.

`php-intl` is the image `php:8.5-cli` with the `intl` extension (see `CruditUiBundle/docs/development.md`).
After `asset-map:compile`, delete `public/assets/` to go back to the development mode.
