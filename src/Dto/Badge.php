<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

/**
 * Badge of a menu item: a short text (a counter) on a pill of a tone, and a text read after it by assistive
 * technologies only ("12" + "visits to plan"). Rendered with the Badge component of CruditUiBundle, which takes any of
 * its tones (neutral, info, success, warning, danger, primary, the extended palette) or a tone of the project
 * (docs/components.md of CruditUiBundle); a tone without its CSS rule gives the neutral colours.
 *
 * The Bootstrap classes of 1.x (`badge-danger`, `bg-danger`) are not converted: they are unknown tones, so neutral.
 */
class Badge
{
    private string $libelle;
    private string $tone;
    private ?string $label;

    public static function new(string $libelle, string $tone = 'neutral', ?string $label = null): self
    {
        return new self($libelle, $tone, $label);
    }

    public function __construct(string $libelle, string $tone = 'neutral', ?string $label = null)
    {
        $this->libelle = $libelle;
        $this->tone = $tone;
        $this->label = $label;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getTone(): string
    {
        return $this->tone;
    }

    /**
     * Text read after the libelle by assistive technologies only (a translation key or a text).
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }
}
