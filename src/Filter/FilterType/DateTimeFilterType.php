<?php

namespace Lle\CruditBundle\Filter\FilterType;

use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * DateTimeFilterType
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
            "equal" => ["icon" => "fas fa-equals"],
            "before" => ["icon" => "fas fa-less-than"],
            "after" => ["icon" => "fas fa-greater-than"],
        ];
    }

    /**
     * @param array  $data     The data
     * @param string $this->id The unique identifier
     */
    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) && isset($this->data['op'])) {

            $datetime = $this->data["value"] . " ";
            if (isset($this->data["time"])) {
                $datetime .= $this->data["time"];
            } else {
                $datetime .= "00:00:00";
            }

            switch ($this->data['op']) {
                case 'equal':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'before':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'after':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
            }

            $queryBuilder->setParameter('var_' . $this->id, $datetime);
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/date_time_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/date_time_filter.html.twig';
    }
}
