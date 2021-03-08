<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Path;

abstract class AbstractCrudConfigurator implements CrudConfiguratorInterface
{

    public function getController(): ?string
    {
        return null;
    }

    public function getLinkElement(Path $path): ?LinkElement
    {
        return LinkElement::new(
            ucfirst(str_replace('-', ' ', $this->getName())),
            $path,
            Icon::new('circle', Icon::TYPE_FAR)
        );
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
