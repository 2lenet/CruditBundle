<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

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
            "eq" => ["icon" => "fas fa-equals"],
            "before" => ["icon" => "fas fa-less-than"],
            "after" => ["icon" => "fas fa-greater-than"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        // ADD JOIN IF NEEDED
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case 'eq':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $column, ':' . $paramname));
                    break;
                case 'before':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($alias . $column, ':' . $paramname));
                    break;
                case 'after':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $column, ':' . $paramname));
                    break;
            }

            $queryBuilder->setParameter($paramname, $this->data["value"] . '%');
        }
    }
}
