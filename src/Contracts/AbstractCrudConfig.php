<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Path;

abstract class AbstractCrudConfig implements CrudConfigInterface
{

    public function getController(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return null;
    }
    
    public function getPath(string $context = self::INDEX, array $params = []): Path
    {
        return Path::new($this->getRootRoute(). '_' . $context, $params);
    }
}
