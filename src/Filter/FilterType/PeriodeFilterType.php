<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

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
            "isnull" => ["icon" => "far fa-square"],
            "interval" => ["icon" => "fas fa-arrows-alt-h"]
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case 'isnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($this->alias . $this->columnName));
                    break;
                case 'interval':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' >= :min_' . $this->id);
                    $queryBuilder->setParameter('min_' . $this->id, $this->data['value']);
                    break;
            }
        }

        if (isset($this->data['to']) && $this->data['to']) {
            switch ($this->data['op']) {
                case 'isnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($this->alias . $this->columnName));
                    break;
                case 'interval':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' <= :max_' . $this->id);
                    $queryBuilder->setParameter('max_' . $this->id, $this->data['to']);
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
