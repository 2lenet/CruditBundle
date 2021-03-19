<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

abstract class AbstractCrudConfig implements CrudConfigInterface
{

    public function getController(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return null;
    }
}
