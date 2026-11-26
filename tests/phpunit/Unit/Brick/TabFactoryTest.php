<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Brick\TabBrick\Tab;
use Lle\CruditBundle\Brick\TabBrick\TabConfig;
use Lle\CruditBundle\Brick\TabBrick\TabFactory;
use Lle\CruditBundle\Brick\TabBrick\TabView;
use Lle\CruditBundle\Brick\TemplateBrick\TemplateConfig;
use Lle\CruditBundle\Builder\BrickBuilder;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The tabs as rendered (lot 3.3.b): only the tabs granted and shown, their bricks built, their counter, a form that
 * knows its tab.
 */
class TabFactoryTest extends TestCase
{
    public function testOnlyTheTabsGrantedAndShown(): void
    {
        $built = [];
        $form = FormConfig::new();
        $config = TabConfig::new()
            ->add('tab.notes', TemplateConfig::new(['template' => 'notes.html.twig']), count: fn (\stdClass $record): int => \count($record->notes))
            ->add('tab.secret', TemplateConfig::new(['template' => 'secret.html.twig']), role: 'ROLE_SECRET')
            ->add('tab.hidden', TemplateConfig::new(['template' => 'hidden.html.twig']), displayIf: fn (?object $record): bool => false)
            ->add('tab.request', TemplateConfig::new(['template' => 'request.html.twig']), displayIfByRequest: fn (?Request $request): bool => $request?->query->has('all') ?? false)
            ->add('tab.edit', $form, role: 'ROLE_EDIT');

        $tabs = $this->tabs($config, ['ROLE_EDIT'], (object) ['notes' => [1, 2]], $built);

        self::assertSame(['notes', 'edit'], array_map(fn (TabView $tab) => $tab->getId(), $tabs));
        self::assertSame([2, null], array_map(fn (TabView $tab) => $tab->getCount(), $tabs));
        self::assertSame('tab.notes', $tabs[0]->getLabel());
        self::assertCount(1, $tabs[1]->getViews());
        // The bricks of a tab not granted are not built: its form is not bound
        self::assertCount(2, $built);
        self::assertSame('edit', $form->getTab());
    }

    public function testATabShownByTheRequest(): void
    {
        $built = [];
        $config = TabConfig::new()->add('tab.request', TemplateConfig::new(['template' => 'request.html.twig']), displayIfByRequest: fn (?Request $request): bool => $request?->query->has('all') ?? false);

        self::assertSame([], $this->tabs($config, [], (object) [], $built));
        $config = TabConfig::new()->add('tab.request', TemplateConfig::new(['template' => 'request.html.twig']), displayIfByRequest: fn (?Request $request): bool => $request?->query->has('all') ?? false);
        self::assertCount(1, $this->tabs($config, [], (object) [], $built, ['all' => '1']));
    }

    public function testATabWithoutRoleNorRecord(): void
    {
        $built = [];
        $config = TabConfig::new()->addTab(Tab::new('tab.parts', [TemplateConfig::new(['template' => 'parts.html.twig'])])->setCount(fn (object $record): int => 1));

        $tabs = $this->tabs($config, [], null, $built);

        self::assertSame(['parts'], array_map(fn (TabView $tab) => $tab->getId(), $tabs));
        self::assertNull($tabs[0]->getCount(), 'no record, no counter');
    }

    /**
     * @param string[] $roles
     * @param BrickConfigInterface[] $built
     * @param array<string, string> $query
     *
     * @return TabView[]
     */
    private function tabs(TabConfig $config, array $roles, ?object $record, array &$built, array $query = []): array
    {
        $datasource = $this->createStub(DatasourceInterface::class);
        $datasource->method('get')->willReturn($record);
        $crudConfig = $this->createStub(CrudConfigInterface::class);
        $crudConfig->method('getDatasource')->willReturn($datasource);
        $crudConfig->method('getTranslationDomain')->willReturn('messages');
        $config->setCrudConfig($crudConfig);
        $config->setPageKey(CrudConfigInterface::SHOW);
        $config->setId('tabs');

        $builder = $this->createStub(BrickBuilder::class);
        $builder->method('buildBrick')->willReturnCallback(function (CrudConfigInterface $crud, string $pageKey, BrickConfigInterface $brick) use (&$built): BrickView {
            $built[] = $brick;
            $brick->setId('brick-' . \count($built));
            $brick->setCrudConfig($crud);
            $brick->setPageKey($pageKey);

            return new BrickView($brick);
        });
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(fn (mixed $role): bool => \in_array($role, $roles, true));

        $requestStack = new RequestStack();
        $requestStack->push(new Request(query: $query, attributes: ['id' => null === $record ? null : 7]));
        $factory = new TabFactory($this->createStub(ResourceResolver::class), $requestStack, $builder, $checker);

        /** @var TabView[] $tabs */
        $tabs = $factory->buildView($config)->getConfig()['tabs'];

        return $tabs;
    }
}
