<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface DataSourceInterface
{
    public function get(mixed $id): Response;
    public function cget(): Response;
    public function delete(mixed $id): Response;
    public function put(mixed $id, array $data): Response;
    public function patch(mixed $id, array $data): Response;
}
