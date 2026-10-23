<?php

declare(strict_types=1);

namespace phpunit\Unit\Builder;

use Lle\CruditBundle\Builder\BreadcrumbBuilder;
use Lle\CruditBundle\Builder\MenuBuilder;
use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Layout\CategoryElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Menu\ActiveMenuMatcher;
use Lle\CruditBundle\Registry\MenuRegistry;
use Lle\CruditBundle\Routing\HomeUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The breadcrumb from the menu of the user: the home page, what holds the entry of the page, the entry, the current
 * page.
 */
class BreadcrumbBuilderTest extends TestCase
{
    private const array HOME = ['label' => 'crudit.layout.breadcrumb_home', 'url' => '/'];

    public function testHomeOnlyWithoutEntryOfTheMenu(): void
    {
        self::assertSame([self::HOME], $this->build([LinkElement::new('clients', Path::new('app_client_index'))], 'app_other_index'));
    }

    public function testEntryOfTheMenu(): void
    {
        $menu = [LinkElement::new('clients', Path::new('app_client_index'))];

        self::assertSame([self::HOME, ['label' => 'clients', 'url' => '/app_client_index']], $this->build($menu, 'app_client_index'));
    }

    public function testGroupOfTheEntryUntilASeparator(): void
    {
        $menu = [
            CategoryElement::new('operations'),
            LinkElement::new('clients', Path::new('app_client_index')),
            HorizontalSeparatorElement::new(),
            LinkElement::new('settings', Path::new('app_settings_index')),
        ];

        self::assertSame([self::HOME, ['label' => 'operations'], ['label' => 'clients', 'url' => '/app_client_index']], $this->build($menu, 'app_client_show'));
        self::assertSame([self::HOME, ['label' => 'settings', 'url' => '/app_settings_index']], $this->build($menu, 'app_settings_index'));
    }

    public function testTitleAndSubMenuThatHoldTheEntry(): void
    {
        $billing = LinkElement::new('billing', null);
        $billing->add(LinkElement::new('invoices', Path::new('app_invoice_index')));
        $title = TitleElement::new('administration');
        $title->addChild($billing);

        self::assertSame(
            [self::HOME, ['label' => 'administration'], ['label' => 'billing'], ['label' => 'invoices', 'url' => '/app_invoice_index']],
            $this->build([$title], 'app_invoice_edit'),
        );
    }

    public function testEntryRefusedToTheUserIsNotThere(): void
    {
        $menu = [LinkElement::new('clients', Path::new('app_client_index'), role: 'ROLE_ADMIN')];

        self::assertSame([self::HOME], $this->build($menu, 'app_client_index'));
    }

    public function testCurrentPageAfterTheEntry(): void
    {
        $menu = [LinkElement::new('clients', Path::new('app_client_index'))];
        $current = [['label' => 'Alice', 'url' => '/client/1'], ['label' => 'Edit']];

        self::assertSame([self::HOME, ['label' => 'clients', 'url' => '/app_client_index'], ...$current], $this->build($menu, 'app_client_edit', $current));
    }

    /**
     * The links after a title with children are in its group, as the menu shows them.
     */
    public function testGroupOfATitleWithChildren(): void
    {
        $admin = TitleElement::new('admin');
        $admin->addChild(LinkElement::new('users', Path::new('app_user_index')));
        $menu = [TitleElement::new('sales'), LinkElement::new('orders', Path::new('app_order_index')), $admin, LinkElement::new('settings', Path::new('app_settings_index'))];

        self::assertSame([self::HOME, ['label' => 'admin'], ['label' => 'settings', 'url' => '/app_settings_index']], $this->build($menu, 'app_settings_index'));
        self::assertSame([self::HOME, ['label' => 'admin'], ['label' => 'users', 'url' => '/app_user_index']], $this->build($menu, 'app_user_index'));
    }

    /**
     * Two titles in a category: the second is in the category, not under the first.
     */
    public function testTitlesOfACategory(): void
    {
        $users = TitleElement::new('users');
        $users->addChild(LinkElement::new('accounts', Path::new('app_account_index')));
        $roles = TitleElement::new('roles');
        $roles->addChild(LinkElement::new('permissions', Path::new('app_permission_index')));
        $menu = [CategoryElement::new('administration'), $users, $roles];

        self::assertSame(
            [self::HOME, ['label' => 'administration'], ['label' => 'roles'], ['label' => 'permissions', 'url' => '/app_permission_index']],
            $this->build($menu, 'app_permission_index'),
        );
    }

    /**
     * The entry of the home page under a title: neither the entry nor its title.
     */
    public function testGroupOfTheHomePageIsNotShown(): void
    {
        $menu = [TitleElement::new('general'), LinkElement::new('dashboard', Path::new('home'))];

        self::assertSame([self::HOME], $this->build($menu, 'home'));
    }

    /**
     * The entry "Home" of the menu goes to another route than the root (/dashboard): not "Home › Home".
     */
    public function testHomeEntryOnAnotherRouteIsNotRepeated(): void
    {
        $menu = [LinkElement::new('crudit.layout.breadcrumb_home', Path::new('app_dashboard'))];

        self::assertSame([self::HOME], $this->build($menu, 'app_dashboard'));
    }

