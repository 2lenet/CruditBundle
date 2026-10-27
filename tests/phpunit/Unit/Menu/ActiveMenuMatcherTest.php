<?php

declare(strict_types=1);

namespace phpunit\Unit\Menu;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Menu\ActiveMenuMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The entry of the menu of the current page: the same route, a page of a CRUD counting for its list.
 */
class ActiveMenuMatcherTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function routes(): iterable
    {
        yield 'same route' => ['app_client_index', 'app_client_index', true];
        yield 'a page of the CRUD' => ['app_client_index', 'app_client_show', true];
        yield 'edit' => ['app_client_index', 'app_client_edit', true];
        yield 'another CRUD' => ['app_client_index', 'app_invoice_index', false];
        yield 'one underscore: not every app_ route' => ['app_home', 'app_planning', false];
        yield 'one underscore, same route' => ['app_home', 'app_home', true];
        yield 'no underscore' => ['invoices', 'credits', false];
        yield 'an action of the project in the CRUD' => ['app_client_index', 'app_client_import', true];
        yield 'a CRUD whose name starts the same' => ['app_client_index', 'app_client_contact_index', false];
        yield 'not a list: its route only' => ['app_client_list', 'app_client_detail', false];
        yield 'app_index: not a CRUD' => ['app_index', 'app_login', false];
        yield 'app_index, same route' => ['app_index', 'app_index', true];
        yield 'not a list, same route' => ['app_client_list', 'app_client_list', true];
    }

    #[DataProvider('routes')]
    public function testRoutes(string $link, string $current, bool $active): void
    {
        self::assertSame($active, $this->isActive(LinkElement::new('link', Path::new($link)), ['_route' => $current]));
    }

    public function testCrudOfTheResource(): void
    {
        $link = LinkElement::new('clients', Path::new('lle_crudit_crud_index', ['resource' => 'client']));

        self::assertTrue($this->isActive($link, ['_route' => 'lle_crudit_crud_index', 'resource' => 'client']));
        self::assertFalse($this->isActive($link, ['_route' => 'lle_crudit_crud_index', 'resource' => 'invoice']));
    }

    public function testCrudLinkWithoutResourceIsNeverActive(): void
    {
        self::assertFalse($this->isActive(LinkElement::new('crud', Path::new('lle_crudit_crud_index')), ['_route' => 'home']));
    }

    public function testParentOfTheEntry(): void
    {
        $title = TitleElement::new('tools');
        $title->addChild(LinkElement::new('exports', Path::new('exports')));

        self::assertTrue($this->isActive($title, ['_route' => 'exports']));
        self::assertFalse($this->isActive($title, ['_route' => 'home']));
    }

    /**
     * @param array<string, string> $attributes
     */
    private function isActive(LinkElement|TitleElement $element, array $attributes): bool
    {
        return (new ActiveMenuMatcher())->isActive($element, new Request(attributes: $attributes));
    }
}
