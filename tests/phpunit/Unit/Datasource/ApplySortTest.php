<?php

namespace phpunit\Unit\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Lle\CruditBundle\Filter\FilterState;
use PHPUnit\Framework\TestCase;

class ApplySortTest extends TestCase
{
    private const SORT_FIELD = 'position';

    /** @var array<string, \stdClass> */
    private array $items = [];

    public function testUnfilteredListKeepsSamePositionsInNewOrder(): void
    {
        $this->givenItems(['a' => 0, 'b' => 1, 'c' => 2]);

        $this->datasource()->applySort(['c', 'a', 'b'], self::SORT_FIELD);

        self::assertSame(['c' => 0, 'a' => 1, 'b' => 2], $this->positions(['c', 'a', 'b']));
    }

    public function testFilteredReorderOnlyReusesVisiblePositions(): void
    {
        // a, b, c are hidden by a filter; only d, e, f are displayed and reordered.
        $this->givenItems(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5]);

        $this->datasource()->applySort(['f', 'd', 'e'], self::SORT_FIELD);

        self::assertSame(
            ['a' => 0, 'b' => 1, 'c' => 2, 'f' => 3, 'd' => 4, 'e' => 5],
            $this->positions(['a', 'b', 'c', 'f', 'd', 'e'])
        );
    }

    public function testSecondPageReorderStaysAfterFirstPage(): void
    {
        $this->givenItems(['a' => 10, 'b' => 20, 'c' => 30, 'd' => 40]);

        $this->datasource()->applySort(['d', 'c'], self::SORT_FIELD);

        self::assertSame(['a' => 10, 'b' => 20, 'd' => 30, 'c' => 40], $this->positions(['a', 'b', 'd', 'c']));
    }

    public function testNullPositionsAreNumberedFromZero(): void
    {
        $this->givenItems(['a' => null, 'b' => null, 'c' => null]);

        $this->datasource()->applySort(['b', 'c', 'a'], self::SORT_FIELD);

        self::assertSame(['b' => 0, 'c' => 1, 'a' => 2], $this->positions(['b', 'c', 'a']));
    }

    public function testDuplicatedPositionsBecomeDistinct(): void
    {
        $this->givenItems(['a' => 2, 'b' => 2, 'c' => 5]);

        $this->datasource()->applySort(['b', 'a', 'c'], self::SORT_FIELD);

        self::assertSame(['b' => 2, 'a' => 3, 'c' => 5], $this->positions(['b', 'a', 'c']));
    }

    public function testNullPositionsGoAfterNumberedOnes(): void
    {
        $this->givenItems(['a' => 3, 'b' => null, 'c' => 7]);

        $this->datasource()->applySort(['b', 'c', 'a'], self::SORT_FIELD);

        self::assertSame(['b' => 3, 'c' => 7, 'a' => 8], $this->positions(['b', 'c', 'a']));
    }

    public function testUnknownIdIsIgnored(): void
    {
        $this->givenItems(['a' => 0, 'b' => 1]);

        $this->datasource()->applySort(['b', 'missing', 'a'], self::SORT_FIELD);

        self::assertSame(['b' => 0, 'a' => 1], $this->positions(['b', 'a']));
    }

    /** @param array<string, int|null> $positions */
    private function givenItems(array $positions): void
    {
        foreach ($positions as $id => $position) {
            $item = new \stdClass();
            $item->position = $position;
            $this->items[$id] = $item;
        }
    }

    /**
     * @param string[] $ids
     *
     * @return array<string, int|null>
     */
    private function positions(array $ids): array
    {
        $positions = [];
        foreach ($ids as $id) {
            $positions[$id] = $this->items[$id]->position;
        }

        return $positions;
    }

    private function datasource(): AbstractDoctrineDatasource
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $items = $this->items;

        $filterState = $this->createMock(FilterState::class);

        return new class ($entityManager, $filterState, $items) extends AbstractDoctrineDatasource {
            /** @param array<string, \stdClass> $items */
            public function __construct(
                EntityManagerInterface $entityManager,
                FilterState $filterState,
                private array $items,
            ) {
                parent::__construct($entityManager, $filterState);
            }

            public function getClassName(): string
            {
                return \stdClass::class;
            }

            public function get($id): ?object
            {
                return $this->items[$id] ?? null;
            }
        };
    }
}
