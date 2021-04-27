<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface DatasourceInterface
{

    /** @param string|int $id */
    public function get($id): ?object;

    public function list(): iterable;

    /** @param string|int $id */
    public function delete($id): bool;

    /** @param string|int $id */
    public function put($id, array $data): ?object;

    /** @param string|int $id */
    public function patch($id, array $data): ?object;

    public function newInstance(): object;

    public function save(object $resource): void;

    public function getClassName(): string;

    public function getType(string $property, object $resource): string;

    public function getIdentifier(object $resource): string;

    public function createQuery(string $alias): QueryAdapterInterface;

    public function getAssociationFieldName(string $className): ?string;

    public function flush(): void;
}
