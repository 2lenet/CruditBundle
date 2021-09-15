<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * DateTimeFilterType
 *
 * For datetimes.
 */
class DateTimeFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        $f = new self($fieldname);
        $f->setAdditionnalKeys(["time"]);

        return $f;
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
        list($id, $alias, $paramname) = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {

            $datetime = $this->data["value"] . " ";
            if (isset($this->data["time"])) {
                $datetime .= $this->data["time"];
            } else {
                $datetime .= "00:00:00";
            }

            switch ($this->data['op']) {
                case 'eq':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $this->columnName, ':'.$paramname));
                    break;
                case 'before':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($alias . $this->columnName, ':'.$paramname));
                    break;
                case 'after':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $this->columnName, ':'.$paramname));
                    break;
            }

            $queryBuilder->setParameter($paramname, $datetime);
        }
    }
}
