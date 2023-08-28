<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * NumberRangeFilterType
 *
 * For number ranges.
 */
class NumberRangeFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(["to"]);

        return $f;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_INTERVAL => ["icon" => "fas fa-arrows-alt-h"],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias . $column));
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    $queryBuilder->andWhere($alias . $column . ' >= :min_' . $this->id);
                    $queryBuilder->setParameter('min_' . $this->id, $this->data['value']);
                    break;
            }
        }

        if (isset($this->data['to']) && $this->data['to'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias . $column));
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    $queryBuilder->andWhere($alias . $column . ' <= :max_' . $this->id);
                    $queryBuilder->setParameter('max_' . $this->id, $this->data['to']);
                    break;
            }
        }
    }
}
