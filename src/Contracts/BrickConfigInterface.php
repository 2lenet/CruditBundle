<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Symfony\Component\HttpFoundation\Request;

interface BrickConfigInterface
{
    public function getCrudConfig(): CrudConfigInterface;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self;

    public function getPageKey(): string;

    public function setPageKey(string $pageKey): self;

    public function getDatasource(): DatasourceInterface;

    public function getConfig(Request $request): array;

    public function getId(): string;

    public function setId(string $id): self;

    /** @return BrickConfigInterface[] */
    public function getChildren(): array;

    public function getOptions(): array;

    public function setOptions(array $options): AbstractBrickConfig;

    public function getRole(): ?string;

    public function setRole(?string $role): self;
}
