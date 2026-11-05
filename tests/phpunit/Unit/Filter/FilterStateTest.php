<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * What the form of the filters sends, and what the session keeps.
 */
class FilterStateTest extends TestCase
{
    private Session $session;

    private FilterState $state;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $set = new class () extends AbstractFilterSet {
            public function getId(): string
            {
                return 'client';
            }

            public function getFilters(): array
            {
                return [
                    StringFilterType::new('name'),
                    ChoiceFilterType::new('status', ['a' => 'a', 'b' => 'b', 'c' => 'c'], true)->setAdditionnalKeys(['items']),
                    NumberFilterType::new('amount'),
                ];
            }
        };
        $this->state = new FilterState([$set], $this->createMock(Security::class), new RequestStack());
    }

    /**
     * @return array<string, mixed>
     */
    private function data(string $id): array
    {
        return $this->state->getData('client', $id) ?? [];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function send(array $query): void
    {
        $request = Request::create('/clients/', 'GET', $query);
        $request->setSession($this->session);
        $this->state->handleRequest($request);
    }

    public function testAValueAndItsCondition(): void
    {
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => 'bob']);

        self::assertSame(['op' => 'contains', 'value' => 'bob'], $this->data('name'));
    }

    public function testAListIsJoinedWithCommas(): void
    {
        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => ['a', 'c']]);

        self::assertSame('a,c', $this->data('status')['value']);
    }

    public function testAConditionWithNoValueEmptiesTheFilter(): void
    {
        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => ['a']]);
        $this->send(['filter_client_status_op' => 'in']);

        self::assertSame('', $this->data('status')['value']);
    }

    public function testAFilterThatIsNotInTheFormIsLeftAlone(): void
    {
        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => ['a']]);
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => 'x']);

        self::assertSame('a', $this->data('status')['value']);
    }

    public function testAnUnknownConditionIsRefusedWithItsValue(): void
    {
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => 'bob']);
        $this->send(['filter_client_name_op' => 'drop table', 'filter_client_name_value' => 'eve']);

        self::assertSame(['op' => 'contains', 'value' => 'bob'], $this->data('name'));
    }

    public function testTheConditionsOfAProjectTypeAreAccepted(): void
    {
        $set = new class () extends AbstractFilterSet {
            public function getId(): string
            {
                return 'client';
            }

            public function getFilters(): array
            {
                return [new class ('year') extends StringFilterType {
                    public function getOperators(): array
                    {
                        return ['thisyear' => ['icon' => 'x']];
                    }
                }];
            }
        };
        $state = new FilterState([$set], $this->createMock(Security::class), new RequestStack());
        $request = Request::create('/', 'GET', ['filter_client_year_op' => 'thisyear', 'filter_client_year_value' => '1']);
        $request->setSession($this->session);
        $state->handleRequest($request);

        self::assertSame('thisyear', ((array) $state->getData('client', 'year'))['op'] ?? null);
    }

    public function testAValueOfAnotherShapeIsIgnored(): void
    {
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => 'bob']);
        $this->send(['filter_client_name_value' => ['a' => ['b' => 'c']], 'filter_client_amount_value' => [['x']]]);

        self::assertSame('bob', $this->data('name')['value']);
        self::assertArrayNotHasKey('value', $this->data('amount'));
    }

    public function testTheSizeOfAValueAndOfAListIsLimited(): void
    {
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => str_repeat('é', 5000)]);
        self::assertSame(1000, mb_strlen($this->data('name')['value']));

        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => range(1, 501)]);
        self::assertArrayNotHasKey('value', $this->data('status'), 'a list of 501 is refused');
    }

    public function testTheItemsOfTheAutocompleteAreDroppedWhenTheValueChanges(): void
    {
        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => 'a', 'filter_client_status_items' => '[{"id":"a"}]']);
        self::assertSame('[{"id":"a"}]', $this->data('status')['items'] ?? null);

        $this->send(['filter_client_status_op' => 'in', 'filter_client_status_value' => ['b']]);
        self::assertArrayNotHasKey('items', $this->data('status'));
    }

    public function testResetBringsTheDefaultsBack(): void
    {
        $this->send(['filter_client_name_op' => 'contains', 'filter_client_name_value' => 'bob']);
        $this->send(['client_reset' => '1']);

        self::assertArrayNotHasKey('value', $this->data('name'));
    }
}
