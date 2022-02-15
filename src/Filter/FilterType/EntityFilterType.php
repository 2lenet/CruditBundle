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
    protected ?string $dataRoute;

    public function __construct($fieldname, string $entityClass, ?string $dataRoute = null)
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

    public static function new(string $fieldname, $entityClass, ?string $customRoute = null): self
    {
        return new self($fieldname, $entityClass, $customRoute);
    }

    public function getOperators(): array
    {
        return [
            'eq' => ['icon' => 'fas fa-equals'],
            'neq' => ['icon' => 'fas fa-not-equal'],
            "isnull" => ["icon" => "far fa-square"],
            "isnotnull" => ["icon" => "fas fa-square"],
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        list($column, $alias, $paramname) = $this->getQueryParams($queryBuilder);

        $ids = [];
        if (isset($this->data['value']) && $this->data['value'] != '') {
            $datas = explode(',', $this->data['value']);

            foreach ($datas as $data) {
                $ids[] = explode('#', $data)[1];
            }
        }

        if (isset($this->data["op"])) {
            switch ($this->data['op']) {
                case 'isnull':
                    $queryBuilder->andWhere($alias . $column . ' IS NULL OR ' . $alias . $column . " = '' ");
                    break;
                case 'isnotnull':
                    $queryBuilder->andWhere($alias . $column . ' IS NOT NULL OR ' . $alias . $column . " = '' ");
                    break;
                case 'neq':
                    if (!empty($ids)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $column, ':' . $paramname));
                        $queryBuilder->setParameter($paramname, $ids);
                    }
                    break;
                case 'eq':
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
}
