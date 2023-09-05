<?php

namespace Lle\CruditBundle\Filter\FilterType;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\FilterTypeInterface;

/**
 * EntityFilterType
 *
 * For entities, with an autocomplete.
 */
class EntityFilterType extends AbstractFilterType
{
    protected string $entityClass;
    protected ?string $dataRoute;

    public function __construct(string $fieldname, string $entityClass, ?string $dataRoute = null)
    {
        parent::__construct($fieldname);
        $this->entityClass = $entityClass;
        $this->setDataRoute($dataRoute);
        $this->setAdditionnalKeys(['items']);
    }

    private function setDataRoute(?string $dataRoute): void
    {
        if (null === $dataRoute) {
            $route = str_replace('App\\Entity\\', '', $this->entityClass);
            $dataRoute = sprintf('app_crudit_%s_autocomplete', strtolower($route));
        }
        $this->dataRoute = $dataRoute;
    }

    public static function new(string $fieldname, string $entityClass, ?string $customRoute = null): self
    {
        return new self($fieldname, $entityClass, $customRoute);
    }

    public function getOperators(): array
    {
        return [
            FilterTypeInterface::OPERATOR_EQUAL => ['icon' => 'fas fa-equals'],
            FilterTypeInterface::OPERATOR_NOT_EQUAL => ['icon' => 'fas fa-not-equal'],
            FilterTypeInterface::OPERATOR_IS_NULL => ["icon" => "far fa-square"],
            FilterTypeInterface::OPERATOR_IS_NOT_NULL => ["icon" => "fas fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        [$column, $alias, $paramname] = $this->getQueryParams($queryBuilder);

        $ids = [];
        if (isset($this->data['value']) && $this->data['value'] != '') {
            $ids = explode(',', $this->data['value']);
        }

        if (isset($this->data["op"])) {
            switch ($this->data['op']) {
                case FilterTypeInterface::OPERATOR_IS_NULL:
                    $queryBuilder->andWhere($alias . $column . ' IS NULL OR ' . $alias . $column . " = '' ");
                    break;
                case FilterTypeInterface::OPERATOR_IS_NOT_NULL:
                    $queryBuilder->andWhere($alias . $column . ' IS NOT NULL OR ' . $alias . $column . " = '' ");
                    break;
                case FilterTypeInterface::OPERATOR_NOT_EQUAL:
                    if (!empty($ids)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $column, ':' . $paramname));
                        $queryBuilder->setParameter($paramname, $ids);
                    }
                    break;
                case FilterTypeInterface::OPERATOR_EQUAL:
                default:
                    if (!empty($ids)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $column, ':' . $paramname));
                        $queryBuilder->setParameter($paramname, $ids);
                    }
            }
        }
    }

    public function getDataRoute(): string
    {
        return $this->dataRoute;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
