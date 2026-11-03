<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

/**
 * Badge of a menu item: a short text (a counter) on a pill of a tone, and a text read after it by assistive
 * technologies only ("12" + "visits to plan"). Rendered with the Badge component of CruditUiBundle, which takes any of
 * its tones (neutral, info, success, warning, danger, primary, the extended palette) or a tone of the project
 * (docs/components.md of CruditUiBundle); a tone without its CSS rule gives the neutral colours.
 *
 * The Bootstrap classes of 1.x (`badge-danger`, `bg-danger`) are not converted: they are unknown tones, so neutral,
 * and deprecated (a deprecation names the tone to write, so that a class forgotten in a migration is seen).
 */
class Badge
{
    /** The tone to write for each colour of Bootstrap */
    private const array BOOTSTRAP_TONES = [
        'primary' => 'primary',
        'secondary' => 'neutral',
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'light' => 'neutral',
        'dark' => 'neutral',
    ];

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

        if (preg_match('/^(?:badge|bg)-(\w+)$/', $tone, $matches) === 1 && isset(self::BOOTSTRAP_TONES[$matches[1]])) {
            trigger_deprecation(
                '2lenet/crudit-bundle',
                '2.0',
                'The tone "%s" of a badge is a Bootstrap class: it is not converted and gives a neutral badge, use "%s".',
                $tone,
                self::BOOTSTRAP_TONES[$matches[1]],
            );
        }
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
