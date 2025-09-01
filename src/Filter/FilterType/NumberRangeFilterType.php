<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class NumberRangeFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(['to']);

        return $f;
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
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($query);
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    $queryBuilder->andWhere($alias . $column . ' >= :min_' . $this->id);
                    $queryBuilder->setParameter('min_' . $this->id, $this->data['value']);
                    break;
            }
        }

        if (isset($this->data['to']) && $this->data['to']) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($query);
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    $queryBuilder->andWhere($alias . $column . ' <= :max_' . $this->id);
                    $queryBuilder->setParameter('max_' . $this->id, $this->data['to']);
                    break;
            }
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_INTERVAL => ['icon' => 'fas fa-arrows-alt-h'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
        ];
    }
}
