<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Datasource\DatasourceParams;

interface DatasourceInterface
{
    public function getClassName(): string;

    /** @param string|int $id */
    public function get($id): ?object;

    public function list(?DatasourceParams $requestParams): iterable;

    public function autocompleteQuery(
        string $queryTerm,
        array $sorts,
        ?DatasourceParams $requestParams = null,
    ): iterable;

    public function autocompleteCountQuery(string $queryTerm): int;

    public function count(?DatasourceParams $requestParams): int;

    /** @param string|int $id */
    public function delete($id): bool;

    /** @param string|int $id */
    public function put($id, array $data): ?object;

    public function newInstance(): object;

    /** @param string|int $id */
    public function patch($id, array $data): ?object;

    public function save(object $resource): void;

    public function getType(string $property, object $resource): string;

    public function getIdentifier(object $resource): string;

    public function getAssociationFieldName(string $className): ?string;

    public function isEntity(string $field): bool;

    public function createQuery(string $alias): QueryAdapterInterface;

    public function getFilterset(): ?FilterSetInterface;

    public function editData(string $id, array $data): bool;

    public function getTotals(?DatasourceParams $requestParams, array $fields): iterable;

    public function setFilterState($filterState): self;
}
