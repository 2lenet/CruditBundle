<?php

namespace Lle\CruditBundle\Test;


use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Filter\FilterType\AbstractFilterType;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\DateFilterType;
use Lle\CruditBundle\Filter\FilterType\DateTimeFilterType;
use Lle\CruditBundle\Filter\FilterType\EntityFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberRangeFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use Lle\CruditBundle\Filter\FilterType\TreeFilterType;
use Lle\CruditBundle\Filter\FilterType\WorkflowFilterType;
use Lle\CruditBundle\Provider\ConfigProvider;

trait FilterTestHelperTrait
{
    protected iterable $cruditDatasources;

    public function testFilters(): void
    {
        $container = static::getContainer();
        $configs = $container->get(ConfigProvider::class)->getConfigurators();
        /** @var CrudConfigInterface $config */
        foreach ($configs as $config) {
            $datasource = $config->getDatasource();
            /** @var AbstractFilterType $filter */
            foreach ($datasource->getFilterset()?->getFilters() ?? [] as $filter) {
                if (in_array(get_class($filter), [
                    BooleanFilterType::class,
                    ChoiceFilterType::class,
                    DateFilterType::class,
                    DateTimeFilterType::class,
                    EntityFilterType::class,
                    WorkflowFilterType::class,
                    TreeFilterType::class,
                    StringFilterType::class,
                    NumberFilterType::class,
                    NumberRangeFilterType::class,
                    PeriodeFilterType::class,
                ])) {
                    continue;
                }
//                dd($filter);
                $dataValue = match (get_class($filter)) {
                    BooleanFilterType::class => 'bool',
                    ChoiceFilterType::class => 'entity',
                    DateFilterType::class => '2024-03-22',
                    DateTimeFilterType::class => '2024-03-22',
//                    EntityFilterType::class => 'entity',
                    NumberFilterType::class => 42,
                    NumberRangeFilterType::class => 'entity',
                    PeriodeFilterType::class => '2024-03-22',
                    StringFilterType::class => 'toto',
//                    TreeFilterType::class => 'entity',
//                    WorkflowFilterType::class => 'entity',
                    default => 'nathan',
                };
                $filter->setData(['value' => $dataValue, 'op' => array_key_first($filter->getOperators())]);
                $requestParams = new DatasourceParams(filters: [$filter]);
                //                'value' => $dataValue, 'op' => array_key_first($filter->getOperators())
//                $qb = $datasource->buildQueryBuilder($requestParams);
//                $qb->distinct();
//                $filter->apply($qb);
                $res = $datasource->list($requestParams);

                $this->assertNotNull($res);
//                $this->assertNotNull($qb->getQuery()->getResult());
            }
        }
    }
}
