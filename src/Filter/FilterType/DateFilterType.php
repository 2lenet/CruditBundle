<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * DateFilterType
 *
 * For dates.
 */
class DateFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): DateFilterType
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ["icon" => "fas fa-equals"],
            FilterTypeInterface::OPERATOR_BEFORE => ["icon" => "fas fa-less-than"],
            FilterTypeInterface::OPERATOR_AFTER => ["icon" => "fas fa-greater-than"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        // ADD JOIN IF NEEDED
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_EQUAL:
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_BEFORE:
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($alias . $column, ':' . $paramname));
                    break;
                case FilterTypeInterface::OPERATOR_AFTER:
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $column, ':' . $paramname));
                    break;
            }

            $queryBuilder->setParameter($paramname, $this->data["value"] . '%');
        }
    }
}
