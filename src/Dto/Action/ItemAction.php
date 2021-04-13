<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ItemAction
{

    /** @var string  */
    protected $label;

    /** @var Path  */
    protected $path;

    /** @var ?Icon */
    protected $icon;

    /** @var ?string */
    protected $url;

    /** @var ?string */
    protected $cssClass;

    /** @var bool */
    protected $hideLabel = false;

    public static function new(string $label, Path $path, ?Icon $icon = null): ItemAction
    {
        return (new ItemAction($label, $path))
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    public function __construct(string $label, Path $path)
    {
        $this->label = $label;
        $this->path = $path;
        $this->url = null;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass): self
    {
        $this->cssClass = $cssClass;
        return $this;
    }

    public function isHideLabel(): bool
    {
        return $this->hideLabel;
    }

    public function setHideLabel(bool $hideLabel): self
    {
        $this->hideLabel = $hideLabel;
        return $this;
    }
}
