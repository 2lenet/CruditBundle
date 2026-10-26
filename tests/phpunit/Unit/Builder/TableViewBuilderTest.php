<?php

declare(strict_types=1);

namespace phpunit\Unit\Builder;

use Lle\CruditBundle\Builder\TableViewBuilder;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\EditAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Twig\CruditExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The data of the Table of CruditUiBundle for a list: columns and sort links, rows and their cells, tone, actions
 * (shown, in the menu, disabled, modal, confirmation), groups with subtotals, totals, manual order.
 */
class TableViewBuilderTest extends TestCase
{
    /** @var string[] roles the user does not have */
    private array $denied = [];

    private bool $hideIfDisabled = false;

    public function testColumnsAndSort(): void
    {
        $name = Field::new('name')->linkTo(Path::new('app_show'));
        $amount = Field::new('amount', 'currency');
        $this->denied = ['ROLE_X'];
        $params = new DatasourceParams(30, 0, [['amount', 'DESC']]);
        $params->setCount(10);
        $table = $this->build([$name, $amount, Field::new('hidden')->setRole('ROLE_X')], [], request: '/clients?foo=1', params: $params);

        self::assertSame(['name', 'amount'], array_column($table['columns'], 'id'), 'a field without the role has no column');
        self::assertTrue($table['columns'][0]['primary']);
        self::assertFalse($table['columns'][1]['primary']);
        self::assertTrue($table['columns'][1]['card'], 'the cards show the same fields as the table');
        self::assertSame('start', $table['columns'][0]['align']);
        self::assertSame('end', $table['columns'][1]['align'], 'an amount is aligned at the end');
        self::assertSame('/clients?foo=1&client_sort=name&client_sort_order=asc', $table['columns'][0]['sortUrl']);
        self::assertSame('/clients?foo=1&client_sort=amount&client_sort_order=asc', $table['columns'][1]['sortUrl'], 'sorted descending: the link sorts ascending');
        self::assertSame(['column' => 'amount', 'dir' => 'desc'], $table['sort']);
        self::assertSame('Clients, crudit.table.sorted_desc', $table['caption']);
    }

    public function testNotSortableColumnHasNoLink(): void
    {
        $table = $this->build([Field::new('name')->setSortable(false)], []);

        self::assertNull($table['columns'][0]['sortUrl']);
    }

    public function testRowsAndCells(): void
    {
        $name = Field::new('name')->linkTo(Path::new('app_show'));
        $email = Field::new('email');
        $row = $this->line(7, [[$name, '<b>Alice</b> &amp; Co'], [$email, '<a href="mailto:a@b.c">a@b.c</a>']]);
        $table = $this->build([$name, $email], [$row]);

        $built = $table['rows'][0];
        self::assertSame('7', $built['id']);
        self::assertSame('list-client-7', $built['domId']);
        self::assertSame('Alice & Co', $built['label'], 'the label names the boxes and the actions: a text');
        self::assertSame('/app_show?id=7', $built['url'], 'the row opens the link of its first linked cell');
        self::assertSame('<b>Alice</b> &amp; Co', $built['cells']['name']['html']);
        self::assertSame('/app_show?id=7', $built['cells']['name']['url']);
        self::assertNull($built['cells']['email']['url']);
        self::assertSame('list-client-7-name-name', $built['cells']['name']['id'], 'same id as before: the scripts that refresh a cell find it');
        self::assertSame([], $table['groups']);
    }

    public function testRowWithoutLinkAndToneOfTheResource(): void
    {
        $resource = new class () {
            public function getCssClass(): string
            {
                return 'table-danger';
            }
        };
        $table = $this->build([Field::new('name')], [$this->line(1, [[Field::new('name'), 'A']], $resource)]);

        self::assertNull($table['rows'][0]['url']);
        self::assertSame('danger', $table['rows'][0]['tone']);
    }

    public function testActions(): void
    {
        $edit = EditAction::new('action.edit', Path::new('app_edit'), null);
        $show = ItemAction::new('action.show', Path::new('app_show'));
        $delete = DeleteAction::new('action.delete', Path::new('app_delete'))->setConfirmModal(true);
        $table = $this->build([Field::new('name')], [$this->line(3, [[Field::new('name'), 'A']])], actions: [$show, $edit, $delete]);

        [$a, $b, $c] = $table['rows'][0]['actions'];
        self::assertFalse($a['visible'], 'the show action is in the menu');
        self::assertSame('/app_show?id=3', $a['url']);
        self::assertTrue($b['visible'], 'the edit action is shown as an icon');
        self::assertSame('/app_edit?id=3', $b['url']);
        self::assertFalse($c['visible']);
        self::assertSame('danger', $c['tone']);
        self::assertNull($c['url'], 'a confirmation is a button');
        self::assertSame(['data-confirm-link' => '/app_delete?id=3', 'data-confirm-text' => 'modal.confirm.action.delete'], $c['attr']);
    }

