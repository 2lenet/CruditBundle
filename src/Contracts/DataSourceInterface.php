<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface DataSourceInterface
{
    public function get($id): ?object;

    public function list(): iterable;

    public function delete($id): bool;

    public function put($id, array $data): ?object;

    public function patch($id, array $data): ?object;
}
