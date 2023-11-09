<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBrickConfig implements BrickConfigInterface
{
    protected CrudConfigInterface $crudConfig;
    protected string $pageKey;
    protected string $id;
    protected array $options = [];
    private ?string $role = null;
    private ?string $template = null;

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function setPageKey(string $pageKey): self
    {
        $this->pageKey = $pageKey;

        return $this;
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->crudConfig->getDatasource();
    }

    public function getConfig(Request $request): array
    {
        return [
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
        ];
    }

    public function getCrudConfig(): CrudConfigInterface
    {
        return $this->crudConfig;
    }

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        $this->crudConfig = $crudConfig;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): BrickConfigInterface
    {
        $this->id = $id;

        return $this;
    }

    /** @return BrickConfigInterface[] */
    public function getChildren(): array
    {
        return [];
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): AbstractBrickConfig
    {
        $this->options = $options;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param ?string $role
     */
    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): BrickConfigInterface
    {
        $this->template = $template;

        return $this;
    }
}
