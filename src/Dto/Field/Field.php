<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Field;

use Lle\CruditBundle\Dto\Path;

class Field
{

    /**
     * @var string
     */
    private $header;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $sort;

    /**
     * @var null|string
     */
    private $template;

    /** @var ?Path */
    private $path;

    /** @var array */
    private $options;

    private $type;

    public function __construct(string $name, $type = null, array $options = [])
    {
        $this->name = $name;
        //no translate ucfirst(strtolower($name)
        $this->header = 'field.' . strtolower($name);
        $this->type = $type;
        $this->setOptions($options);
    }

    public static function new(string $name, $type = null, array $options = []): self
    {
        return new self($name, $type, $options);
    }

    public function override(Field $field): self
    {
        $options = array_merge($this->getOptions(), $field->getOptions());
        $this->setOptions($options);
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getType()
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
        $this->options = $options;
        $this->header = (isset($options['label'])) ? $options['label'] : $this->header;
        $this->sort = (isset($options['sort'])) ? $options['sort'] : false;
        $this->path = (isset($options['path'])) ? $options['path'] : null;
        $this->template = (isset($options['template'])) ? $options['template'] : null;
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
        $this->header = ($this->header) ? $this->header : $this->name;
        return $this;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): self
    {
        $this->header = $header;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sort;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }


    public function getPath(): ?Path
    {
        return $this->path;
    }


    public function getSort(): bool
    {
        return $this->sort;
    }
}
