<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Path;
use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    public const INDEX = "INDEX";
    public const SHOW = "SHOW";
    public const EDIT = "EDIT";
    public const NEW = "NEW";

    public function getBrickConfigs(Request $request, string $pageKey): iterable;

    public function getDatasource(): DatasourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;

    public function getRootRoute(): ?string;

    public function getPath(string $context = self::INDEX, array $params = []): Path;
}
