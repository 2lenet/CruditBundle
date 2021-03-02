<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

class TitleElement extends AbstractLayoutElement
{
    /** @var string */
    private $libelle;

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
        return 'elements/_title';
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }
}
