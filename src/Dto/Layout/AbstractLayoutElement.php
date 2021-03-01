<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

abstract class AbstractLayoutElement implements LayoutElementInterface
{

    /** @var array */
    protected $roles;

    /** @var string */
    protected $cssClass;

    /**
     * @return string[]
     */
    public function getRole(): ?array
    {
        return $this->roles;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }
}
