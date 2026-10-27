<?php

declare(strict_types=1);

namespace phpunit\Unit\Action;

use Lle\CruditBundle\Action\ActionResolver;
use Lle\CruditBundle\Action\ActionView;
use Lle\CruditBundle\Action\PageActions;
use Lle\CruditBundle\Action\PageActionsBuilder;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\EditAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\PrintAction;
use Lle\CruditBundle\Dto\Path;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The bar of the header: the hidden actions removed, one primary action (the first), the menu "More" by group (those
 * without group first), a DropdownAction without any action shown removed, the id of the request in the parameters.
 * A line of a list: the edition apart, the others in its menu, the id of the line in the parameters and the modals.
 */
class PageActionsBuilderTest extends TestCase
{
    public function testOrderAndOnePrimary(): void
    {
        $actions = $this->build([
            $this->action('back'),
            $this->action('add')->setPrimary(),
            $this->action('import')->setPrimary(),
            $this->action('export'),
        ]);

        self::assertSame(['back', 'add', 'import', 'export'], $this->labels($actions->inline));
        self::assertSame([false, true, false, false], array_map(static fn (ActionView $view): bool => $view->primary, $actions->inline));
        self::assertSame(['back', 'import', 'export'], $this->labels($actions->getSecondary()));
    }

    public function testHiddenPrimaryLeavesThePlace(): void
    {
        $actions = $this->build([
            $this->action('add')->setPrimary()->setRole('ROLE_NOBODY'),
            $this->action('import')->setPrimary(),
        ]);

        self::assertSame(['import'], $this->labels($actions->inline));
        self::assertTrue($actions->inline[0]->primary);
    }

    public function testMoreByGroup(): void
    {
        $actions = $this->build([
            $this->action('archive')->setDropdown(true)->setGroup('group.state'),
            $this->action('duplicate')->setDropdown(true),
            $this->action('close')->setDropdown(true)->setGroup('group.state'),
            $this->action('hidden')->setDropdown(true)->setGroup('group.other')->setDisplayIf(static fn (): bool => false),
        ]);

        self::assertSame([], $actions->inline);
        self::assertSame([null, 'group.state'], array_column($actions->more, 'label'));
        self::assertSame(['duplicate'], $this->labels($actions->more[0]['items']));
        self::assertSame(['archive', 'close'], $this->labels($actions->more[1]['items']));
    }

    public function testDropdownAction(): void
    {
        $actions = $this->build([
            DropdownAction::new('action.status', [
                'group.state' => [$this->action('archive'), $this->action('hidden')->setRole('ROLE_NOBODY')],
                [$this->action('duplicate')],
            ]),
            DropdownAction::new('action.empty', [[$this->action('hidden')->setRole('ROLE_NOBODY')]]),
        ]);

        self::assertSame(['action.status'], $this->labels($actions->inline));
        $dropdown = $actions->inline[0];
        self::assertTrue($dropdown->isDropdownAction());
        self::assertSame(['group.state', null], array_column($dropdown->groups, 'label'));
        self::assertSame(['archive'], $this->labels($dropdown->groups[0]['items']));
        self::assertSame([['label' => 'action.status', 'domain' => null, 'items' => [$dropdown->groups[0]['items'][0], $dropdown->groups[1]['items'][0]]]], $actions->getSecondaryGroups());
    }

    public function testVariant(): void
    {
        $actions = $this->build([
            $this->action('add')->setPrimary()->setVariant('ghost'),
            $this->action('delete')->setVariant('danger'),
            $this->action('purge')->setVariant('danger-outline')->setDisabledReason('no.purge'),
            $this->action('archive')->setVariant('ghost')->setDisabledReason('no.archive'),
        ]);

        // The primary one in primary, no colour of danger on a disabled action
        self::assertSame(['primary', 'danger', 'secondary', 'ghost'], array_map(static fn (ActionView $view): string => $view->getVariant(), $actions->inline));

        // setVariant('primary') on an action that is not the primary one: secondary, one primary button per page
        $actions = $this->build([$this->action('add')->setPrimary(), $this->action('import')->setVariant('primary')]);
        self::assertSame(['primary', 'secondary'], array_map(static fn (ActionView $view): string => $view->getVariant(), $actions->inline));
    }

