<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class Icon
{
    public const TYPE_FA = 'fa';

    public const TYPE_FAR = 'far';

    public const TYPE_IMG = 'img';

    private string $icon;

    private string $type;

    public static function new(string $icon, string $type = self::TYPE_FA): self
    {
        return new self($icon, $type);
    }

    public function __construct(string $icon, string $type = self::TYPE_FA)
    {
        $this->icon = $icon;
        $this->type = $type;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCssClass(): string
    {
        return $this->getType() . ' fa-' . $this->getIcon();
    }

    public function isImg(): bool
    {
        return ($this->getType() === static::TYPE_IMG);
    }
}
