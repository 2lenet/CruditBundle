<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class Icon
{
    public const TYPE_FA = 'fa';
    public const TYPE_FAS = 'fas';
    public const TYPE_FAR = 'far';
    public const TYPE_FAB = 'fab';
    public const TYPE_IMG = 'img';
    public const TYPE_BI = 'bi';

    private const FA_FAMILY = [self::TYPE_FA, self::TYPE_FAS, self::TYPE_FAR, self::TYPE_FAB];

    private string $icon;
    private string $type;
    private ?string $prefix;

    public static function new(string $icon, string $type = self::TYPE_FA, ?string $prefix = null): self
    {
        return new self($icon, $type, $prefix);
    }

    public function __construct(string $icon, string $type = self::TYPE_FA, ?string $prefix = null)
    {
        $this->icon = $icon;
        $this->type = $type;
        $this->prefix = $prefix;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPrefix(): string
    {
        if ($this->prefix !== null) {
            return $this->prefix;
        }

        if (in_array($this->type, self::FA_FAMILY, true)) {
            return self::TYPE_FA;
        }

        return $this->type;
    }

    public function getCssClass(): string
    {
        if ($this->isImg()) {
            return '';
        }

        return $this->type . ' ' . $this->getPrefix() . '-' . $this->icon;
    }

    public function isImg(): bool
    {
        return ($this->getType() === static::TYPE_IMG);
    }
}