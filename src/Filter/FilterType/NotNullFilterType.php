<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * BooleanFilterType
 */
class NotNullFilterType extends AbstractFilterType
{

    /**
     * @param array  $data     The data
     * @param string $uniqueId The unique identifier
     */
    public function apply($queryBuilder)
    {
        if (isset($this->data['value'])) {
            if ($this->data['value'] == 'notnull') {
                $queryBuilder->andWhere($this->alias . $this->columnName . '  IS NOT NULL');
            } elseif ($this->data['value'] == 'null') {
                $queryBuilder->andWhere($this->alias . $this->columnName . '  IS NULL');
            }
        }
    }

    public function getTemplate()
    {
        return '@LleEasyAdminPlus/filter/type/not_null_filter.html.twig';
    }
}
