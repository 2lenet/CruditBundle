<?php

namespace Lle\CruditBundle\Test;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Provider\ConfigProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\RouterInterface;

trait TestSortableHelperTrait
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::buildClient();
        $this->container = static::getContainer();

        $this->loginUser();
    }

    public function testSortable()
    {
        $configs = $this->container->get(ConfigProvider::class);

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

    protected function buildClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return static::createClient();
    }

    protected function loginUser(): void
    {
        $userRepository = $this->container->get(self::USER_REPOSITORY);

        $this->client->loginUser($userRepository->findOneByEmail(self::LOGIN_USER));
    }
}
