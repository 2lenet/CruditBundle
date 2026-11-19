# Test applications

`tests/apps/` holds one Symfony application per way a project delivers the assets. In each one the bundle is installed
as a project does. They check, at each lot of the redesign, that what has been reworked works in a real container, in a
browser, and is accessible.

| Application | Assets | Tests |
|---|---|---|
| `crudit` | AssetMapper | PHPUnit, `smoke.mjs` |
| `encore` | Webpack Encore (the packages of the bundles are `file:vendor/2lenet/...`) | `smoke.mjs` |
| `turbo` | AssetMapper and Symfony UX Turbo (Turbo 8) | `smoke.mjs`, `smoke-turbo.mjs` |

`encore` and `turbo` have the code of `crudit` (`src/`, loaded by the `autoload` of their `composer.json`, `services.yaml`
and Doctrine pointing at `../crudit/src`), its templates and translations (symlinks). Only the Kernel, the configuration
and the assets are their own: a change of an entity, of a `CrudConfig` or of the menu is made once, in `crudit`.

- **What `crudit` has**: SQLite (`app:seed` creates the schema, 25 clients and 6 interventions), the entities `Client`
  and `Intervention` with their `CrudConfig` (list, show, forms, workflow), a menu with one of each kind of element
  (`src/Crudit/CrudMenu/AppMenuProvider.php`: links with icon and badge, sub-menus, category, title, header, separator,
  external link), empty pages for the entries.
- **PHPUnit** (`tests/apps/crudit/tests/Functional`): the layout (landmarks, skip links, menu markup, state of the
  menu read from the cookie), the global search (button, palette, route: the clients by name and e-mail, the screens)
  and the CRUD pages (list, show, create). `AppTestCase` rebuilds the database for each
  test.
- **Browser** (`tests/apps/smoke.mjs`, Playwright and axe-core; `browser.mjs` launches the project and keeps the few
  Puppeteer methods the test was written with): the same test runs on `chromium`, `webkit` (Safari), `iphone` (WebKit,
  touch) and `pixel` (Chrome on Android): `node smoke.mjs <url> [project]`. The menu (full, rail, drawer, filter, sub-menus,
  tooltips, cookie), keyboard and focus, right to left, light and dark, 360 to 1500 px, the list, the record, the forms,
  the workflow. `EXCLUDED` lists what axe does not check yet because its lot is not done (header: 2.2.a, footer:
  2.1.c): remove an entry when its lot is done. It runs on the three applications. It changes the data (a workflow
  transition): build the database again (`app:seed`) before running it, or `smoke-turbo.mjs`, a second time.
- **Turbo** (`tests/apps/smoke-turbo.mjs`): real Turbo visits (`smoke.mjs` only simulates `turbo:load`). Turbo
  replaces `<body>` (the controllers disconnect and connect again, the listeners on `document` and `window` stay if a
  controller does not remove them), keeps a snapshot of the page left and shows it on Back, sends the forms with
  `fetch`. The test visits the screens of Crudit and checks:
    - visits back and forth: no listener left on `document` or `window` (counted by wrappers of `addEventListener`, the DevTools protocol being Chromium only), the focus on
      the title of the page (`page-title`), after a visit and after Back;
    - the menu: the rail across the visits and Back, the drawer of a phone (the page reached is free, Back does not show
      it open), the global search (Ctrl K, a result opened by Turbo);
    - the list: the filters of the bar and of the panel, a chip, the reset, the selection (the pager, a sort, Back),
      without a full load;
    - the record: the confirmation and the modals after a visit and after Back, the focus given back;
    - the batch actions (a link, the form of an action, a free modal) answered by a redirect: the flash message as a
      toast, announced; a form sent and its redirect, Back; the workflow (a transition without a reload, the status and
      its menu after Back);
    - axe-core on the pages reached by Turbo.

  A defect it finds that is not fixed yet is a `KNOWN DEFECT` warning, not a failure (the `known` list). Today: a form sent
  with errors is answered by a 200, and Turbo refuses to show it ("Form responses must redirect to another location"):
  the page does not change, the errors are not visible. It must be a 422 (`FormFactory::bindRequest()`); the PHPUnit tests
  of the forms expect a 200.

