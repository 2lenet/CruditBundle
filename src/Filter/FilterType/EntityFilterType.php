<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Filter\HiddenEntity;

class EntityFilterType extends AbstractFilterType
{

    protected $table;
    protected $method;
    protected $multiple;
    protected $args;
    protected $group_by;
    protected $method_label;
    protected $em;

    public static function new(string $fieldname): self
    {
        return new self($fieldname);
    }

    public function getOperators()
    {
        return [
            "equal" => ["icon" => "fas fa-equals"],
            "not_equal" => ["icon" => "fas fa-not-equal"],
        ];
    }

    public function apply($queryBuilder)
    {
        if (isset($this->data['value'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->id));
            $queryBuilder->setParameter('var_' . $this->id, $this->data['value']);
        }
    }

    public function getStateTemplate()
    {
        return '@LleCrudit/filter/state/entity_filter.html.twig';
    }

    public function getTemplate()
    {
        return '@LleCrudit/filter/type/entity_filter.html.twig';
    }
}
