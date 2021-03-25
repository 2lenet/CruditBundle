<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * NumberFilterType
 */
class NumberFilterType extends AbstractFilterType
{
    public function configure(array $config = [])
    {
        parent::configure($config);
        $this->defaults = [
            'value' => $config['defaultValue'] ?? null,
            'comparator' => $config['defaultComparator'] ?? "eq"
        ];
    }

    public function apply($queryBuilder)
    {
        if (isset($this->data['value']) && $this->data['value']) {
            switch ($this->data['comparator']) {
                case 'eq':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' = :var_' . $this->uniqueId);
                    $queryBuilder->setParameter('var_' . $this->uniqueId, $this->data['value']);
                    break;
                case 'neq':
                    $queryBuilder->andWhere($queryBuilder->expr()->neq($this->alias . $this->columnName, ':var_' . $this->uniqueId));
                    break;
                case 'lt':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($this->alias . $this->columnName, ':var_' . $this->uniqueId));
                    break;
                case 'lte':
                    $queryBuilder->andWhere($queryBuilder->expr()->lte($this->alias . $this->columnName, ':var_' . $this->uniqueId));
                    break;
                case 'gt':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' > :var_' . $this->uniqueId);
                    $queryBuilder->setParameter('var_' . $this->uniqueId, '%' . $this->data['value'] . '%');
                    break;
                case 'gte':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' >= :var_' . $this->uniqueId);
                    $queryBuilder->setParameter('var_' . $this->uniqueId, '%' . $this->data['value'] . '%');
                    break;

                case 'isnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($this->alias . $this->columnName));
                    return;
                case 'isnotnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($this->alias . $this->columnName));
                    return;
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->uniqueId));
                    break;
            }
            $queryBuilder->setParameter('var_' . $this->uniqueId, $this->data['value']);
        }
    }

    public function getStateTemplate()
    {
        return '@LleEasyAdminPlus/filter/state/number_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleEasyAdminPlus/filter/type/number_filter.html.twig';
    }
}
