<?php

declare(strict_types=1);

namespace phpunit\Unit\Search;

use Lle\CruditBundle\Builder\MenuBuilder;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Layout\ExternalLinkElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Provider\ConfigProvider;
use Lle\CruditBundle\Search\GlobalSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The groups of the global search: the CrudConfig of the configuration (rights, limit, second line, title), then the
 * screens of the menu.
 */
class GlobalSearchTest extends TestCase
{
    /** @var list<string> the roles asked, with the subject (null or the class of the resource) */
    private array $asked = [];

    /** @var list<string> the roles that the user does not have */
    private array $refused = [];

    /** @var list<DatasourceParams> */
    private array $params = [];

    public function testGroupOfAnEntity(): void
    {
        $search = $this->search([$this->entity()], items: [$this->customer(1, 'Martin', 'Colmar'), $this->customer(2, 'Marc', null)]);

        self::assertSame([[
            'id' => 'CUSTOMER',
            'label' => 'Customers',
            'results' => [
                ['id' => 'CUSTOMER-1', 'title' => 'Martin', 'subtitle' => 'Colmar', 'url' => '/customer/show?id=1'],
                ['id' => 'CUSTOMER-2', 'title' => 'Marc', 'subtitle' => null, 'url' => '/customer/show?id=2'],
            ],
        ]], $search->search(' mar '));
        // The text is trimmed, the limit of the group goes to the datasource.
        self::assertSame(3, $this->params[0]->getLimit());
        // The group is asked on the class, each result on the resource: the voters of the project apply.
        self::assertSame(['ROLE_CUSTOMER_SHOW', 'ROLE_CUSTOMER_SHOW@resource', 'ROLE_CUSTOMER_SHOW@resource'], $this->asked);
    }

    public function testResultRefusedByAVoterIsNotGiven(): void
    {
        $this->refused = ['ROLE_CUSTOMER_SHOW@resource'];

        self::assertSame([], $this->search([$this->entity()], items: [$this->customer(1, 'Martin', null)])->search('mar'));
    }

    public function testGroupWithoutTheRoleIsNotAskedToTheDatabase(): void
    {
        $this->refused = ['ROLE_CUSTOMER_SHOW'];
        $search = $this->search([$this->entity()], items: [$this->customer(1, 'Martin', null)]);

        self::assertSame([], $search->search('mar'));
        self::assertSame([], $this->params, 'the datasource is not asked');
    }

    public function testTitleOfTheGroup(): void
    {
        $search = $this->search([$this->entity(title: 'menu.customers')], items: [$this->customer(1, 'Martin', null)]);

        self::assertSame('Translated menu.customers', $search->search('mar')[0]['label']);
        // Without a title nor a translation of the list: the name of the CrudConfig.
        $search = $this->search([$this->entity()], items: [$this->customer(1, 'Martin', null)], translated: false);

        self::assertSame('Customer', $search->search('mar')[0]['label']);
    }

    public function testSubtitleThatCannotBeReadIsLeftOut(): void
    {
        $search = $this->search([$this->entity(subtitle: 'unknown.path')], items: [$this->customer(1, 'Martin', null)]);

        self::assertNull($search->search('mar')[0]['results'][0]['subtitle']);
    }

    public function testGroupThatFailsIsLeftOutAndTheOthersStay(): void
    {
        // The first entity is unknown (the container refuses it, here the provider does not): the second one answers.
        $search = $this->search(
            [['config' => 'App\\Crudit\\Config\\GhostCrudConfig', 'title' => null, 'limit' => 5, 'subtitle' => null], $this->entity()],
            items: [$this->customer(1, 'Martin', null)],
        );

        self::assertSame(['CUSTOMER'], array_column($search->search('mar'), 'id'));
    }

    public function testEveryGroupThatFailsIsAnError(): void
    {
        // The database is down: not "no result", an error that the route answers with a 500 (the palette says it).
        $search = $this->search([['config' => 'App\\Crudit\\Config\\GhostCrudConfig', 'title' => null, 'limit' => 5, 'subtitle' => null]]);

        $this->expectException(\LogicException::class);
        $search->search('mar');
    }

    public function testGroupThatFailsIsNotHiddenInDebug(): void
    {
        $search = $this->search(
            [['config' => 'App\\Crudit\\Config\\GhostCrudConfig', 'title' => null, 'limit' => 5, 'subtitle' => null], $this->entity()],
            items: [$this->customer(1, 'Martin', null)],
            debug: true,
        );

        $this->expectException(\LogicException::class);
        $search->search('mar');
    }

    public function testGroupWithoutResultIsNotAFailure(): void
    {
        self::assertSame([], $this->search([$this->entity()], items: [])->search('zzz'));
    }

    public function testNothingUnderTheMinimumOfCharacters(): void
    {
        $search = $this->search([$this->entity()], items: [$this->customer(1, 'Martin', null)]);

        self::assertSame([], $search->search('m'));
        self::assertSame([], $search->search('  m '));
        self::assertSame([], $this->params);
        // The minimum counts the characters, not the bytes: "é" is one.
        self::assertSame([], $search->search('é'));
    }

    public function testNoGroupWithoutResult(): void
    {
        self::assertSame([], $this->search([$this->entity()], items: [])->search('zzz'));
    }