    public function testUrlAndParams(): void
    {
        $request = new Request(attributes: ['id' => 7]);
        $actions = $this->build([
            $this->action('edit'),
            ListAction::new('other', Path::new('app_client_show', ['id' => 9])),
            $this->action('external')->setUrl('https://example.org'),
            PrintAction::new(),
        ], $request);

        self::assertSame('/app_client_edit?id=7', $actions->inline[0]->url);
        // The way back to the list is not for the record: no id
        $back = $this->build([ListAction::new(CrudConfigInterface::LABEL_ACTION_LIST, Path::new('app_client_index'))->setBack()], $request);
        self::assertSame('/app_client_index', $back->inline[0]->url);
        self::assertSame(['id' => 9], $actions->inline[1]->params);
        self::assertSame('https://example.org', $actions->inline[2]->url);
        self::assertNull($actions->inline[3]->url);
        self::assertTrue($actions->inline[3]->isPrint());
    }

    public function testRow(): void
    {
        $resource = new class () {
            public function canDelete(): string
            {
                return 'client.active';
            }
        };
        $row = $this->builder()->buildRow([
            ItemAction::new('action.show', Path::new('app_client_show')),
            EditAction::new('action.edit', Path::new('app_client_edit')),
            DeleteAction::new('action.delete', Path::new('app_client_delete')),
            ItemAction::new('action.note', Path::new('app_client_note'))->setModal('note.html.twig'),
            DropdownAction::new('action.status', [[ItemAction::new('action.archive', Path::new('app_client_archive'))]]),
        ], $resource, 12);

        self::assertSame(['action.edit'], $this->labels($row->inline));
        self::assertSame('/app_client_edit?id=12', $row->inline[0]->url);
        self::assertSame(['action.show', 'action.delete', 'action.note', 'action.status'], $this->labels($row->others));
        self::assertSame('client.active', $row->others[1]->state->reason);
        self::assertSame([null, null, null, 'action.status'], array_column($row->getMenu(), 'label'));
        self::assertSame(['action.archive'], $this->labels($row->getMenu()[3]['items']));
        // One dialog per line
        self::assertSame('modal_' . $row->others[2]->action->getId() . '12', $row->getModals()[0]->getModalId());
    }

    /**
     * As before 2.0: in a line, its id wins over the one of the path; in the header, the one of the path wins. An id 0
     * is an id.
     */
    public function testIdOfTheLineWins(): void
    {
        $show = static fn (): ItemAction => ItemAction::new('action.show', Path::new('app_client_show', ['id' => 9, 'tab' => 'notes']));

        self::assertSame(['id' => 12, 'tab' => 'notes'], $this->builder()->buildRow([$show()], new \stdClass(), 12)->others[0]->params);
        self::assertSame(['id' => 9, 'tab' => 'notes'], $this->build([$show()], new Request(attributes: ['id' => 7]))->inline[0]->params);
        self::assertSame('/app_client_edit?id=0', $this->build([$this->action('edit')], new Request(attributes: ['id' => 0]))->inline[0]->url);
        self::assertSame('/app_client_edit?id=0', $this->builder()->buildRow([EditAction::new('action.edit', Path::new('app_client_edit'))], new \stdClass(), 0)->inline[0]->url);
    }

