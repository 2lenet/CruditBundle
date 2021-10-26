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

            list($id, $alias, $paramname) = $this->getQueryParams($queryBuilder);

            switch ($this->data['op']) {
                case "neq":
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $id, ':'.$paramname));
                    break;
                case "eq":
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $id, ':'.$paramname));
                    break;
            }

            $queryBuilder->setParameter($paramname, $ids);
        }
    }

    public function getDataRoute(): string
    {
        $route = str_replace("App\\Entity\\", "", $this->entityClass);

        return "app_crudit_" . strtolower($route) . "_autocomplete";
    }
}
