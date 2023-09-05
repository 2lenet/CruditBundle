<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ListAction extends BaseAction
{
    protected bool $batch = false;
    protected ?string $form = "";

    public static function new(string $label, Path $path, ?Icon $icon = null): self
    {
        return (new self($label, $path))
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    public function __construct(string $label, Path $path)
    {
        $this->label = $label;
        $this->path = $path;
        $this->url = null;
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
