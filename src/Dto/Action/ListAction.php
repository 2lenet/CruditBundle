<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ListAction extends AbstractAction
{
    protected bool $batch = false;
    protected ?string $form = "";

    public static function new(string $label, Path $path, ?Icon $icon = null): static
    {
        return (new static($label))
            ->setPath($path)
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    public function isBatch(): bool
    {
        return $this->batch;
    }

    public function setIsBatch(): self
    {
        $this->batch = true;

        return $this;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function setForm(?string $form): self
    {
        $this->form = $form;

        return $this;
    }
}