The CI (`.github/workflows/app.yml`) runs `crudit` in dev and prod (with `asset-map:compile`), `encore` in dev
(`encore dev`, the build of every day) and prod, and `turbo` in dev and prod. There is one job per application, environment
and project (`chromium`, `webkit`, `iphone`, `pixel`), 24 in parallel: the four projects in a row took 30 to 50 minutes.
PHPUnit runs once, in the `chromium` job of `crudit`.

### Running the tests locally, with nothing to install

```bash
# the application (the bundle at the same path: vendor/ holds absolute symlinks)
docker run -d --rm --name app --network host -v /data/bundles:/data/bundles -w <this dir>/crudit -e APP_ENV=dev \
    php-intl sh -c 'php bin/console app:seed && php -S 127.0.0.1:8080 -t public'
# the browsers (the tag follows the version of playwright in package.json)
docker run --rm --network host --ipc=host -v $PWD:/work -w /work mcr.microsoft.com/playwright:v1.65.1-noble \
    node smoke.mjs http://127.0.0.1:8080/ iphone
```

Build the database again (`docker exec app php bin/console app:seed`) between two runs.

## A new lot

Add the case where the screen is shown (a page of the application, an entity if the lot needs data), its PHPUnit
test, its steps in `smoke.mjs` (and its page in the axe checks), and remove from `EXCLUDED` what the lot reworks. A
component that keeps a state in the page, a controller that listens on `document` or `window`, a form: its case in
`smoke-turbo.mjs` too.

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

`turbo` the same way (`cd tests/apps/turbo`, port 8081), then `node smoke-turbo.mjs http://127.0.0.1:8081/` after
`app bin/console app:seed`.

`encore` needs the bundle **copied**, not linked: with a link, the files of the bundle are outside the application and
Babel cannot find `core-js` from them. Without `COMPOSER=composer.local.json` (the copy takes a while: it includes the
`node_modules` of the bundle if it has been installed), then the build:

```bash
cd tests/apps/encore
docker run --rm -u $(id -u) -e COMPOSER_HOME=/tmp/c -v /data/bundles:/data/bundles -w $PWD composer:2 composer install --ignore-platform-reqs --no-scripts
app bin/console ux:icons:lock && app bin/console assets:install public && app bin/console app:seed
docker run --rm -u $(id -u) -e HOME=/tmp -v /data/bundles:/data/bundles -w $PWD node:22 sh -c 'npm install && npm run build'
# a change of the bundle: composer update 2lenet/crudit-bundle again, then the build
docker run -d --rm --name crudit-encore -u $(id -u) --network host -e HOME=/tmp -e APP_SECRET=ci \
    -v /data/bundles:/data/bundles -w $PWD php-intl php -S 127.0.0.1:8082 -t public
cd .. && node smoke.mjs http://127.0.0.1:8082/
```

`@symfony/stimulus-bridge` 3.x breaks with webpack 5.112 ("Could not retrieve module factory for type
LoaderDependency"): `encore` uses stimulus-bridge 4 and a free webpack. A project that keeps stimulus-bridge 3 must
pin webpack to 5.111 or update the bridge.

Signed in, the header has the user menu: open `/login` and give `alice` / `alice` (name, role, email, can impersonate:
`/?_switch_user=bob`) or `bob` / `bob` (name only). The application lets anonymous visitors in otherwise.

`php-intl` is the image `php:8.5-cli` with the `intl` extension (see `CruditUiBundle/docs/development.md`).
After `asset-map:compile`, delete `public/assets/` to go back to the development mode.
