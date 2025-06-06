<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * PeriodeFilterType
 *
 * For date ranges.
 */
class PeriodeFilterType extends AbstractFilterType
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
                    if (!isset($this->data['to']) || $this->data['value'] !== $this->data['to']) {
                        $queryBuilder->andWhere($alias . $column . ' >= :min_' . $paramname);
                        $queryBuilder->setParameter('min_' . $paramname, $this->data['value']);
                    } else {
                        $queryBuilder->andWhere($alias . $column . ' LIKE :min_' . $paramname);
                        $queryBuilder->setParameter('min_' . $paramname, $this->data['value'] . '%');
                    }

                    break;
            }
        }

        if (isset($this->data['to']) && $this->data['to']) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($alias . $column));
                    break;
                case FilterTypeInterface::OPERATOR_INTERVAL:
                    if (!isset($this->data['value']) || $this->data['value'] !== $this->data['to']) {
                        $queryBuilder->andWhere($alias . $column . ' <= :max_' . $paramname);
                        $queryBuilder->setParameter('max_' . $paramname, $this->data['to'] . ' 23:59:59');
                    }

                    break;
            }
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/periode_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/periode_filter.html.twig';
    }
}
