<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public function getBrickConfigs(Request $request): iterable;

    public function getDefaultDatasource(): DataSourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;
}
