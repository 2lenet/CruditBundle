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
        $className = get_class($this);
        return strtoupper(str_replace("CrudConfig", "", (substr($className, strrpos($className, '\\') + 1))));
    }

    public function getPath(string $context = self::INDEX, array $params = []): Path
    {
        return Path::new($this->getRootRoute() . '_' . $context, $params);
    }
}
