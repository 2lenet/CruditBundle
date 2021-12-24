<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ListAction
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

    protected ?string $modal = null;

    protected array $config = [];
    protected bool $batch = false;

    protected ?string $form = "";

    public static function new(string $label, Path $path, ?Icon $icon = null): self
    {
        return (new ListAction($label, $path))
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

    public function getModal(): ?string
    {
        return $this->modal;
    }

    public function setModal(?string $modal): self
    {
        $this->modal = $modal;

        return $this;
    }

    public function getId(): string
    {
        return md5("crudit_action_" . spl_object_id($this));
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

    public function setIsBatch()
    {
        $this->batch = true;
        return $this;
    }

    public function isBatch()
    {
        return $this->batch;
    }

    public function getTitle(): string
    {
        return $this->getLabel();
    }

    public function isDisabled(): bool
    {
        return false;
    }

    public function setResource(?object $resource): self
    {
        /** do nothing */
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
