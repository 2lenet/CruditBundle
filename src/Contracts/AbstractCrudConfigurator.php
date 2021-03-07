<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

abstract class AbstractCrudConfigurator implements CrudConfiguratorInterface
{

    public function getController(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        $className = explode('\\', $this->getSubjectClass());
        return ltrim(
            strtolower(
                join(
                    '-',
                    (array) preg_split('/(?=[A-Z])/', $className[\count($className) - 1])
                )
            ),
            '-'
        );
    }
}
