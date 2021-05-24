<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Filter\HiddenEntity;
use function Symfony\Component\String\u;

class EntityFilterType extends AbstractFilterType
{
    private string $entityClass;

    public static function new(string $fieldname, $entityClass): self
    {
        $f = new self($fieldname);
        $f->setEntityClass($entityClass);
        return $f;
    }

    public function setEntityClass(string $classname) {
        $this->entityClass = $classname;
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
            $ids = explode(',', $this->data['value']);
            $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->id));
            $queryBuilder->setParameter('var_' . $this->id, $ids);
        }
    }

    public function getDataRoute() {
        $route = str_replace("App\\Entity\\","",$this->entityClass);
        return "app_crudit_".strtolower($route)."_autocomplete";
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
