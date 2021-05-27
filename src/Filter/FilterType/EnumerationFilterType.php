<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * EnumerationFilterType
 */
class EnumerationFilterType extends AbstractFilterType
{

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($data['value']) && isset($data['op'])) {
            switch ($data['op']) {
                case 'in':
                    $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $this->columnName, ':var_' . $this->id));
                    $queryBuilder->setParameter('var_' . $id, $data['value'], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                    break;
                case 'notin':
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $this->columnName, ':var_' . $this->id));
                    $queryBuilder->setParameter('var_' . $this->id, $data['value'], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                    break;
            }
        }
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/enumeration_filter.html.twig';
    }
}
