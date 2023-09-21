<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * NumberFilterType
 *
 * For numbers.
 */
class NumberFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ["icon" => "fas fa-equals"],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ["icon" => "fas fa-not-equal"],
            FilterTypeInterface::OPERATOR_LESS_THAN => ["icon" => "fas fa-less-than"],
            FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL => ["icon" => "fas fa-less-than-equal"],
            FilterTypeInterface::OPERATOR_GREATER_THAN => ["icon" => "fas fa-greater-than"],
            FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL => ["icon" => "fas fa-greater-than-equal"],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ["icon" => "fas fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (
            isset($this->data["op"]) &&
            in_array(
                $this->data["op"],
                [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL]
            )
        ) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NOT_NULL:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($alias . $column));
                    break;
                case FilterTypeInterface::OPERATOR_IS_NULL:
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias . $column));
            }
        } elseif (isset($this->data['value']) && $this->data['value']) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                    $queryBuilder->andWhere($queryBuilder->expr()->neq($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_LESS_THAN:
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_LESS_THAN_EQUAL:
                    $queryBuilder->andWhere($queryBuilder->expr()->lte($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_GREATER_THAN:
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_GREATER_THAN_EQUAL:
                    $queryBuilder->andWhere($queryBuilder->expr()->gte($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_EQUAL:
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, ':' . $paramname));
            }

            $queryBuilder->setParameter($paramname, $this->data['value']);
        }
    }
}
