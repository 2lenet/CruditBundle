<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exception\CruditException;

class ItemAction
{
    protected string $label;

    protected Path $path;

    protected ?Icon $icon;

    protected ?string $url;

    protected ?string $cssClass;

    protected bool $hideLabel = false;

    protected ?object $resource = null;

    protected ?string $modal = null;

    protected bool $dropdown = false;

    protected ?string $title = null;

    protected bool $disabled = false;

    protected bool $hasVoter = false;

    protected ?string $target = null;

    public static function new(string $label, Path $path, ?Icon $icon = null): ItemAction
    {
        return (new static($label, $path))
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    final public function __construct(string $label, Path $path)
    {
        $this->label = $label;
        $this->path = $path;
        $this->url = null;
    }

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

    public function getModal(): ?string
    {
        return $this->modal;
    }

    public function setModal(?string $modal): self
    {
        $this->modal = $modal;

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

    public function getTitle(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        return $this->getLabel();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getHasVoter(): bool
    {
        return $this->hasVoter;
    }

    public function setHasVoter(bool $hasVoter): self
    {
        $this->hasVoter = $hasVoter;

        return $this;
    }

    public function enableVoter(bool $hasVoter = true): self
    {
        $this->hasVoter = $hasVoter;

        return $this;
    }

    public function getRoleVoter(): string
    {
        if (!$this->path->getRole()) {
            $what = sprintf(
                "To use a voter on action '%s', please set the role in the action path.",
                $this->getLabel()
            );
            throw new CruditException($what);
        }

        return $this->path->getRole() . "_VOTER";
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

    public function isBatch(): bool
    {
        return false;
    }
}