    public function testActionWithUrlTargetAndIcon(): void
    {
        $action = ItemAction::new('export', Path::new('app_x'), \Lle\CruditBundle\Dto\Icon::new('trash-alt'))->setUrl('/export')->setTarget('_blank');
        $table = $this->build([Field::new('name')], [$this->line(1, [[Field::new('name'), 'A']])], actions: [$action]);

        $built = $table['rows'][0]['actions'][0];
        self::assertSame('/export', $built['url']);
        self::assertSame(['target' => '_blank'], $built['attr']);
        self::assertSame('delete', $built['icon'], 'the Font Awesome name of 1.x becomes the name of the catalogue');
    }

    public function testModalActionOpensItsDialog(): void
    {
        $action = ItemAction::new('action.send', Path::new('app_send'))->setModal('@App/modal.html.twig');
        $table = $this->build([Field::new('name')], [$this->line(5, [[Field::new('name'), 'A']])], actions: [$action]);

        $built = $table['rows'][0]['actions'][0];
        $id = $table['modals'][0]['id'];
        self::assertSame(['commandfor' => $id, 'command' => 'show-modal'], $built['attr']);
        self::assertNull($built['url']);
        self::assertSame('@App/modal.html.twig', $table['modals'][0]['template']);
        self::assertSame($action, $table['modals'][0]['action']);
        self::assertSame(5, $table['modals'][0]['line']->getId());
    }

    public function testDisabledAction(): void
    {
        $this->denied = ['ROLE_EDIT'];
        $action = EditAction::new('action.edit', Path::new('app_edit'))->setRole('ROLE_EDIT');
        $table = $this->build([Field::new('name')], [$this->line(1, [[Field::new('name'), 'A']])], actions: [$action]);

        $built = $table['rows'][0]['actions'][0];
        self::assertSame('crudit.action.unavailable', $built['disabledReason']);
        self::assertNull($built['url'], 'a disabled action is not a link');

        $this->hideIfDisabled = true;
        $table = $this->build([Field::new('name')], [$this->line(1, [[Field::new('name'), 'A']])], actions: [$action]);
        self::assertSame([], $table['rows'][0]['actions'], 'hidden when disabled');
    }

    public function testActionNotDisplayed(): void
    {
        $action = EditAction::new('action.edit', Path::new('app_edit'))->setDisplayIf(fn (object $resource) => false);
        $table = $this->build([Field::new('name')], [$this->line(1, [[Field::new('name'), 'A']])], actions: [$action]);

        self::assertSame([], $table['rows'][0]['actions']);
    }

    public function testDropdownActionIsAGroupOfTheMenu(): void
    {
        $dropdown = DropdownAction::new('more', [
            'Documents' => [ItemAction::new('print', Path::new('app_print'))],
            [ItemAction::new('copy', Path::new('app_copy'))],
        ]);
        $table = $this->build([Field::new('name')], [$this->line(2, [[Field::new('name'), 'A']])], actions: [$dropdown]);

        [$documents, $others] = $table['rows'][0]['actions'];
        self::assertSame('Documents', $documents['label']);
        self::assertSame('/app_print?id=2', $documents['items'][0]['url']);
        self::assertSame('more', $others['label'], 'the actions without a group name are under the label of the dropdown');
        self::assertArrayNotHasKey('visible', $documents);
    }

    public function testGroupsWithSubtotals(): void
    {
        $name = Field::new('name');
        $amount = Field::new('amount', 'currency');
        $lines = [
            $this->line(1, [[$name, 'A'], [$amount, '10']])->setRuptGroup1('<i>Paris</i>', 'paris'),
            $this->line(2, [[$name, 'B'], [$amount, '20']]),
            $this->line(3, [[$name, 'C'], [$amount, '5']])->setRuptGroup1('Lyon', 'lyon'),
        ];
        $table = $this->build([$name, $amount], $lines, groupedTotals: ['paris' => [$this->fieldView($amount, '30')]]);

        self::assertSame([], $table['rows']);
        self::assertCount(2, $table['groups']);
        self::assertSame('Paris', $table['groups'][0]['label'], 'the label of a group is a text');
        self::assertSame(['1', '2'], array_column($table['groups'][0]['rows'], 'id'));
        self::assertSame(['amount' => ['html' => '30']], $table['groups'][0]['subtotals']);
        self::assertSame([], $table['groups'][1]['subtotals']);
        self::assertArrayNotHasKey('children', $table['groups'][0]);
    }

