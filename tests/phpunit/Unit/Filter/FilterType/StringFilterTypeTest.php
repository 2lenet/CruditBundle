<?php

namespace phpunit\Unit\Filter\FilterType;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
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

    private function createEntityManager(bool $withTranslatableListener = true): EntityManager
    {
        $eventManager = new EventManager();
        if ($withTranslatableListener) {
            $eventManager->addEventSubscriber(new TranslatableListener());
        }

        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Fixtures'], true);
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'serverVersion' => '8.0.0',
        ]);

        return new EntityManager($connection, $config, $eventManager);
    }

    private function applyFilter(
        StringFilterType $filter,
        string $op,
        ?string $value,
        bool $withTranslatableListener = true,
    ): QueryBuilder {
        $qb = $this->createEntityManager($withTranslatableListener)->createQueryBuilder()->select('root')->from(Product::class, 'root');
        $filter->setData(['op' => $op, 'value' => $value]);
        $filter->apply($qb);

        // ensures the generated DQL is valid
        $qb->getQuery()->getAST();

        return $qb;
    }

    public function testTranslationPositiveOperator(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label')->setSearchInTranslations(true), FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

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
        $qb = $this->applyFilter(StringFilterType::new('label')->setSearchInTranslations(true), FilterTypeInterface::OPERATOR_NOT_EQUAL, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('root.label <> :_label', $dql);
        self::assertStringContainsString(' AND NOT EXISTS (SELECT ', $dql);
        self::assertStringContainsString('.content = :_label', $dql);
    }

    public function testTranslationDoesNotContainOperator(): void
    {
        $filter = StringFilterType::new('label')->setSearchInTranslations(true);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('root.label NOT LIKE :_label', $dql);
        self::assertStringContainsString(' AND NOT EXISTS (SELECT ', $dql);
        self::assertStringContainsString('.content LIKE :_label', $dql);
        self::assertSame('%foo%', $qb->getParameter('_label')?->getValue());
    }

    public function testDoesNotContainIsOffered(): void
    {
        self::assertArrayHasKey(
            FilterTypeInterface::OPERATOR_DOES_NOT_CONTAIN,
            StringFilterType::new('label')->getOperators(),
        );
    }

    public function testNullOperatorIgnoresTranslations(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label')->setSearchInTranslations(true), FilterTypeInterface::OPERATOR_IS_NULL, null);

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
        $qb = $this->applyFilter(StringFilterType::new('category:name')->setSearchInTranslations(true), FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        self::assertStringContainsString('category.name LIKE :category_name', $dql);
        self::assertStringNotContainsString('EXISTS', $dql);
    }

    public function testAdditionnalTranslatableField(): void
    {
        $filter = StringFilterType::new('code')
            ->setAdditionnalFields(['label', 'category:name'])
            ->setSearchInTranslations(true);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        $dql = $qb->getDQL();
        // only label is translatable: code is not, category:name is on an entity without translations
        self::assertSame(1, substr_count($dql, 'EXISTS'));
        self::assertStringContainsString(".field = 'label'", $dql);
        self::assertStringNotContainsString(".field = 'code'", $dql);
    }

    public function testTheDqlIsTheSameFromARequestToTheNext(): void
    {
        $filter = StringFilterType::new('code')->setAdditionnalFields(['label'])->setSearchInTranslations(true);

        $first = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo')->getDQL();
        $second = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo')->getDQL();

        // The alias comes from the filter and the field: the query cache of Doctrine finds the same DQL
        self::assertSame($first, $second);
        self::assertStringContainsString('FROM ' . ProductTranslation::class . ' crudit_tr_code_label ', $first);
    }

    public function testTwoFiltersOnTheSameTranslatableField(): void
    {
        $qb = $this->createEntityManager()->createQueryBuilder()->select('root')->from(Product::class, 'root');
        $search = StringFilterType::new('code')->setAdditionnalFields(['label'])->setSearchInTranslations(true);
        $search->setData(['op' => FilterTypeInterface::OPERATOR_CONTAINS, 'value' => 'foo']);
        $search->apply($qb);
        $label = StringFilterType::new('label')->setSearchInTranslations(true);
        $label->setData(['op' => FilterTypeInterface::OPERATOR_CONTAINS, 'value' => 'bar']);
        $label->apply($qb);

        // Doctrine shares the aliases between the query and its subqueries: each filter has its own
        $qb->getQuery()->getAST();
        $dql = $qb->getDQL();
        self::assertStringContainsString(' crudit_tr_code_label ', $dql);
        self::assertStringContainsString(' crudit_tr_label_label ', $dql);
    }

    public function testSearchInTranslationsDisabledByDefault(): void
    {
        $qb = $this->applyFilter(StringFilterType::new('label'), FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        self::assertStringNotContainsString('EXISTS', $qb->getDQL());
    }

    public function testNotTranslatableField(): void
    {
        $filter = StringFilterType::new('code')->setSearchInTranslations(true);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo');

        self::assertStringNotContainsString('EXISTS', $qb->getDQL());
    }

    public function testWithoutTranslatableListener(): void
    {
        $filter = StringFilterType::new('label')->setSearchInTranslations(true);
        $qb = $this->applyFilter($filter, FilterTypeInterface::OPERATOR_CONTAINS, 'foo', false);

        self::assertStringNotContainsString('EXISTS', $qb->getDQL());
    }
}
