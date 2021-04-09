<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface BrickConfigInterface
{

    public function getCrudConfig(): CrudConfigInterface;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self;

    public function setPageKey(string $pageKey): self;

    public function getPageKey(): string;

    public function getConfig(): array;

    public function getId(): string;
}