    public function testTwoLevelsOfGroups(): void
    {
        $name = Field::new('name');
        $lines = [
            $this->line(1, [[$name, 'A']])->setRuptGroup1('2026', '2026')->setRuptGroup2('January', '2026-01'),
            $this->line(2, [[$name, 'B']])->setRuptGroup2('February', '2026-02'),
        ];
        $table = $this->build([$name], $lines);

        self::assertCount(1, $table['groups']);
        self::assertArrayNotHasKey('rows', $table['groups'][0]);
        self::assertSame(['January', 'February'], array_column($table['groups'][0]['children'], 'label'));
        self::assertSame(['2'], array_column($table['groups'][0]['children'][1]['rows'], 'id'));
    }

    public function testTotals(): void
    {
        $name = Field::new('name');
        $amount = Field::new('amount', 'currency');
        $tax = Field::new('tax', 'currency')->setLabel('Tax');
        $totals = [$this->fieldView($amount, '35'), $this->fieldView($name, 'n'), $this->fieldView($tax, '7')];
        $table = $this->build([$name, $amount], [], totals: $totals);

        self::assertSame(['column' => 'amount', 'html' => '35'], $table['totals'][0]);
        self::assertTrue($table['totals'][1]['standalone'], 'a total of the first column (it holds the label) goes under the table');
        self::assertSame(['label' => 'Tax', 'html' => '7', 'standalone' => true], $table['totals'][2], 'a total of a field without a column too');
    }

    public function testManualOrder(): void
    {
        $table = $this->build([Field::new('name')], [], sortable: true);
        self::assertSame(['url' => '/app_sort'], $table['sortable']);

        $this->denied = ['ROLE_client_EDIT'];
        $table = $this->build([Field::new('name')], [], sortable: true);
        self::assertFalse($table['sortable'], 'only for those who can edit');

        $this->denied = [];
        self::assertFalse($this->build([Field::new('name')], [])['sortable']);
    }

    public function testCaptionWithPage(): void
    {
        $params = new DatasourceParams(10, 10, [['name', 'ASC']]);
        $params->setCount(35);
        $table = $this->build([Field::new('name')], [], params: $params);

        self::assertSame('Clients, crudit.table.sorted_asc, crudit.table.page', $table['caption']);
    }

    /**
     * @param Field[]        $fields
     * @param ResourceView[] $lines
     * @param FieldView[]    $totals
     */
    private function build(
        array $fields,
        array $lines,
        array $actions = [],
        array $totals = [],
        array $groupedTotals = [],
        array $sorts = [['name', 'ASC']],
        bool $sortable = false,
        string $request = '/clients',
        ?DatasourceParams $params = null,
    ): array {
        $crudConfig = $this->createStub(CrudConfigInterface::class);
        $crudConfig->method('getPath')->willReturn(Path::new('app_brick'));
        $brick = $this->createStub(BrickConfigInterface::class);
        $brick->method('getId')->willReturn('brick1');
        $brick->method('getOptions')->willReturn([]);
        $brick->method('getCrudConfig')->willReturn($crudConfig);

        $params ??= new DatasourceParams(30, 0, $sorts);
        $view = new BrickView($brick);
        $view->setConfig([
            'name' => 'client',
            'title' => 'Clients',
            'fields' => $fields,
            'actions' => $actions,
            'datasource_params' => $params,
            'sortable' => $sortable,
            'sort_url' => Path::new('app_sort'),
            'translation_domain' => 'app',
        ]);
        $view->setData(['lines' => $lines, 'totals' => $totals, 'grouped_totals' => $groupedTotals]);

        return $this->builder($request)->build($view);
    }

    private function builder(string $request): TableViewBuilder
    {
        $denied = $this->denied;
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(fn (string $role) => !in_array($role, $denied, true));

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id) => $id);

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(
            fn (string $route, array $params = []) => '/' . $route . ($params ? '?' . http_build_query($params) : '')
        );

        $requests = new RequestStack();
        $requests->push(Request::create($request));

        $extension = $this->createStub(CruditExtension::class);
        $extension->method('hideIfDisabled')->willReturn($this->hideIfDisabled);

        return new TableViewBuilder($checker, $translator, $urls, $requests, $extension);
    }

    /**
     * @param array<array{0: Field, 1: string}> $cells
     */
    private function line(int $id, array $cells, ?object $resource = null): ResourceView
    {
        return new ResourceView($id, $resource ?? new \stdClass(), array_map(fn (array $c) => $this->fieldView($c[0], $c[1]), $cells));
    }

    private function fieldView(Field $field, string $html): FieldView
    {
        return (new FieldView($field, $html))->setStringValue($html);
    }
}
