<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface CrudConfigInterface
{
    public static function getDataSource(): DataSourceInterface;

    public function configureFields(string $pageName): iterable;
}
