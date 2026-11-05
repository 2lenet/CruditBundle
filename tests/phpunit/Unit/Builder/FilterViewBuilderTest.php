<?php

declare(strict_types=1);

namespace phpunit\Unit\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Builder\FilterViewBuilder;
use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\DateFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberRangeFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use Lle\CruditUiBundle\Format\Formatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The data of the Filter:Bar of CruditUiBundle: the type of each filter, its conditions, whether it is active, its value in
 * words (the chip), its choices, and the filters that keep a template of the project.
 */
class FilterViewBuilderTest extends TestCase
{
    private function builder(?Formatter $formatter = null): FilterViewBuilder
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id, array $parameters = [], ?string $domain = null): string => $id . ($parameters ? ' ' . json_encode($parameters) : ''));

        if ($formatter === null) {
            $formatter = $this->createMock(Formatter::class);
            $formatter->method('date')->willReturnCallback(static fn (mixed $date): string => 'D:' . $date);
            $formatter->method('datetime')->willReturnCallback(static fn (mixed $date): string => 'DT:' . $date);
        }

        $stack = new RequestStack();
        $stack->push(Request::create('/clients/?x=1'));

        $twig = new Environment(new ArrayLoader([
            '@LleCrudit/filter/type/string_filter.html.twig' => '',
            '@LleCrudit/filter/type/boolean_filter.html.twig' => '',
            '@LleCrudit/filter/type/choice_filter.html.twig' => '',
            '@LleCrudit/filter/type/number_filter.html.twig' => '',
            '@LleCrudit/filter/type/numberrange_filter.html.twig' => '',
            '@LleCrudit/filter/type/date_filter.html.twig' => '',
            '@LleCrudit/filter/type/periode_filter.html.twig' => '',
            'project/own.html.twig' => '',
        ]));

        return new FilterViewBuilder($translator, $stack, $this->createMock(EntityManagerInterface::class), $formatter, $twig);
    }

    private function view(): BrickView
    {
        $crud = $this->createMock(CrudConfigInterface::class);
        $crud->method('getPath')->willReturn(Path::new('app_index'));
        $brick = $this->createMock(BrickConfigInterface::class);
        $brick->method('getId')->willReturn('filter');
        $brick->method('getOptions')->willReturn([]);
        $brick->method('getCrudConfig')->willReturn($crud);

        return new BrickView($brick);
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed> the item of the filter
     */
    private function item(StringFilterType|BooleanFilterType|ChoiceFilterType|NumberFilterType|NumberRangeFilterType|DateFilterType|PeriodeFilterType $filter, ?array $data = null): array
    {
        $filter->setData($data);
        $set = new class () extends AbstractFilterSet {
            public function getId(): string
            {
                return 'client';
            }
        };
        $view = $this->view();
        $view->setConfig(['translation_domain' => 'messages', 'title' => 'Clients']);

        return $this->builder()->build($set, ['client_' . $filter->getId() => $filter], $view)['filters'][0];
    }

    public function testThePropsOfTheBar(): void
    {
        $set = new class () extends AbstractFilterSet {
            public function getId(): string
            {
                return 'client';
            }
        };
        $view = $this->view();
        $view->setConfig(['translation_domain' => 'messages', 'title' => 'Clients']);
        $bar = $this->builder()->build($set, [], $view);

        self::assertSame('crudit-filters-client', $bar['id']);
        self::assertSame('/clients/?client_reset=1', $bar['resetUrl']);
        self::assertSame('client_filter', $bar['applyName']);
        self::assertSame([], $bar['filters']);
    }

    public function testATextFilter(): void
    {
        $item = $this->item(StringFilterType::new('name')->setPrimary(true), ['op' => 'contains', 'value' => 'boiler']);

        self::assertEqualsCanonicalizing(['id' => 'name', 'name' => 'filter_client_name', 'type' => 'text', 'operator' => 'contains', 'value' => 'boiler', 'primary' => true, 'hidden' => false, 'active' => true, 'summary' => 'boiler'], array_intersect_key($item, array_flip(['id', 'name', 'type', 'operator', 'value', 'primary', 'hidden', 'active', 'summary'])));
        self::assertSame(['contains', 'startswith', 'endswith', 'eq', 'neq', 'isnull', 'isnotnull'], array_column($item['operators'], 'value'));
        self::assertSame('operator-contains', $item['operators'][0]['icon']);
        self::assertSame([false, false, false, false, false, true, true], array_column($item['operators'], 'noValue'));
    }

    public function testNoMainFilterByDefault(): void
    {
        self::assertFalse($this->item(StringFilterType::new('name'))['primary']);
    }

    public function testAnEmptyFilterIsNotActive(): void
    {
        $item = $this->item(StringFilterType::new('name'), ['op' => 'contains', 'value' => '']);

        self::assertFalse($item['active']);
        self::assertSame('', $item['summary']);
        self::assertFalse($this->item(StringFilterType::new('name'))['active'], 'no data');
    }

    public function testTheConditionIsToldWhenItIsNotTheFirst(): void
    {
        $item = $this->item(StringFilterType::new('name'), ['op' => 'neq', 'value' => 'bob']);

        self::assertSame('crudit.filter.operator.neq bob', $item['summary']);
    }

    public function testIsEmptyIsActiveWithItsLabel(): void
    {
        $item = $this->item(StringFilterType::new('name'), ['op' => 'isnull', 'value' => '']);

        self::assertTrue($item['active']);
        self::assertSame('crudit.filter.operator.isnull', $item['summary']);
    }

    public function testAChoice(): void
    {
        $filter = ChoiceFilterType::new('status', ['client.status.prospect' => 'prospect', 'client.status.customer' => 'customer'], true);
        $item = $this->item($filter, ['op' => 'in', 'value' => 'customer,prospect']);

        self::assertSame('choice', $item['type']);
        self::assertSame([['value' => 'prospect', 'label' => 'client.status.prospect'], ['value' => 'customer', 'label' => 'client.status.customer']], $item['choices']);
        self::assertSame('client.status.customer, client.status.prospect', $item['summary']);
        self::assertSame('customer,prospect', $item['value']);
    }

    public function testAListOfChoicesWithoutKeys(): void
    {
        $item = $this->item(ChoiceFilterType::new('color', ['red', 'blue']));

        self::assertSame([['value' => 'red', 'label' => 'red'], ['value' => 'blue', 'label' => 'blue']], $item['choices']);
    }

    public function testABoolean(): void
    {
        self::assertSame('boolean', $this->item(BooleanFilterType::new('active'))['type']);
        self::assertFalse($this->item(BooleanFilterType::new('active'), ['op' => 'eq', 'value' => 'all'])['active'], 'All is no filter');
        $item = $this->item(BooleanFilterType::new('active'), ['op' => 'eq', 'value' => 'true']);
        self::assertTrue($item['active']);
        self::assertSame('crudit.boolean.yes', $item['summary']);
    }

    public function testARangeAndAPeriod(): void
    {
        $range = $this->item(NumberRangeFilterType::new('progress'), ['op' => 'interval', 'value' => '10', 'to' => '50']);
        self::assertSame('range', $range['type']);
        self::assertSame('10 – 50', $range['summary']);
        self::assertSame('50', $range['to']);
        self::assertSame('≤ 50', $this->item(NumberRangeFilterType::new('progress'), ['op' => 'interval', 'value' => '', 'to' => '50'])['summary']);

        $period = $this->item(PeriodeFilterType::new('createdAt'), ['op' => 'interval', 'value' => '2026-10-01', 'to' => '2026-10-31']);
        self::assertSame('period', $period['type']);
        self::assertStringContainsString('crudit_ui.filter.period.range', $period['summary']);
        self::assertStringContainsString('D:2026-10-01', $period['summary']);
    }

    public function testADateAndANumber(): void
    {
        self::assertSame('date', $this->item(DateFilterType::new('day'), ['op' => 'after', 'value' => '2026-10-13'])['type']);
        self::assertSame('number', $this->item(NumberFilterType::new('amount'))['type']);
    }

    public function testAHiddenFilterIsFlagged(): void
    {
        self::assertTrue($this->item(StringFilterType::new('notes')->setHidden(true), ['op' => 'contains', 'value' => 'x'])['hidden']);
    }

    public function testAFilterOfTheBundleIsAFieldAndOneWithATemplateOfItsOwnIsIncluded(): void
    {
        $plain = $this->item(StringFilterType::new('name'));
        self::assertArrayNotHasKey('template', $plain);

        $own = new class ('name') extends StringFilterType {
            public function getTemplate(): string
            {
                return 'project/own.html.twig';
            }
        };
        $item = $this->item($own, ['op' => 'contains', 'value' => 'x']);

        self::assertSame('legacy', $item['type']);
        self::assertSame('project/own.html.twig', $item['template']);
        self::assertSame('filter_client_name', $item['context']['id']);
        self::assertSame($own, $item['context']['filtertype']);
        self::assertSame(['contains', 'startswith', 'endswith', 'eq', 'neq', 'isnull', 'isnotnull'], array_column($item['context']['operators'], 'value'));
        self::assertTrue($item['active']);
    }
}
