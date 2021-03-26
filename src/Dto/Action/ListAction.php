<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Path;

class ListAction
{
    protected $label;

    protected $path;

    protected $url;

    public static function new(string $label, Path $path)
    {
        return new static($label, $path);
    }

    public function __construct(string $label, Path $path)
    {
        $this->label = $label;
        $this->path = $path;
        $this->url = null;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLabel()
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
}
