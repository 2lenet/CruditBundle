<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class DateFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): DateFilterType
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_BEFORE => ['icon' => 'fas fa-less-than'],
            FilterTypeInterface::OPERATOR_AFTER => ['icon' => 'fas fa-greater-than'],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (isset($this->data['value']) && $this->data['value']) {
            $value = $this->data['value'] . '%';

            $queryBuilder->andWhere($query);
            $queryBuilder->setParameter($paramname, $value);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }
}
