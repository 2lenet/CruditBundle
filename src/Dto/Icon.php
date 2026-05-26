<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class Icon
{
    public const TYPE_FA = 'fa';
    public const TYPE_FAR = 'far';
    public const TYPE_IMG = 'img';

    private const FA_FAMILY = [self::TYPE_FA, self::TYPE_FAR];

    private string $icon;
    private string $type;
    private ?string $prefix;

    public static function new(string $icon, string $type = self::TYPE_FA, ?string $prefix = null): self
    {
        return new self($icon, $type, $prefix);
    }

    public function __construct(string $icon, string $type = self::TYPE_FA, ?string $prefix = null)
    {
        if (!in_array($type, self::FA_FAMILY, true) && $type !== self::TYPE_IMG && $prefix === null) {
            throw new \InvalidArgumentException(sprintf(
                'Icon prefix is required when using a custom icon pack ("%s"). '
                . 'Pass it as the third argument: new Icon($name, "%s", "your-prefix").',
                $type,
                $type,
            ));
        }

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

        return self::TYPE_FA;
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