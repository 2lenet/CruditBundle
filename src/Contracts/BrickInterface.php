<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\BrickView;

interface BrickInterface
{
    //public static function getDataSource(): DataSourceInterface;

    //public function configureFields(string $pageName): iterable;

    public function support(BrickConfiguratorInterface $brickConfigurator): bool;

    public function buildView(BrickConfiguratorInterface $brickConfigurator): BrickView;
}