<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Layout;

use Lle\CruditBundle\Dto\Icon;

class ExternalLinkElement extends AbstractLayoutElement
{
    public const string TYPE_HEADER = 'header';
    public const string TYPE_BODY = 'body';

    protected string $type;

    protected string $libelle;

    protected ?Icon $icon = null;

    protected ?string $url;

    protected ?string $target;

    public static function new(
        string $libelle,
        ?string $url,
        ?Icon $icon = null,
        ?string $target = '_blank',
        ?string $role = null,
    ): self {
        $item = new self($libelle, $url, $target, $icon, $role);
        $item->setId(str_replace('menu.', '', $libelle));

        return $item;
    }

    public function getTemplate(): string
    {
        return '@LleCrudit/layout/sb_admin/elements/_external_link.html.twig';
    }

    public function __construct(
        string $libelle,
        ?string $url,
        ?string $target,
        ?Icon $icon = null,
        ?string $role = null,
    ) {
        $this->libelle = $libelle;
        $this->icon = $icon;
        $this->url = $url;
        $this->target = $target;
        $this->role = $role;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): self
    {
        $this->target = $target;

        return $this;
    }
}
