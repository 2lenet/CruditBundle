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
        $arr = explode(':',$this->id);
        if (count($arr)>1) {
            $id = $arr[1];
            $alias = $arr[0].'.';
            if (!in_array($arr[0],$queryBuilder->getAllAliases())) {
                $queryBuilder->join($this->alias . $arr[0], $arr[0]);
            }
        } else {
            $id = $this->id;
            $alias = $this->alias;
        }

        if (isset($this->data['value']) && $this->data['value'] && isset($this->data['op'])) {
            switch ($this->data['op']) {
                case 'eq':
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($alias . $this->columnName, ':var_' . $id));
                    break;
                case 'before':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($alias . $this->columnName, ':var_' . $id));
                    break;
                case 'after':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($alias . $this->columnName, ':var_' . $id));
                    break;
            }

            $queryBuilder->setParameter('var_' . $id, $this->data["value"] . '%');
        }
    }
}
