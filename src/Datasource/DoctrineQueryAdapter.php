<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;

class DoctrineQueryAdapter implements QueryAdapterInterface
{
    private QueryBuilder $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function where(string $string): self
    {
        $this->queryBuilder->where($string);

        return $this;
    }

    /** @param mixed $value */
    public function setParameter(string $parameter, $value): self
    {
        $this->queryBuilder->setParameter($parameter, $value);

        return $this;
    }

    public function execute(): array
    {
        $return = $this->queryBuilder->getQuery()->execute();
        if (is_array($return)) {
            return $return;
        }

        return [];
    }
}
