<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ItemAction extends AbstractAction
{
    protected ?object $resource = null;
    protected bool $dropdown = false;
    protected ?string $title = null;
    protected bool $disabled = false;

    public static function new(string $label, Path $path, ?Icon $icon = null): static
    {
        return (new static($label, $path))
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

    public function isDropdown(): bool
    {
        return $this->dropdown;
    }

    public function setDropdown(bool $dropdown): self
    {
        $this->dropdown = $dropdown;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function isBatch(): bool
    {
        return false;
    }
}
