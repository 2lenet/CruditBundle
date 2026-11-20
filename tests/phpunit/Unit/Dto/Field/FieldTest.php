<?php

namespace phpunit\Unit\Dto\Field;

use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Field\DateField;
use Lle\CruditBundle\Field\DateTimeField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testFirstSortOrderIsAscWithoutType(): void
    {
        self::assertSame(Field::SORT_ASC, Field::new('name')->getFirstSortOrder());
    }

    public function testFirstSortOrderIsAscForText(): void
    {
        self::assertSame(Field::SORT_ASC, Field::new('name')->setType('string')->getFirstSortOrder());
    }

    #[DataProvider('dateTypes')]
    public function testFirstSortOrderIsDescForDates(string $type): void
    {
        self::assertSame(Field::SORT_DESC, Field::new('createdAt')->setType($type)->getFirstSortOrder());
    }

    public function testForcedFirstSortOrderOnText(): void
    {
        $field = Field::new('amount')->setType('integer')->setFirstSortOrder('DESC');

        self::assertSame(Field::SORT_DESC, $field->getFirstSortOrder());
    }

    public function testForcedFirstSortOrderOnDate(): void
    {
        $field = Field::new('birthDate')->setType('date')->setFirstSortOrder(Field::SORT_ASC);

        self::assertSame(Field::SORT_ASC, $field->getFirstSortOrder());
    }

    public function testInvalidFirstSortOrderThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Field::new('name')->setFirstSortOrder('up');
    }

    /**
     * @return iterable<array{string}>
     */
    public static function dateTypes(): iterable
    {
        foreach (
            [
                'date',
                'date_immutable',
                'datetime',
                'datetime_immutable',
                'datetimetz',
                'datetimetz_immutable',
                DateField::class,
                DateTimeField::class,
            ] as $type
        ) {
            yield $type => [$type];
        }
    }
}
