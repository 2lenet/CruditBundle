<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

/**
 * An icon of a menu element or of an action, rendered by ui_icon() of CruditUiBundle: a logical name of its catalogue
 * (`add`, `delete`, `back`) or an Iconify name (`fa7-solid:users`), locked by ux:icons:lock (MenuIconFinder,
 * ActionIconFinder). The type (fa, far, img) of 1.x has no effect anymore (the batch actions of lot 3.1.c still use
 * getCssClass()).
 */
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

    /**
     * @deprecated since 2.0: the icons are rendered by ui_icon() of CruditUiBundle with getIcon() (a logical name of
     * its catalogue, or an Iconify name `prefix:name`), Font Awesome is no longer loaded
     */
    public function getCssClass(): string
    {
        return $this->getType() . ' fa-' . $this->getIcon();
    }

    public function isImg(): bool
    {
        return ($this->getType() === static::TYPE_IMG);
    }
}
