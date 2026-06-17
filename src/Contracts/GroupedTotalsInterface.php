<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Datasource\DatasourceParams;

interface GroupedTotalsInterface
{
    public function getGroupedTotals(
        ?DatasourceParams $requestParams,
        array $fields,
        string $ruptFieldPath,
        ?string $ruptDateFormat = null,
    ): array;
}
