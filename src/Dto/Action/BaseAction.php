<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class BaseAction
{
    protected string $label;
    protected Path $path;
    protected ?Icon $icon;
    protected ?string $url;
    protected ?string $cssClass = null;
    protected bool $hideLabel = false;
    protected ?string $modal = null;
    protected array $config = [];
    protected ?string $target = null;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPath(): Path
    {
        return $this->path;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

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

    public function getModal(): ?string
    {
        return $this->modal;
    }

    /**
     * @param ?string $modal #Template
     */
    public function setModal(?string $modal): self
    {
        $this->modal = $modal;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getId(): string
    {
        return md5("crudit_action_" . spl_object_id($this));
    }

    public function getTitle(): string
    {
        return $this->getLabel();
    }

    public function isDisabled(): bool
    {
        return false;
    }
}
