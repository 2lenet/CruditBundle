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

- `testEveryRouteIsProtected`: static analysis, no HTTP request;
- `testExclusionsAreStillUsed`: fails when an exclusion no longer excuses anything (for instance a route
  that got a real check since), so the lists cannot rot;
- `testAnonymousIsDeniedEverywhere`: sends one anonymous request per route and HTTP method. Anything other
  than 401, 403 or a redirect to the login page is reported.

### What counts as protected

A route is protected, for every HTTP method it accepts, when one of these is true:

- the `access_control` rule matching its path requires something other than `PUBLIC_ACCESS`, `ROLE_USER`,
  `IS_AUTHENTICATED_*`, or an `allow_if` expression that only checks one of those (`is_granted('ROLE_USER')`,
  `is_authenticated()`, `is_fully_authenticated()`, `is_remember_me()`), **and** a firewall covers the path
  (a `security: false` firewall is reported);
- the controller method, or the **concrete** controller class, carries `#[IsGranted]` with a real role.
  Symfony does not read attributes of parent classes, so neither does the test: an `#[IsGranted]` on a
  parent class protects nothing;
- the body of the method calls `denyAccessUnlessGranted` / `denyUnlessAccessGranted`, or tests
  `!isGranted(...)` followed by a denial (`throw`, `createAccessDeniedException`, `HTTP_FORBIDDEN`, `403`),
  or calls a method of the controller doing so. The call must open a statement of the method body itself
  (a `try` block is not nesting): a check inside an `if` (with or without braces), a loop, a closure or an
  arrow function, or behind `&&`, `?:` or `??`, may not run, so it is reported.

A check against `ROLE_USER` alone never counts, whether it is written as a string or as a constant
(`self::ROLE_USER`, `AuthenticatedVoter::IS_AUTHENTICATED_FULLY`), and neither does a commented-out one.

The analysis is a heuristic, so "protected" means "a check was found", not "proved". It cannot see a check
done in a service or a listener, a `catch` that swallows the `AccessDeniedException`, a positive guard
(`if (isGranted(...)) { ... } else { throw ... }`) or a guard that redirects instead of denying
(`if (!isGranted(...)) { return $this->redirectToRoute(...); }`): exclude the route and say why.

Routes without `_controller` (handled by the firewall, like a logout) and routes whose name starts with `_`
(profiler, wdt...) are ignored. Routes declared in config with `RedirectController` or `TemplateController`
(a redirect of `/`, for example) are reported like any other: add them to `EXCLUDED_ROUTES`. A controller that cannot be resolved to a class and a method (closure,
`service.id:method`) is reported, since nothing can be checked.

### The anonymous test

The sample path of a route takes its defaults first, then the first alternative of a requirement
(`fr|en` gives `fr`), then `1`. Routing runs before the firewall, so when the sample path does not resolve
to the route (a requirement such as `Requirement::UUID`, a catch-all route, a `schemes: https` redirect)
the route cannot be tested, and the test **fails** rather than silently testing less: give the placeholders
a value with `ROUTE_SAMPLE_PARAMETERS`, or list the route in `ANONYMOUS_EXCLUDED_ROUTES`. A route hidden by
another route with the same path is never served, so it is ignored.

Heads up: a route that really is open runs for real, **POST, PUT, PATCH and DELETE included**, against the
test database. A route without `methods` gets five requests.

### Setup

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
  Both also skip the anonymous test.
- `PUBLIC_ROUTES` and `ROLE_USER_ROUTES` are typed on purpose: if a route listed in `ROLE_USER_ROUTES` becomes
  public, the test fails instead of staying silent. `PUBLIC_ROUTES` also skips the anonymous test.
- `ROUTE_SAMPLE_PARAMETERS` gives the anonymous test its placeholder values, by route name glob, the last
  matching entry winning: `['*' => ['uuid' => '0190a1b2-...'], 'app_blog_*' => ['slug' => 'hello']]`.
- `ANONYMOUS_EXCLUDED_ROUTES` skips the anonymous test only (public in `access_control` but guarded in the
  controller, which answers 404 before its check when the sample id does not exist, or a route no sample
  path can reach). It is checked too: an entry that no anonymous request reaches any more fails the test.
- `ANONYMOUS_LOGIN_PATTERN` is the regex a redirect `Location` must match to count as a redirect to the login
  page (default: a path segment starting with `login`, `connect`, `oauth`, `sso` or `auth`).

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
