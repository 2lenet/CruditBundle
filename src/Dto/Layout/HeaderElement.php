<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class HeaderElement extends AbstractLayoutElement
{
    /** @var string */
    protected $libelle;

    public static function new(string $libelle): self
    {
        return new self($libelle);
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/layout/sb_admin/elements/_header.html.twig';
    }

    public function __construct(string $libelle)
    {
        $this->libelle = $libelle;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }
}
