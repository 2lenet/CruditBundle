<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

class EntityFilterType extends AbstractFilterType
{
    protected string $entityClass;

    protected ?string $dataRoute;

    public static function new(string $fieldname, string $entityClass, ?string $dataRoute = null): static
    {
        return (new static($fieldname))
            ->setEntityClass($entityClass)
            ->setDataRoute($dataRoute)
            ->setAdditionnalKeys(['items']);
    }

    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    private function setDataRoute(?string $dataRoute): static
    {
        if (null === $dataRoute) {
            $route = str_replace('App\\Entity\\', '', $this->entityClass);
            $dataRoute = sprintf('app_crudit_%s_autocomplete', strtolower($route));
        }
        $this->dataRoute = $dataRoute;

        return $this;
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_IN => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_IN => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ['icon' => 'far fa-square'],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ['icon' => 'fas fa-square'],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!isset($this->data['op'])) {
            return;
        }

        $op = $this->data['op'];

        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $query = $this->getPattern($op, $column, $alias, $column, $paramname);
        $this->applyAdditionnalFields($queryBuilder, $query, $op, $paramname);

        if (in_array($op, [FilterTypeInterface::OPERATOR_IS_NULL, FilterTypeInterface::OPERATOR_IS_NOT_NULL])) {
            $queryBuilder->andWhere($query);
        } elseif (isset($this->data['value']) && $this->data['value'] != '') {
            $value = explode(',', $this->data['value']);

            $queryBuilder->andWhere($query);
            $queryBuilder->setParameter($paramname, $value);
        }

        $this->applyAdditionnalConditions($queryBuilder);
    }

    public function getDataRoute(): ?string
    {
        return $this->dataRoute;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
