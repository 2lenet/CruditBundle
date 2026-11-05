<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter\FilterType;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Filter\FilterType\DateTimeFilterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The date and time a DateTime filter compares with: the datetime-local of the bar, and the date and time apart of the 1.x.
 */
class DateTimeFilterTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function values(): iterable
    {
        yield 'datetime-local' => ['2026-11-05T10:30', '', '2026-11-05 10:30:00'];
        yield 'datetime-local with seconds' => ['2026-11-05T10:30:15', '', '2026-11-05 10:30:15'];
        yield 'the time of the value wins' => ['2026-11-05T10:30', '23:59:59', '2026-11-05 10:30:00'];
        yield '1.x, date and time' => ['2026-11-05', '10:30:00', '2026-11-05 10:30:00'];
        yield '1.x, time without seconds' => ['2026-11-05', '10:30', '2026-11-05 10:30:00'];
        yield 'date only' => ['2026-11-05', '', '2026-11-05 00:00:00'];
    }

    #[DataProvider('values')]
    public function testTheValueToCompareWith(string $value, string $time, string $expected): void
    {
        self::assertSame($expected, DateTimeFilterType::dateTime($value, $time));
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('applied')]
    public function testApply(array $data, string $expected): void
    {
        $queryBuilder = (new QueryBuilder($this->createMock(EntityManagerInterface::class)))->select('e')->from('App\\Entity\\Visit', 'e');
        $filter = DateTimeFilterType::new('at');
        $filter->setData($data);
        $filter->apply($queryBuilder);

        self::assertCount(1, $queryBuilder->getParameters());
        self::assertSame($expected, $queryBuilder->getParameters()->first()->getValue());
        self::assertStringContainsString('e.at = :', (string) $queryBuilder->getDQL());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function applied(): iterable
    {
        // What FilterState keeps after the form of the bar: `time` is emptied (it is an additional key not sent).
        yield 'the bar' => [['op' => 'eq', 'value' => '2026-11-05T10:30', 'time' => ''], '2026-11-05 10:30:00'];
        yield 'a 1.x session' => [['op' => 'eq', 'value' => '2026-11-05', 'time' => '10:30:00'], '2026-11-05 10:30:00'];
        yield 'a link with a date' => [['op' => 'eq', 'value' => '2026-11-05'], '2026-11-05 00:00:00'];
    }
}
