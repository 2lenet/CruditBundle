<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

/**
 * Badge of a menu item: a short text (a counter) on a pill of a tone, and a text read after it by assistive
 * technologies only ("12" + "visits to plan"). Rendered with the Badge component of CruditUiBundle, which takes any of
 * its tones (neutral, info, success, warning, danger, primary, the extended palette) or a tone of the project
 * (docs/components.md of CruditUiBundle); a tone without its CSS rule gives the neutral colours.
 *
 * The Bootstrap classes of 1.x (`badge-danger`, `bg-danger`) are deprecated and turned into their tone (`danger`;
 * `secondary`, `light` and `dark` give `neutral`).
 */
class Badge
{
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
        $this->tone = self::tone($tone);
        $this->label = $label;
    }

    private static function tone(string $tone): string
    {
        if (preg_match('/^(?:badge|bg)-(\w+)$/', $tone, $matches) !== 1 || !isset(self::BOOTSTRAP_TONES[$matches[1]])) {
            return $tone;
        }

        $normalized = self::BOOTSTRAP_TONES[$matches[1]];
        trigger_deprecation(
            '2lenet/crudit-bundle',
            '2.0',
            'The tone "%s" of a badge (a Bootstrap class) is deprecated, use "%s".',
            $tone,
            $normalized,
        );

        return $normalized;
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
