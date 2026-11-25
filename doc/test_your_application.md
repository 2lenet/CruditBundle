# Test your application

Crudit gives you some helper traits in order to test your app.

## Navigation test

A navigation test is provided. The test will navigate on every crud that appear in the menu.
To enable the test create a new class in the tests directory :

```php
#/tests/NavigationTest.php

<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Lle\CruditBundle\Test\TestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NavigationTest extends WebTestCase
{
    use TestHelperTrait;

    public const EXCLUDED_ROUTES = ['hermes'];
    public const LOGIN_USER = 'admin@app.fr';
    public const USER_REPOSITORY = UserRepository::class;
}

```

There is 3 constants to setup in order to configure the test :

- `EXCLUDED_ROUTES` The test will not access these routes.
- `LOGIN_USER` The user to be used in the tests. Make sure that this user has the rights to access every pages. 
- `USER_REPOSITORY` The repository that manages users in the app.

## Route security test

The route security test lists every route that anyone can reach, or that only requires `ROLE_USER`
without any right check, and fails as soon as it finds one. There is no baseline: the goal is that
no route stays exposed by accident.

It runs three tests:

- `testEveryRouteIsProtected` (static analysis, no HTTP request, so route parameters are not an issue);
- `testExclusionsAreStillUsed` (fails when an exclusion no longer excuses anything, so lists cannot rot);
- `testAnonymousIsDeniedEverywhere` (sends one anonymous request per route and HTTP method: anything other
  than 401, 403 or a redirect to the login page is reported. The firewall answers before arguments are
  resolved, so parameters are not an issue here either).

A route is considered protected, for every HTTP method it accepts, when one of these is true:

- the `access_control` rule matching its path requires something other than `PUBLIC_ACCESS`, `ROLE_USER`
  or `IS_AUTHENTICATED_*`, and a firewall covers the path (a `security: false` firewall is reported);
- the controller method, its class or a parent class carries `#[IsGranted]` with a real role;
- the method body (or a method of the same class it calls through `$this->`) calls `denyAccessUnlessGranted`
  or `denyUnlessAccessGranted`, or calls `isGranted` **and** denies access (`throw`,
  `createAccessDeniedException`, `HTTP_FORBIDDEN`, `403`). A check that is commented out does not count,
  and neither does a check against `ROLE_USER` alone.

The sample path used for the requests takes route defaults first, then the first alternative of a
requirement (`fr|en` gives `fr`), then `1`.

```php
#/tests/RouteSecurityTest.php

<?php

namespace App\Tests;

use Lle\CruditBundle\Test\RouteSecurityTestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RouteSecurityTest extends WebTestCase
{
    use RouteSecurityTestHelperTrait;

    // Right check done elsewhere (service, listener...): say why.
    public const EXCLUDED_ROUTES = ['app_api_checked_by_listener_*'];
    public const EXCLUDED_CONTROLLERS = ['Lle\\HermesBundle\\Controller\\*'];

    // Public on purpose. Only excuses a route that really is public.
    public const PUBLIC_ROUTES = ['login', 'logout_oauth'];

    // Open to any logged in user. Only excuses a route that really is ROLE_USER only.
    public const ROLE_USER_ROUTES = ['app_profile'];
}
```

All constants are optional and accept `fnmatch` globs:

- `EXCLUDED_ROUTES` matches the route name, `EXCLUDED_CONTROLLERS` the fully qualified controller class name.
- `PUBLIC_ROUTES` and `ROLE_USER_ROUTES` are typed on purpose: if a route listed in `ROLE_USER_ROUTES` becomes
  public, the test fails instead of staying silent.
- `ANONYMOUS_EXCLUDED_ROUTES` skips the anonymous test only (public in `access_control` but guarded in the
  controller, which answers 404 before its check when the sample id does not exist).
- `ANONYMOUS_LOGIN_PATTERN` is the regex a redirect `Location` must match to count as a redirect to the
  login page (default `#/(login|connect|oauth|sso|auth)#i`).

Routes whose name starts with `_` (profiler, wdt...) and routes without a class controller are ignored.
A right check done somewhere the analysis cannot see (a service, a listener) is not detected: exclude the
route and say why.

## Filter Test

The filter test will be useful to check that your filters are well configured.

Here is the class to set up:

```php
#/tests/FiltersTest.php

<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Lle\CruditBundle\Test\FilterTestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FiltersTest extends WebTestCase
{
    use FilterTestHelperTrait;
    
    public const LOGIN_USER = 'admin@app.fr';
    public const USER_REPOSITORY = UserRepository::class;
}

```
There is 2 constants to setup in order to configure the test :

- `LOGIN_USER` The user to be used in the tests. Make sure that this user has the rights to access every pages.
- `USER_REPOSITORY` The repository that manages users in the app.
## Sortable test

To test the sort actions on the cruds, you can use the sortable test.

Here is the configuration:

```php
#/tests/SortableTest.php

<?php

namespace App\Tests;

use Lle\CruditBundle\Test\TestSortableHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SortableTest extends KernelTestCase
{
    use TestSortableHelperTrait;
}

```
