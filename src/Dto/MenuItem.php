<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto;

class MenuItem
{
    public const TYPE_HEADER = 'header';
    public const TYPE_BODY = 'body';

    /** @var string */
    protected $type;

    /** @var string */
    protected $libelle;

    /** @var string */
    protected $icon;

    /** @var Path  */
    protected $path;

    /** @var array  */
    protected $labels;

    /** @var string */
    protected $cssClass;

    /** @var string[] */
    protected $roles;

    public static function new(string $libelle, Path $path, array $roles = []): self
    {
        return new self($libelle, $path, $roles);
    }

    public function __construct(string $libelle, Path $path, array $roles = [])
    {
        $this->libelle = $libelle;
        $this->path = $path;
        $this->roles = $roles;
        $this->labels = [];
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function setPath(Path $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }


    /**
     * @param string[] $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @return string[]
     */
    public function getRole(): array
    {
        return $this->roles;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }
}
