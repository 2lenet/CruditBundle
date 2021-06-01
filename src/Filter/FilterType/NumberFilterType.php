<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * NumberFilterType
 */
class NumberFilterType extends AbstractFilterType
{
    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators(): array
    {
        return [
            "eq" => ["icon" => "fas fa-equals"],
            "neq" => ["icon" => "fas fa-not-equal"],
            "lt" => ["icon" => "fas fa-less-than"],
            "lte" => ["icon" => "fas fa-less-than-equal"],
            "gt" => ["icon" => "fas fa-greater-than"],
            "gte" => ["icon" => "fas fa-greater-than-equal"],
            "isnull" => ["icon" => "far fa-square"],
            "isnotnull" => ["icon" => "fas fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data["op"]) && in_array($this->data["op"], ["isnull", "isnotnull"])) {
            switch ($this->data['op']) {
                case 'isnotnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($this->alias . $this->columnName));
                    break;
                case 'isnull':
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($this->alias . $this->columnName));
            }
        } else if (isset($this->data['value']) && $this->data['value']) {
            switch ($this->data['op']) {
                case 'neq':
                    $queryBuilder->andWhere($queryBuilder->expr()->neq($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'lt':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'lte':
                    $queryBuilder->andWhere($queryBuilder->expr()->lte($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'gt':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'gte':
                    $queryBuilder->andWhere($queryBuilder->expr()->gte($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
                case 'eq':
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->id));
            }

            $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
        }
    }

    public function getStateTemplate(): string
    {
        return '@LleCrudit/filter/state/number_filter.html.twig';
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/filter/type/number_filter.html.twig';
    }
}
