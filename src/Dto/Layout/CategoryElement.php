<?php

namespace App\Crudit\CrudMenu;

use Lle\CruditBundle\Dto\Layout\AbstractLayoutElement;

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
        return '@LleCrudit/layout/sb_admin/elements/_category.html.twig';
    }
}
