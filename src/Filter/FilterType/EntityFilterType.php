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
    protected ?string $customRoute;

    public function __construct($fieldname, string $entityClass, ?string $customRoute = null)
    {
        parent::__construct($fieldname);
        $this->entityClass = $entityClass;
        $this->customRoute = $customRoute;
        $this->setAdditionnalKeys(['items']);
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
        ];
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (isset($this->data['value']) and $this->data['value'] != '') {
            $ids = explode(',', $this->data['value']);

            list($id, $alias, $paramname) = $this->getQueryParams($queryBuilder);

            switch ($this->data['op']) {
                case 'neq':
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . $id, ':' . $paramname));
                    break;
                case 'eq':
                default:
                    $queryBuilder->andWhere($queryBuilder->expr()->in($alias . $id, ':'.$paramname));
                    break;
            }

            $queryBuilder->setParameter($paramname, $ids);
        }
    }

    public function getDataRoute(): string
    {
        if (null !== $this->customRoute) {
            return $this->customRoute;
        }
        $route = str_replace('App\\Entity\\', '', $this->entityClass);

        return sprintf('app_crudit_%s_autocomplete', strtolower($route));
    }
}
