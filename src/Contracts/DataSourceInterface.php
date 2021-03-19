<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface DataSourceInterface
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
}
