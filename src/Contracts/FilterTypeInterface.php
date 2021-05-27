<?php

namespace Lle\CruditBundle\Contracts;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * FilterTypeInterface
 */
interface FilterTypeInterface
{
    public function getOperators(): array;

    public function getTemplate(): string;

    public function getStateTemplate(): string;

    public function getId(): string;

    public function apply(QueryBuilder $queryBuilder): void;

}
