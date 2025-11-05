<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ItemAction extends AbstractAction
{
    protected ?object $resource = null;

    protected ?string $title = null;

    public static function new(string $label, Path $path, ?Icon $icon = null): static
    {
        return (new static($label))
            ->setPath($path)
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    /** exists only when rendering */
    public function getResource(): ?object
    {
        return $this->resource;
    }

    public function setResource(?object $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function isBatch(): bool
    {
        return false;
    }
}
