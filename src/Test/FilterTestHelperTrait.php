<?php

namespace Lle\CruditBundle\Test;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Filter\FilterType\AbstractFilterType;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\DateFilterType;
use Lle\CruditBundle\Filter\FilterType\DateTimeFilterType;
use Lle\CruditBundle\Filter\FilterType\EntityFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberRangeFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use Lle\CruditBundle\Filter\FilterType\TreeFilterType;
use Lle\CruditBundle\Filter\FilterType\WorkflowFilterType;
use Lle\CruditBundle\Provider\ConfigProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait FilterTestHelperTrait
{
    protected iterable $cruditDatasources;

    protected KernelBrowser $client;

    public function testFilters(): void
    {
        $this->client = static::buildClient();
        $container = static::getContainer();
        
        $this->loginUser();
        
        $configs = $container->get(ConfigProvider::class)->getConfigurators();
        $filterState = [];
        /** @var CrudConfigInterface $config */
        foreach ($configs as $config) {
            $datasource = $config->getDatasource();

            /** @var AbstractFilterType $filter */
            foreach ($datasource->getFilterset()?->getFilters() ?? [] as $filter) {
                $classKey = strtolower(array_slice(explode('\\', $datasource->getClassName()), 2, 1)[0]);
                $dataValue = match (get_class($filter)) {
                    BooleanFilterType::class => [
                        'value' => true,
                        'op' => 'eq'
                    ],
                    ChoiceFilterType::class => [
                        'value' => 'success',
                    ],
                    DateFilterType::class => [
                        'value' => '2024-03-22',
                        'op' => 'eq',
                        'time' => '19:30:25'
                    ],
                    DateTimeFilterType::class => [
                        'value' => '2024-03-22',

                    ],
                    EntityFilterType::class, TreeFilterType::class => [
                        'value' => '42',
                        'op' => 'eq',
                        'item' => '[{"id":"42","text":"label"}]'
                    ],
                    NumberFilterType::class => [
                        'value' => 42,
                        'op' => 'eq',
                    ],
                    NumberRangeFilterType::class => [
                        'value' => 10,
                        'op' => 'interval',
                        'to' => '42'
                    ],
                    PeriodeFilterType::class => [
                        'value' => '2024-03-22',
                        'op' => 'interval',
                        'to' => '2024-03-23'
                    ],
                    StringFilterType::class => [
                        'value' => 'toto',
                        'op' => 'contains'
                    ],
                    WorkflowFilterType::class => [
                        'value' => 'validated',
                        'op' => 'eq',
                        'items' => '[{"id":"validated","text":"Validé"}]'
                    ],
                    default => [],
                };

                $filterState[$classKey][$filter->getId()] = $dataValue;
            }
            try {
                $datasource->setFilterState($filterState);
                $params = new DatasourceParams();
                $params->setEnableFilters(true);
                $res = $datasource->list($params);
                $this->assertNotNull($res);
            } catch (\Exception $exception) {
                $this->assertTrue(false, 'class : ' . $classKey . ' error: ' . $exception->getMessage());
            }
        }
    }

    protected function loginUser(): void
    {
        $userRepository = static::getContainer()->get(self::USER_REPOSITORY);
        
        $this->client->loginUser($userRepository->findOneByEmail(self::LOGIN_USER));
    }

    protected function buildClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return static::createClient();
    }
}
