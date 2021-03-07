<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Exception;

class UnsupportedBrickConfigurationException extends CruditException
{
    public function __construct(string $brickConfiguratorClassName)
    {
        parent::__construct("Crudit unsupported brick configuration: " . $brickConfiguratorClassName);
    }
}
