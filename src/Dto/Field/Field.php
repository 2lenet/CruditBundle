<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Field;

use Lle\CruditBundle\Dto\Path;

class Field
{
    private string $label;

    private string $name;

    private bool $sort = true;

    private ?Path $path;

    private string $linkId = 'id';

    private array $options;

    private ?string $type;

    private ?string $template = null;

    private ?int $ruptGroup = 0;

    private ?string $role = null;

    private bool $editInPlace = false;

    private ?string $autocompleteUrl = null;

    private bool $multiple = false;

    private ?string $info = null;

    public function __construct(string $name, ?string $type = null, array $options = [])
    {
        $this->name = $name;
        $this->label = 'field.' . strtolower(str_replace('.', '_', $name));
        $this->type = $type;
        $this->options = $options;
        $this->setOptions($options);
    }

    public static function new(string $name, ?string $type = null, array $options = []): self
    {
        return new self($name, $type, $options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->label = $options['label'] ?? $this->label;
        $this->sort = $options['sort'] ?? $this->sort;
        $this->path = $options['path'] ?? $options['link_to'] ?? null;
        $this->template = (isset($options['template'])) ? $options['template'] : $this->template;
        unset($options['label']);
        unset($options['sort']);
        unset($options['path']);
        unset($options['template']);
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEditInPlace(): bool
    {
        return $this->editInPlace;
    }

    public function setEditInPlace(bool $editInPlace): self
    {
        $this->editInPlace = $editInPlace;

        return $this;
    }

    /**
     * @param ?string $editRoute #Route
     */
    public function setEditable(?string $editRoute = null, ?string $role = null): self
    {
        if ($editRoute) {
            $this->options['edit_route'] = $editRoute;
        }

        if ($role) {
            $this->options['editRole'] = $role;
        }

        $this->editInPlace = true;

        return $this;
    }

    public function setCssClass(?string $cssClass): self
    {
        $this->options["cssClass"] = $cssClass;

        return $this;
    }

    public function cssClassIsCustom(?bool $custom): self
    {
        $this->options["cssClassIsCustom"] = $custom;

        return $this;
    }

    public function hasCascade(): bool
    {
        return \str_contains($this->getName(), '.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->label = ($this->label) ? $this->name : $this->label;

        return $this;
    }

    public function getId(): string
    {
        return str_replace('.', '_', $this->getName());
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sort;
    }

    public function setSortable(bool $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function linkTo(Path $path, string $linkId = "id"): self
    {
        $this->path = $path;
        $this->linkId = $linkId;

        return $this;
    }

    public function getLinkId(): string
    {
        return $this->linkId;
    }

    public function getPath(): ?Path
    {
        return $this->path;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param ?string $template #Template
     */
    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getRuptGroup(): ?int
    {
        return $this->ruptGroup;
    }

    public function setRuptGroup(int $ruptGroup): self
    {
        $this->ruptGroup = $ruptGroup;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getAutocompleteUrl(): ?string
    {
        return $this->autocompleteUrl;
    }

    public function setAutocompleteUrl(?string $autocompleteUrl): self
    {
        $this->autocompleteUrl = $autocompleteUrl;

        return $this;
    }

    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }
}
