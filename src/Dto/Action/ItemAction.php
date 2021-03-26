<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Dto\Path;

class ItemAction
{

    /** @var string  */
    protected $label;

    /** @var Path  */
    protected $path;

    /** @var ?string */
    protected $url;

    public static function new(string $label, Path $path): self
    {
        return new self($label, $path);
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
}
