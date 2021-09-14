<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;

/**
 * EntityFilterType
 *
 * For entities, with an autocomplete.
 */
class EntityFilterType extends AbstractFilterType
{
    protected string $entityClass;

    public static function new(string $fieldname, $entityClass): self
    {
        $f = new self($fieldname);
        $f->setEntityClass($entityClass);
        $f->setAdditionnalKeys(['items']);

        return $f;
    }

    public function setEntityClass(string $classname)
    {
        $this->entityClass = $classname;

        return $this;
    }

    public function getOperators(): array
    {
        return [
            "eq" => ["icon" => "fas fa-equals"],
            "neq" => ["icon" => "fas fa-not-equal"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) and $this->data['value'] != '') {
            $ids = explode(',', $this->data['value']);
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

            
            $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $id, ':var_' . $id));
            $queryBuilder->setParameter('var_' . $id, $ids);
        }
    }

    public function getDataRoute(): string
    {
        $route = str_replace("App\\Entity\\", "", $this->entityClass);

        return "app_crudit_" . strtolower($route) . "_autocomplete";
    }
}
