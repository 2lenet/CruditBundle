<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class TitleElement extends AbstractLayoutElement
{
    private string $libelle;

    public static function new(string $libelle): self
    {
        return new self($libelle);
    }

    public function __construct(string $libelle)
    {
        $this->libelle = $libelle;
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/layout/sb_admin/elements/_title.html.twig';
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }
}
