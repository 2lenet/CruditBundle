<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class CategoryElement extends AbstractLayoutElement
{
    protected string $libelle;

    public static function new(string $libelle): self
    {
        return new self($libelle);
    }

    public function __construct(string $libelle)
    {
        $this->libelle = $libelle;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/layout/sidebar/_category.html.twig';
    }
}