    /**
     * %entity%: the element as a string, else #<id> (an entity without __toString()).
     */
    public function testEntityLabel(): void
    {
        $stringable = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'Bob';
            }
        };
        $edit = EditAction::new('action.edit', Path::new('app_client_edit'));

        self::assertSame('Bob', $this->builder()->buildRow([$edit], $stringable, 3)->entityLabel);
        self::assertSame('#3', $this->builder()->buildRow([$edit], new \stdClass(), 3)->entityLabel);
        self::assertSame('Bob', $this->builder()->buildRow([$edit], $stringable, 3)->inline[0]->getEntityLabel());
        // The id of the line, even when the first action is a DropdownAction (no id in its parameters)
        $dropdown = DropdownAction::new('action.status', [[ItemAction::new('action.archive', Path::new('app_client_archive'))]]);
        self::assertSame('#5', $this->builder()->buildRow([$dropdown], new \stdClass(), 5)->entityLabel);
    }

    /**
     * Shown in a line: the edition and the actions setDropdown(false), in their order; setDropdown(true) puts the
     * edition in the menu; nothing set: in the menu.
     */
    public function testShownInALine(): void
    {
        $row = $this->builder()->buildRow([
            ItemAction::new('action.show', Path::new('app_client_show')),
            ItemAction::new('action.archive', Path::new('app_client_archive'))->setDropdown(false),
            EditAction::new('action.edit', Path::new('app_client_edit')),
            DropdownAction::new('action.status', [[ItemAction::new('action.close', Path::new('app_client_close'))]])->setDropdown(false),
            DeleteAction::new('action.delete', Path::new('app_client_delete')),
        ], new \stdClass(), 1);

        self::assertSame(['action.archive', 'action.edit', 'action.status'], $this->labels($row->inline));
        self::assertSame(['action.show', 'action.delete'], $this->labels($row->others));

        $row = $this->builder()->buildRow([EditAction::new('action.edit', Path::new('app_client_edit'))->setDropdown(true)], new \stdClass(), 1);
        self::assertSame([], $row->inline);
        self::assertSame(['action.edit'], $this->labels($row->others));

        // In the header, nothing set is shown, as before
        self::assertSame(['action.show'], $this->labels($this->build([ItemAction::new('action.show', Path::new('app_client_show'))])->inline));
    }

    /**
     * The heading of a DropdownAction, and of its groups, in the domain of the action.
     */
    public function testDomainOfTheHeadingOfADropdown(): void
    {
        $dropdown = DropdownAction::new('action.status', ['group.state' => [$this->action('archive')]])->setTranslationDomain('admin');
        $actions = $this->build([$dropdown]);

        self::assertSame('admin', $actions->inline[0]->groups[0]['domain']);
        self::assertSame('admin', $actions->getSecondaryGroups()[0]['domain']);
        self::assertSame('admin', $this->builder()->buildRow([$dropdown], new \stdClass(), 1)->getMenu()[0]['domain']);
    }

    public function testRowWithoutEdition(): void
    {
        $row = $this->builder()->buildRow([EditAction::new('action.edit', Path::new('app_client_edit'))->setRole('ROLE_NOBODY')], new \stdClass(), 3);

        self::assertSame([], $row->inline);
        self::assertTrue($row->isEmpty());
    }

    public function testModals(): void
    {
        $actions = $this->build([
            $this->action('export')->setModal('@LleCrudit/modal/_export.html.twig'),
            $this->action('import')->setModal('import.html.twig')->setDisabledReason('no.file'),
            $this->action('note')->setDropdown(true)->setModal('note.html.twig'),
        ]);

        self::assertSame(['export', 'note'], $this->labels($actions->getModals()));
        self::assertSame('modal_' . $actions->inline[0]->action->getId(), $actions->inline[0]->getModalId());
        self::assertTrue((new PageActions())->isEmpty());
    }

    /**
     * @param list<ActionView> $views
     *
     * @return list<string>
     */
    private function labels(array $views): array
    {
        return array_map(static fn (ActionView $view): string => $view->action->getLabel(), $views);
    }

    private function action(string $name): ListAction
    {
        return ListAction::new($name, Path::new('app_client_' . $name));
    }

    /**
     * @param list<\Lle\CruditBundle\Dto\Action\AbstractAction> $actions
     */
    private function build(array $actions, ?Request $request = null): PageActions
    {
        return $this->builder($request)->build($actions);
    }

    private function builder(?Request $request = null): PageActionsBuilder
    {
        $checker = new class () implements AuthorizationCheckerInterface {
            public function isGranted(mixed $attribute, mixed $subject = null, mixed $accessDecision = null): bool
            {
                return $attribute !== 'ROLE_NOBODY';
            }
        };
        $urls = new class () implements UrlGeneratorInterface {
            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                return '/' . $name . ($parameters ? '?' . http_build_query($parameters) : '');
            }

            public function setContext(\Symfony\Component\Routing\RequestContext $context): void
            {
            }

            public function getContext(): \Symfony\Component\Routing\RequestContext
            {
                return new \Symfony\Component\Routing\RequestContext();
            }
        };
        $stack = new RequestStack();
        $stack->push($request ?? new Request());

        return new PageActionsBuilder(new ActionResolver($checker), $urls, $stack);
    }
}
