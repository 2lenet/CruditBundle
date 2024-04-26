# Test your application

Crudit gives you some helper traits in order to test your app.

## Navigation test

A navigation test is provided. The test will navigate on every crud which apear in the menu.
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

## Filter Test

The filter test will be useful to check that your filters are well configured.

Here is the class to set up:

```php
#/tests/FiltersTest.php

<?php

namespace App\Tests;

use Lle\CruditBundle\Test\FilterTestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FiltersTest extends KernelTestCase
{
    use FilterTestHelperTrait;
}

```

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
