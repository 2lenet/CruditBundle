<?php

namespace Lle\CruditBundle\Contracts;

use Doctrine\ORM\QueryBuilder;

interface FilterTypeInterface
{
    public const OPERATOR_EQUAL = 'eq';

    public const OPERATOR_NOT_EQUAL = 'neq';

    public const OPERATOR_BEFORE = 'before';

    public const OPERATOR_AFTER = 'after';

    public const OPERATOR_IS_NULL = 'isnull';

    public const OPERATOR_IS_NOT_NULL = 'isnotnull';

    public const OPERATOR_LESS_THAN = 'lt';

    public const OPERATOR_LESS_THAN_EQUAL = 'lte';

    public const OPERATOR_GREATER_THAN = 'gt';

    public const OPERATOR_GREATER_THAN_EQUAL = 'gte';

    public const OPERATOR_INTERVAL = 'interval';

    public const OPERATOR_CONTAINS = 'contains';

    public const OPERATOR_DOES_NOT_CONTAIN = 'doesnotcontain';

    public const OPERATOR_STARTS_WITH = 'startswith';

    public const OPERATOR_ENDS_WITH = 'endswith';

    public const OPERATOR_IN = 'in';

    public const OPERATOR_NOT_IN = 'notin';

    public function __construct(string $fieldname);

    public function getOperators(): array;

    public function getTemplate(): string;

    public function getStateTemplate(): string;

    public function getId(): string;

    public function apply(QueryBuilder $queryBuilder): void;

    public function getPattern(string $op, string $id, string $alias, string $col, string $paramname): ?string;

    public function applyAdditionnalFields(
        QueryBuilder $queryBuilder,
        string &$query,
        string $op,
        string $paramname,
    ): void;

    public function applyAdditionnalConditions(QueryBuilder $queryBuilder): void;
}
