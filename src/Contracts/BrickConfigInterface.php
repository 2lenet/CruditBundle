<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

interface BrickConfigInterface
{

    public function getConfig(): array;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self;
}
