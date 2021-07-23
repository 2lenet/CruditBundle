<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

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

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) && $this->data['value']) {
            $queryBuilder->andWhere($this->alias . $this->columnName . ' >= :min_' . $this->id);
            $queryBuilder->setParameter('min_' . $this->id, $this->data['value']);
        }

        if (isset($this->data['to']) && $this->data['to']) {
            $queryBuilder->andWhere($this->alias . $this->columnName . ' <= :max_' . $this->id);
            $queryBuilder->setParameter('max_' . $this->id, $this->data['to']);
        }
    }
}
