<?php

namespace Lle\CruditBundle\Filter\FilterType;

/**
 * NumberFilterType
 */
class NumberFilterType extends AbstractFilterType
{
    public function __construct($fieldname)
    {
        $this->columnName = $fieldname;
        $this->id = $fieldname;
        $this->label = "field.".$fieldname;
        $this->alias = "root.";
    }

    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators()
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

    public function apply($queryBuilder)
    {
        if (isset($this->data['value']) && $this->data['value']) {
            switch ($this->data['op']) {
                case 'eq':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' = :var_' . $this->id);
                    $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
                    break;
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
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' > :var_' . $this->id);
                    $queryBuilder->setParameter('var_' . $this->id, '%' . $this->data['value'] . '%');
                    break;
                case 'gte':
                    $queryBuilder->andWhere($this->alias . $this->columnName . ' >= :var_' . $this->id);
                    $queryBuilder->setParameter('var_' . $this->id, '%' . $this->data['value'] . '%');
                    break;

                case 'isnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($this->alias . $this->columnName));
                    break;
                case 'isnotnull':
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($this->alias . $this->columnName));
                    break;
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($this->alias . $this->columnName, ':var_' . $this->id));
                    break;
            }
            $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
        }
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/number_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/number_filter.html.twig';
    }
}
