<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

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
            FilterTypeInterface::OPERATOR_EQUAL => ["icon" => "fas fa-equals"],
            FilterTypeInterface::OPERATOR_BEFORE => ["icon" => "fas fa-less-than"],
            FilterTypeInterface::OPERATOR_AFTER => ["icon" => "fas fa-greater-than"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            $datetime = $this->data["value"] . " ";
            if (isset($this->data["time"])) {
                $datetime .= $this->data["time"];
            } else {
                $datetime .= "00:00:00";
            }

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

            $queryBuilder->setParameter($paramname, $datetime);
        }
    }
}
