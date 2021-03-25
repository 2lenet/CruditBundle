<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public const INDEX = "INDEX";
    public const SHOW = "SHOW";
    public const EDIT = "EDIT";
    public const NEW = "NEW";

    public function getBrickConfigs(Request $request, string $pageKey): iterable;

    public function getDatasource(): DataSourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;
}
