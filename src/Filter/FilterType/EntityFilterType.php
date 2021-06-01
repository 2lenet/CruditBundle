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
        if (isset($this->data['value'])) {
            $ids = explode(',', $this->data['value']);
            $queryBuilder->andWhere($queryBuilder->expr()->in($this->alias . $this->columnName, ':var_' . $this->id));
            $queryBuilder->setParameter('var_' . $this->id, $ids);
        }
    }

    public function getDataRoute(): string
    {
        $route = str_replace("App\\Entity\\", "", $this->entityClass);

        return "app_crudit_" . strtolower($route) . "_autocomplete";
    }
}
