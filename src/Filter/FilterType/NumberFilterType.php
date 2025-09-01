<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class NumberFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_LESS_THAN => ['icon' => 'fas fa-less-than'],
            FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL => ['icon' => 'fas fa-less-than-equal'],
            FilterTypeInterface::OPERATOR_GREATER_THAN => ['icon' => 'fas fa-greater-than'],
            FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL => ['icon' => 'fas fa-greater-than-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ['icon' => 'fas fa-square'],
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

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (isset($this->data['value']) && $this->data['value']) {
            $value = $this->data['value'];

            $queryBuilder->andWhere($query);
            $queryBuilder->setParameter($paramname, $value);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }
}
