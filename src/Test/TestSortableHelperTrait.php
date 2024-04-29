<?php

namespace Lle\CruditBundle\Test;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Provider\ConfigProvider;

trait TestSortableHelperTrait
{
    public function testSortable()
    {
        $container = static::getContainer();

        $configs = $container->get(ConfigProvider::class);

        foreach ($configs->getConfigurators() as $config) {
            $datasource = $config->getDatasource();
            /** @var Field $field */
            foreach ($config->getFields(CrudConfigInterface::INDEX) as $field) {
                if ($field->isSortable()) {
                    $datasourceParams = new DatasourceParams(sorts: [[$field->getName(), 'ASC']]);
                    $iterable = $datasource->list($datasourceParams);

                    self::assertNotNull($iterable);
                }
            }
        }
    }
}
