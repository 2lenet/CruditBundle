<?php

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\RuptureAwareTrait;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use PHPUnit\Framework\TestCase;

class RuptureAwareTraitTest extends TestCase
{
    public function testDateRuptureFollowsClickedSortDirection(): void
    {
        $ruptField = Field::new('orderDate')->setRuptGroup(1)->setRuptDateFormat('Y-m')->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['orderDate', 'DESC']], [$ruptField]);

        self::assertSame("DATE_FORMAT(root.orderDate, '%Y-%m')", $sorts[0][0]);
        self::assertSame('DESC', $sorts[0][1]);
    }

    public function testRuptureStaysAscWhenSortedOnAnotherField(): void
    {
        $ruptField = Field::new('orderDate')->setRuptGroup(1)->setRuptDateFormat('Y-m')->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['amount', 'DESC']], [$ruptField]);

        self::assertSame('ASC', $sorts[0][1]);
    }

    public function testNonDateRuptureFollowsClickedSortDirection(): void
    {
        $ruptField = Field::new('client')->setRuptGroup(1)->withRuptSortPriority();

        $sorts = $this->prependRuptureSorts([['client', 'desc']], [$ruptField]);

        self::assertSame([['client', 'DESC']], $sorts);
    }

    /** @param Field[] $fields */
    private function prependRuptureSorts(array $sorts, array $fields): array
    {
        $brick = new class {
            use RuptureAwareTrait {
                prependRuptureSorts as public;
            }
        };
        $dsParams = new DatasourceParams(sorts: $sorts);
        $brick->prependRuptureSorts($dsParams, $fields);

        return $dsParams->getSorts();
    }
}
