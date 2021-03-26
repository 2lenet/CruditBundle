<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Field;

use Lle\CruditBundle\Dto\Path;

class FormField
{
    /** @var string */
    private $name;

    /** @var string */
    private $type = 'text';

    /** @var string */
    private $options = [];

    static public function new(string $name, string $type = null, array $options = [])
    {
        return (new self($name))
            ->setType($type)
            ->setOptions($options);
    }

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions($options): self
    {
        $this->options = $options;
        return $this;
    }


}
