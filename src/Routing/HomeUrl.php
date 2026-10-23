<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Routing;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The root of the application, as the router writes the addresses (under its base path, `index.php` included
 * without rewriting): the link of the logo (crudit_home_url()), the home page of the breadcrumb, the way back of an
 * impersonation without its route.
 */
class HomeUrl
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function url(): string
    {
        return ($this->requestStack->getCurrentRequest()?->getBaseUrl() ?? '') . '/';
    }
}
