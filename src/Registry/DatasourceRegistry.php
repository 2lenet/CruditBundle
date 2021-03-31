<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Registry;

use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Exception\CruditException;

class DatasourceRegistry
{
    /** @var DatasourceInterface[] */
    private $datasources = [];

    public function __construct(iterable $datasources)
    {
        foreach ($datasources as $datasource) {
            $this->datasources[get_class($datasource)] = $datasource;
        }
    }

    public function get(string $datasourceClassname): DatasourceInterface
    {
        if (array_key_exists($datasourceClassname, $this->datasources)) {
            return $this->datasources[$datasourceClassname];
        }
        throw new CruditException('datasource ' . $datasourceClassname . ' not found');
    }

    /** @param string $classname */
    public function getByClass(string $classname): DatasourceInterface
    {
        foreach ($this->datasources as $datasource) {
            if ($datasource->getClassName() === $classname) {
                return $datasource;
            }
        }
        throw new CruditException('datasource for class ' . $classname . ' not found');
    }
}