    public function testScreens(): void
    {
        $billing = LinkElement::new('menu.billing', null);
        $billing->add(LinkElement::new('menu.invoices', Path::new('invoices')));
        $billing->add(LinkElement::new('menu.credits', Path::new('credits')));
        $menu = [
            LinkElement::new('menu.home', Path::new('home')),
            $billing,
            LinkElement::new('menu.élèves', Path::new('pupils')),
            ExternalLinkElement::new('menu.documentation', 'https://example.org/doc'),
        ];
        $search = $this->search([], menu: $menu, screens: true);

        // A link without route (the sub-menu itself) and an external link are not screens; without regard to the accents
        // and the case; the sub-menu is the second line, "Screen" the others.
        self::assertSame([['id' => 'screens', 'label' => 'Screens', 'results' => [
            ['id' => 'screen-0', 'title' => 'Translated menu.invoices', 'subtitle' => 'Translated menu.billing', 'url' => '/invoices'],
        ]]], $search->search('INVOICES'));
        self::assertSame('Translated menu.élèves', $search->search('eleves')[0]['results'][0]['title']);
        self::assertSame('Screen', $search->search('home')[0]['results'][0]['subtitle']);
        self::assertSame([], $search->search('documentation'));
        self::assertSame([], $this->search([], menu: $menu, screens: false)->search('invoices'));
    }

    public function testScreensAreLimitedAndComeAfterTheEntities(): void
    {
        $menu = array_map(static fn (int $i): LinkElement => LinkElement::new('menu.page' . $i, Path::new('page' . $i)), range(1, 8));
        $search = $this->search([$this->entity()], items: [$this->customer(1, 'Page', null)], menu: $menu, screens: true);

        $groups = $search->search('page');

        self::assertSame(['CUSTOMER', 'screens'], array_column($groups, 'id'));
        self::assertCount(5, $groups[1]['results']);
    }

    /**
     * @return array{config: string, title: ?string, limit: int, subtitle: ?string}
     */
    private function entity(?string $title = null, ?string $subtitle = 'city'): array
    {
        return ['config' => 'App\\Crudit\\Config\\CustomerCrudConfig', 'title' => $title, 'limit' => 3, 'subtitle' => $subtitle];
    }

    /**
     * @param list<array{config: string, title: ?string, limit: int, subtitle: ?string}> $entities
     * @param list<object> $items
     * @param list<object> $menu
     */
    private function search(array $entities, array $items = [], array $menu = [], bool $screens = false, bool $translated = true, bool $debug = false): GlobalSearch
    {
        $datasource = $this->createMock(DatasourceInterface::class);
        $datasource->method('autocompleteQuery')->willReturnCallback(function (string $text, array $sorts, ?DatasourceParams $params) use ($items): array {
            self::assertSame(trim($text), $text);
            $this->params[] = $params ?? new DatasourceParams();

            return $items;
        });

        $config = $this->createMock(CrudConfigInterface::class);
        $config->method('getName')->willReturn('CUSTOMER');
        $config->method('getTitle')->willReturn('crud.title.list.customer');
        $config->method('getTranslationDomain')->willReturn('messages');
        $config->method('getDefaultAutocompleteSort')->willReturn(null);
        $config->method('getDefaultSort')->willReturn([['name', 'ASC']]);
        $config->method('getDatasource')->willReturn($datasource);
        $config->method('getPath')->willReturnCallback(static fn (string $context, array $params): Path => Path::new('customer_' . $context, $params));

        // The key of ConfigProvider is the class of the CrudConfig, which a double does not have.
        $provider = $this->createMock(ConfigProvider::class);
        $provider->method('get')->willReturnCallback(static fn (string $class): ?CrudConfigInterface => $class === 'App\\Crudit\\Config\\CustomerCrudConfig' ? $config : null);

        $menuBuilder = $this->createMock(MenuBuilder::class);
        $menuBuilder->method('build')->with('menu_side')->willReturn($menu);

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->method('isGranted')->willReturnCallback(function (string $role, mixed $subject = null): bool {
            $key = $subject === null ? $role : $role . '@resource';
            $this->asked[] = $key;

            return !\in_array($key, $this->refused, true);
        });

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $route, array $params = []): string => match (true) {
            $route === 'customer_show' => '/customer/show?id=' . $params['id'],
            default => '/' . $route,
        });

        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => match (true) {
            $id === 'crudit.search.screens' => 'Screens',
            $id === 'crudit.search.screen' => 'Screen',
            $id === 'crud.title.list.customer' => $translated ? 'Customers' : $id,
            default => 'Translated ' . $id,
        });

        return new GlobalSearch(
            $provider,
            $menuBuilder,
            $security,
            $router,
            $translator,
            PropertyAccess::createPropertyAccessor(),
            $entities,
            $screens,
            2,
            null,
            $debug,
        );
    }

    /**
     * A resource of the application: an identifier, a name (its string form) and a city.
     */
    private function customer(int $id, string $name, ?string $city): object
    {
        return new class ($id, $name, $city) {
            public function __construct(private readonly int $id, private readonly string $name, private readonly ?string $city)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getCity(): ?string
            {
                return $this->city;
            }

            public function __toString(): string
            {
                return $this->name;
            }
        };
    }
}
