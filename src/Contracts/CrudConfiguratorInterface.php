<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;

interface CrudConfiguratorInterface
{
    //public static function getDataSource(): DataSourceInterface;

    //public function configureFields(string $pageName): iterable;

    public function getName(): string;

    public function getSubjectClass(): string;

    public function getController(): ?string;

    public function getBrickConfigurators(): array;

    public function getLinkElement(Path $path): ?LinkElement;
}
