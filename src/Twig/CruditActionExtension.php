<?php

namespace Lle\CruditBundle\Twig;

use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CruditActionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('crudit_is_dropdown_action', [$this, 'isDropdownAction']),
            new TwigFunction('crudit_is_dropdown_group_name', [$this, 'isDropdownGroupName']),
        ];
    }

    public function isDropdownAction(AbstractAction $action): bool
    {
        return $action instanceof DropdownAction;
    }

    public function isDropdownGroupName(mixed $groupName): bool
    {
        return is_string($groupName);
    }
}
