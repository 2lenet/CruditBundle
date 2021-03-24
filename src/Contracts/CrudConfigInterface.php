<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

interface CrudConfigInterface
{
    const LIST="LIST";
    const SHOW="SHOW";
    const EDIT="EDIT";
    const NEW="NEW";
    
    public function getBrickConfigs(Request $request, $pageKey): iterable;

    public function getDatasource(): DataSourceInterface;

    public function getController(): ?string;

    public function getName(): ?string;
}