    /**
     * The page of the record on the page of its form: all the parameters of the route (a prefix /{tenant}); without a
     * route of the page of the record, a text.
     */
    public function testLinkOfTheRecordOnItsForm(): void
    {
        $menu = [LinkElement::new('clients', Path::new('app_client_index'))];

        self::assertSame(
            ['label' => 'Bob', 'url' => '/app_client_show?tenant=acme&id=12'],
            $this->build($menu, 'app_client_edit', crudRecord: 'Bob', routeParams: ['tenant' => 'acme', 'id' => '12'])[2],
        );
        self::assertSame(['label' => 'Bob'], $this->build($menu, 'app_nopage_edit', crudRecord: 'Bob')[1]);
    }

    /**
     * Two levels of links, as the menu: a child of a link of a sub-menu is not shown, nor in the breadcrumb.
     */
    public function testThirdLevelIsNotInTheBreadcrumb(): void
    {
        $reminders = LinkElement::new('reminders', Path::new('reminders'));
        $reminders->add(LinkElement::new('late', Path::new('reminders')));
        $billing = LinkElement::new('billing', null);
        $billing->add($reminders);

        self::assertSame([self::HOME, ['label' => 'billing'], ['label' => 'reminders', 'url' => '/reminders']], $this->build([$billing], 'reminders'));
    }

    /**
     * An application under a base path: the home page is its root, and its entry in the menu is not repeated.
     */
    #[DataProvider('basePaths')]
    public function testHomeUnderTheBasePath(string $basePath): void
    {
        $menu = [LinkElement::new('home', Path::new('home'))];

        self::assertSame([['label' => 'crudit.layout.breadcrumb_home', 'url' => $basePath . '/']], $this->build($menu, 'home', [], $basePath));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function basePaths(): iterable
    {
        yield 'with rewriting' => ['/gestio'];
        yield 'without rewriting' => ['/gestio/index.php'];
    }

    /**
     * A sub-menu is a button in the menu: a text in the breadcrumb, even with a path, and a path of a provider that
     * does not exist is a text, not an error on the page.
     */
    public function testSubMenuAndMissingRouteAreTexts(): void
    {
        $admin = LinkElement::new('admin', Path::new('app_nopage_admin'));
        $admin->add(LinkElement::new('users', Path::new('app_user_index')));
        $missing = LinkElement::new('missing', Path::new('app_nopage_index'));

        self::assertSame([self::HOME, ['label' => 'admin'], ['label' => 'users', 'url' => '/app_user_index']], $this->build([$admin], 'app_user_index'));
        self::assertSame([self::HOME, ['label' => 'missing']], $this->build([$missing], 'app_nopage_index'));
    }

    /**
     * @return iterable<string, array{string, list<array{label: string, url?: string}>}>
     */
    public static function crudPages(): iterable
    {
        $clients = ['label' => 'clients', 'url' => '/app_client_index'];
        yield 'list' => ['app_client_index', [self::HOME, $clients]];
        yield 'record' => ['app_client_show', [self::HOME, $clients, ['label' => 'Bob']]];
        yield 'edit' => ['app_client_edit', [self::HOME, $clients, ['label' => 'Bob', 'url' => '/app_client_show?id=12'], ['label' => 'crudit.action.edit']]];
        yield 'new' => ['app_client_new', [self::HOME, $clients, ['label' => 'crudit.layout.breadcrumb_new']]];
    }

    /**
     * @param list<array{label: string, url?: string}> $expected
     */
    #[DataProvider('crudPages')]
    public function testPageOfACrud(string $route, array $expected): void
    {
        $menu = [LinkElement::new('clients', Path::new('app_client_index'))];

        self::assertSame($expected, $this->build($menu, $route, crudRecord: 'Bob'));
    }

    public function testEntryOfTheHomePageIsNotRepeated(): void
    {
        $menu = [LinkElement::new('home', Path::new('home'))];

        self::assertSame([self::HOME], $this->build($menu, 'home'));
    }

    /**
     * @param LayoutElementInterface[]                  $elements
     * @param list<array{label: string, url?: string}> $current
     * @param array<string, string>                    $routeParams
     *
     * @return list<array{label: string, url?: string}>
     */
    private function build(array $elements, string $route, array $current = [], string $basePath = '', ?string $crudRecord = null, array $routeParams = ['id' => '12']): array
    {
        $provider = new class ($elements) implements MenuProviderInterface {
            /** @param LayoutElementInterface[] $elements */
            public function __construct(private array $elements)
            {
            }

            public function getMenuEntry(): iterable
            {
                return $this->elements;
            }
        };
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(static fn (mixed $role): bool => $role !== 'ROLE_ADMIN');
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static function (string $name, array $parameters = []) use ($basePath): string {
            if (str_starts_with($name, 'app_nopage')) {
                throw new RouteNotFoundException($name);
            }

            return $basePath . ($name === 'home' ? '/' : '/' . $name) . ($parameters !== [] ? '?' . http_build_query($parameters) : '');
        });
        // $basePath ends with /index.php without rewriting: the front controller is in the address
        $script = str_ends_with($basePath, '/index.php') ? $basePath : $basePath . '/index.php';
        $request = Request::create($basePath . '/page', server: ['SCRIPT_FILENAME' => '/app/public/index.php', 'SCRIPT_NAME' => $script]);
        $request->attributes->set('_route', $route);
        $request->attributes->set('_route_params', $routeParams);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $builder = new BreadcrumbBuilder(
            new MenuBuilder(new MenuRegistry([$provider]), $authorizationChecker),
            new ActiveMenuMatcher(),
            $requestStack,
            $translator,
            $urlGenerator,
            new HomeUrl($requestStack),
        );

        return $crudRecord !== null ? $builder->buildForCrud($crudRecord) : $builder->build($current);
    }
}
