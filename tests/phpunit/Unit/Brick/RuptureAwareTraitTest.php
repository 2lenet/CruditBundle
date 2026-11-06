<?php

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\RuptureAwareTrait;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Registry\FieldRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RuptureAwareTraitTest extends TestCase
{
    public function testDateRuptureFollowsClickedSortDirection(): void
    {
        $ruptField = Field::new('orderDate')->setRuptGroup(1)->setRuptDateFormat('Y-m')->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['orderDate', 'DESC']], [$ruptField]);

        // The date column is not filtered out: rows inside a group keep following the date.
        self::assertSame([
            ["DATE_FORMAT(root.orderDate, '%Y-%m')", 'DESC'],
            ['orderDate', 'DESC'],
        ], $sorts);
    }

    public function testRuptureStaysAscWhenSortedOnAnotherField(): void
    {
        $ruptField = Field::new('orderDate')->setRuptGroup(1)->setRuptDateFormat('Y-m')->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['amount', 'DESC']], [$ruptField]);

        self::assertSame([
            ["DATE_FORMAT(root.orderDate, '%Y-%m')", 'ASC'],
            ['amount', 'DESC'],
        ], $sorts);
    }

    public function testNonDateRuptureFollowsClickedSortDirection(): void
    {
        $ruptField = Field::new('client')->setRuptGroup(1)->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['client', 'desc']], [$ruptField]);

        self::assertSame([['client', 'DESC']], $sorts);
    }

    public function testRuptureWithSortPropertyFollowsClickedSortDirection(): void
    {
        $ruptField = Field::new('client', null, ['sortProperty' => 'client.name'])
            ->setRuptGroup(1)
            ->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['client.name', 'DESC']], [$ruptField]);

        self::assertSame([['client', 'DESC']], $sorts);
    }

    public function testRuptureSortsAppendedWhenNoPriority(): void
    {
        $ruptField = Field::new('client')->setRuptGroup(1);

        $sorts = $this->prependRuptureSorts([['amount', 'DESC']], [$ruptField]);

        self::assertSame([['amount', 'DESC'], ['client', 'ASC']], $sorts);
    }

    public function testMissingSortOrderFallsBackToAsc(): void
    {
        $ruptField = Field::new('client')->setRuptGroup(1)->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['client', null]], [$ruptField]);

        self::assertSame([['client', 'ASC']], $sorts);
    }

    public function testTwoLevelRupturesAreIndependent(): void
    {
        $rupt1 = Field::new('client')->setRuptGroup(1)->withRuptSortPriority();
        $rupt2 = Field::new('status')->setRuptGroup(2);

        $sorts = $this->prependRuptureSorts([['client', 'DESC']], [$rupt1, $rupt2]);

        self::assertSame([['client', 'DESC'], ['status', 'ASC']], $sorts);
    }

    /** @param Field[] $fields */
    private function prependRuptureSorts(array $sorts, array $fields): array
    {
        $brick = new class {
            use RuptureAwareTrait {
                prependRuptureSorts as public;
            }

            // Required by the trait; unused by prependRuptureSorts().
            protected FieldRegistry $fieldRegistry;
            protected TranslatorInterface $translator;
        };
        $dsParams = new DatasourceParams(sorts: $sorts);
        $brick->prependRuptureSorts($dsParams, $fields);

        return $dsParams->getSorts();
    }
}
