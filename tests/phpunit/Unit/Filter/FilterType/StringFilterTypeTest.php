<?php

namespace phpunit\Unit\Filter\FilterType;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use phpunit\Unit\Filter\FilterType\Fixtures\Product;
use phpunit\Unit\Filter\FilterType\Fixtures\ProductTranslation;
use PHPUnit\Framework\TestCase;

class StringFilterTypeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // test fixtures are not autoloaded
        foreach (glob(__DIR__ . '/Fixtures/*.php') ?: [] as $file) {
            require_once $file;
        }
    }

    private function createEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Fixtures'], true);
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'serverVersion' => '8.0.0',
        ]);

        return new EntityManager($connection, $config);
    }

    private function applyFilter(StringFilterType $filter, string $op, ?string $value): QueryBuilder
    {
        $qb = $this->createEntityManager()->createQueryBuilder()->select('root')->from(Product::class, 'root');
        $filter->setData(['op' => $op, 'value' => $value]);
        $filter->apply($qb);

        // ensures the generated DQL is valid
        $qb->getQuery()->getAST();

        return $qb;
    }

    public function testTranslationPositiveOperator(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label'), FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('root.label LIKE :_label', $dql);
        self::assertStringContainsString(' OR EXISTS (SELECT ', $dql);
        self::assertStringContainsString('FROM ' . ProductTranslation::class, $dql);
        self::assertStringContainsString(".object = root AND ", $dql);
        self::assertStringContainsString(".field = 'label'", $dql);
        self::assertStringContainsString('.content LIKE :_label', $dql);
        self::assertSame('%foo%', $qb->getParameter('_label')?->getValue());
    }

    public function testTranslationNegativeOperator(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label'), FilterTypeInterface::OPERATOR_NOT_EQUAL, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('root.label <> :_label', $dql);
        self::assertStringContainsString(' AND NOT EXISTS (SELECT ', $dql);
        self::assertStringContainsString('.content = :_label', $dql);
    }

    public function testNullOperatorIgnoresTranslations(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label'), FilterTypeInterface::OPERATOR_IS_NULL, null);

        self::assertStringNotContainsString('EXISTS', $qb->getDQL());
    }

    public function testSearchInTranslationsDisabled(): void
    {
        $filter = StringFilterType::new('label')->setSearchInTranslations(false);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('root.label LIKE :_label', $dql);
        self::assertStringNotContainsString('EXISTS', $dql);
    }

    public function testEntityWithoutTranslations(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('category:name'), FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('category.name LIKE :category_name', $dql);
        self::assertStringNotContainsString('EXISTS', $dql);
    }

    public function testAdditionnalTranslatableField(): void
    {
        $filter = StringFilterType::new('code')->setAdditionnalFields(['label', 'category:name']);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        // code and label are on Product (which has translations), category:name is not
        self::assertSame(2, substr_count($dql, 'EXISTS'));
        self::assertStringContainsString(".field = 'code'", $dql);
        self::assertStringContainsString(".field = 'label'", $dql);
    }
}
