<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

interface BrickConfigInterface
{

    public function getCrudConfig(): CrudConfigInterface;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self;

    public function setPageKey(string $pageKey): self;

    public function getPageKey(): string;

    public function getConfig(Request $request): array;

    public function getId(): string;

    public function setId(string $id): self;

    /** @return BrickConfigInterface[] */
    public function getChildren(): array;
}
