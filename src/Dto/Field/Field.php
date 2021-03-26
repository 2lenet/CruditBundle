<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Field;

use Lle\CruditBundle\Dto\Path;

class Field
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $sort;

    /** @var ?Path */
    private $path;

    /** @var array */
    private $options;

    /** @var ?string */
    private $type;

    public function __construct(string $name, ?string $type = null, array $options = [])
    {
        $this->name = $name;
        $this->label = 'field.' . strtolower(str_replace('.', '_', $name));
        $this->type = $type;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setOptions(array $options): void
    {

        $this->label = (isset($options['label'])) ? $options['label'] : $this->label;
        $this->sort = (isset($options['sort'])) ? $options['sort'] : false;
        $this->path = (isset($options['path'])) ? $options['path'] : null;
        unset($options['label']);
        unset($options['sort']);
        unset($options['path']);
        $this->options = $options;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasCascade(): bool
    {
        return \str_contains($this->getName(), '.');
    }

    public function getId(): string
    {
        return str_replace('.', '_', $this->getName());
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->label = ($this->label) ? $this->name : $this->label;
        return $this;
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

    public function getPath(): ?Path
    {
        return $this->path;
    }
}
