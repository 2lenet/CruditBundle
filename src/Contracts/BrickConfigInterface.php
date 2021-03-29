<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface BrickConfigInterface
{

    public function getCrudConfig(): CrudConfigInterface;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self;

    public function getConfig(): array;
}
