<?php

declare(strict_types=1);

namespace phpunit\Unit\Twig;

use Lle\CruditBundle\Twig\SearchExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * What the layout needs for the global search: its address and the minimum of characters, or null (disabled, or the
 * route of the bundle is not imported: the pages must not fail).
 */
class SearchExtensionTest extends TestCase
{
    public function testSearch(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->with('lle_crudit_search')->willReturn('/crudit/search');

        self::assertSame(['url' => '/crudit/search', 'min_length' => 3], (new SearchExtension($router, true, 3))->search());
    }

    public function testDisabled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        self::assertNull((new SearchExtension($router, false, 2))->search());
    }

    public function testRouteNotImported(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willThrowException(new RouteNotFoundException());

        self::assertNull((new SearchExtension($router, true, 2))->search());
    }
}
