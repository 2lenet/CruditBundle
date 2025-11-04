<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Contracts\ActionInterface;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

abstract class AbstractAction implements ActionInterface
{
    protected string $label;

    protected Path $path;

    protected ?Icon $icon;

    protected ?string $url = null;

    protected ?string $cssClass = null;

    protected bool $hideLabel = false;

    protected ?string $modal = null;

    protected bool $confirmModal = false;

    protected array $config = [];

    protected ?string $target = null;

    protected ?string $role = null;

    protected ?bool $hideIfDisabled = null;

    protected bool $dropdown = false;

    protected ?string $template = null;

    /** @var callable|null $displayIf */
    protected $displayIf = null;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getId(): string
    {
        return md5('crudit_action_' . spl_object_id($this));
    }

    public function getTitle(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function setPath(Path $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass): static
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function isHideLabel(): bool
    {
        return $this->hideLabel;
    }

    public function setHideLabel(bool $hideLabel): static
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
    public function setModal(?string $modal): static
    {
        $this->modal = $modal;

        return $this;
    }

    public function getConfirmModal(): bool
    {
        return $this->confirmModal;
    }

    public function setConfirmModal(bool $confirmModal): static
    {
        $this->confirmModal = $confirmModal;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function isDisabled(): bool
    {
        return false;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getHideIfDisabled(): ?bool
    {
        return $this->hideIfDisabled;
    }

    public function setHideIfDisabled(?bool $hideIfDisabled): static
    {
        $this->hideIfDisabled = $hideIfDisabled;

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

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function isDisplayed(?object $resource = null): bool
    {
        return !$this->displayIf || call_user_func($this->displayIf, $resource);
    }

    public function setDisplayIf(?callable $displayIf = null): self
    {
        $this->displayIf = $displayIf;

        return $this;
    }
}
