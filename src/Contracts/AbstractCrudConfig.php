<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCrudConfig implements CrudConfigInterface
{
    /** @var array */
    public $bricks = [];

    public function getController(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        $classname = get_class($this);
        return strtoupper(str_replace("CrudConfig","",(substr($classname, strrpos($classname, '\\') + 1))));
    }

    public function getPath(string $context = self::INDEX, array $params = []): Path
    {
        return Path::new($this->getRootRoute() . '_' . $context, $params);
    }

    /** @param BrickConfigInterface[] $bricks */
    public function add(string $pageKey, array $bricks)
    {
        if (!array_key_exists($pageKey, $this->bricks)) {
            $this->bricks[$pageKey] = [];
        }
        $this->bricks[$pageKey] = $bricks;
    }
}
