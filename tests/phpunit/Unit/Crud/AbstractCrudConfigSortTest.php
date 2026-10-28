<?php

namespace phpunit\Unit\Crud;

use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Datasource\DatasourceParams;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Le tri vient de l'URL (`<crud>_sort`, `<crud>_sort_order`) puis est concaténé dans le ORDER BY DQL :
 * seul un chemin de propriété et un sens ASC/DESC doivent passer.
 */
class AbstractCrudConfigSortTest extends TestCase
{
    private const DEFAULT_SORT = [['name', 'ASC']];

    public function testAPropertyPathAndADirectionAreKept(): void
    {
        $params = $this->datasourceParams(['booking_sort' => 'customer.name', 'booking_sort_order' => 'desc']);

        self::assertSame([['customer.name', 'DESC']], $params->getSorts());
    }

    public function testAMissingDirectionIsKeptEmpty(): void
    {
        $params = $this->datasourceParams(['booking_sort' => 'name']);

        self::assertSame([['name', null]], $params->getSorts());
    }

    #[DataProvider('injectedSortFields')]
    public function testASortFieldThatIsNotAPropertyPathIsIgnored(string $sortField): void
    {
        $params = $this->datasourceParams(['booking_sort' => $sortField, 'booking_sort_order' => 'asc']);

        self::assertSame(self::DEFAULT_SORT, $params->getSorts());
    }

    public static function injectedSortFields(): iterable
    {
        yield 'function' => ['LENGTH(root.password)'];
        yield 'subquery' => ['(SELECT 1 FROM App\Entity\User u)'];
        yield 'expression' => ['root.id, root.name'];
        yield 'space' => ['root.id DESC'];
        yield 'empty' => [''];
        yield 'trailing dot' => ['customer.'];
    }

    #[DataProvider('injectedSortOrders')]
    public function testASortOrderOtherThanAscOrDescIsDropped(string $sortOrder): void
    {
        $params = $this->datasourceParams(['booking_sort' => 'name', 'booking_sort_order' => $sortOrder]);

        self::assertSame([['name', null]], $params->getSorts());
    }

    public static function injectedSortOrders(): iterable
    {
        yield 'expression' => ['ASC, root.password'];
        yield 'subquery' => ['ASC, (SELECT 1 FROM App\Entity\User u)'];
        yield 'unknown' => ['up'];
    }

    private function datasourceParams(array $query): DatasourceParams
    {
        $request = new Request($query);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $crudConfig = new class extends AbstractCrudConfig {
            public function getName(): string
            {
                return 'BOOKING';
            }

            public function getFields(string $key): array
            {
                return [];
            }

            public function getDefaultSort(): array
            {
                return [['name', 'ASC']];
            }

            public function getRootRoute(): string
            {
                return 'app_crudit_booking';
            }
        };

        return $crudConfig->getDatasourceParams($request);
    }
}

