<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class Badge
{
    private string $libelle;
    private string $cssClass;

    public static function new(string $libelle, string $cssClass = 'badge-success'): self
    {
        return new self($libelle, $cssClass);
    }

    public function __construct(string $libelle, string $cssClass = 'success')
    {
        $this->libelle = $libelle;
        $this->cssClass = $cssClass;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }
}
