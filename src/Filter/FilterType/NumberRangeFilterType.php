<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * EnumerationFilterType
 */
class NumberRangeFilterType extends AbstractFilterType
{

    public function apply(QueryBuilder $queryBuilder): void
    {
        /*
        if (isset($data['value'][0]) or isset($data['value'][1])) {
            if ($data['value'][0]) {
                $queryBuilder->andWhere($this->alias . $this->columnName . ' >= :min_' . $this->id);
                $queryBuilder->setParameter('min_' . $this->id, $data['value'][0]);
            }
            if ($data['value'][1]) {
                $queryBuilder->andWhere($this->alias . $this->columnName . ' <= :max_' . $this->id);
                $queryBuilder->setParameter('max_' . $this->id, $data['value'][1]);
            }
        }
        */
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/number_range_filter.html.twig';
    }
}
