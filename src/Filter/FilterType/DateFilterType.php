<?php

namespace Lle\CruditBundle\Filter\FilterType;

use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * DateFilterType
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
            "equal" => ["icon" => "fas fa-equals"],
            "before" => ["icon" => "fas fa-less-than"],
            "after" => ["icon" => "fas fa-greater-than"],
        ];
    }

    /**
     * @param array $data The data
     * @param string $this- >id The unique identifier
     */
    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
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

            $queryBuilder->setParameter('var_' . $this->id, $this->data["value"] . '%');
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/date_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/date_filter.html.twig';
    }
}
