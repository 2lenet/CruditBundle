<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class BrickView
{
    /** @var string|null  */
    private $template;

    /** @var array  */
    private $data;

    /** @var bool  */
    private $movable;

    /** @var string  */
    private $id;

    /** @var ?string  */
    private $cssClass;

    public function __construct(string $template = null, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
        $this->movable = false;
        $this->id = (string) 1;
        $this->cssClass = null;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getIndexTemplate(): string
    {
        return $this->template . '/index.html.twig';
    }

    public function getPartial(string $name): string
    {
        return $this->template . '/' . $name . '.html.twig';
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function gid(string $name): string
    {
        return $this->id . '_' . $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isMovable(): bool
    {
        return $this->movable;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }
}
