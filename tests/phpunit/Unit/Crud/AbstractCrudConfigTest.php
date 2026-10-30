<?php

namespace phpunit\Unit\Crud;

use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\SublistBrick\SublistConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Dto\Field\Field;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AbstractCrudConfigTest extends TestCase
{
    public function testEipFieldsOfFieldsKeys(): void
    {
        $config = new EipCrudConfig($this->createDatasource('App\\Entity\\Order'), [
            CrudConfigInterface::INDEX => [
                Field::new('label')->setEditable('app_crudit_order_editdata'),
                Field::new('reference'),
            ],
            CrudConfigInterface::SHOW => [
                'group' => [Field::new('comment')->setEditInPlace(true)],
            ],
            'SUBLIST' => [Field::new('quantity')->setEditInPlace(true)],
        ]);

        self::assertSame(['comment', 'label'], $this->getNames($config->getEipFields()));
    }

    public function testEipFieldsOfAdditionalFieldsKeys(): void
    {
        $config = new EipAdditionalKeysCrudConfig($this->createDatasource('App\\Entity\\Order'), [
            'SUBLIST' => [Field::new('quantity')->setEditInPlace(true)],
        ]);

        self::assertSame(['quantity'], $this->getNames($config->getEipFields()));
    }

    public function testEipFieldsOfTabs(): void
    {
        $lineConfig = new EipCrudConfig($this->createDatasource('App\\Entity\\OrderLine'), []);

        $config = new EipCrudConfig($this->createDatasource('App\\Entity\\Order'), [], [
            'tab.detail' => ShowConfig::new()->addFields([Field::new('status')->setEditInPlace(true)]),
            'tab.lines' => SublistConfig::new('order', $lineConfig)
                ->setFields([Field::new('price')->setEditable('app_crudit_orderline_editdata')]),
        ]);

        self::assertSame(['status'], $this->getNames($config->getEipFields()));
    }

    private function createDatasource(string $className): DatasourceInterface
    {
        $datasource = $this->createMock(DatasourceInterface::class);
        $datasource->method('getClassName')->willReturn($className);

        return $datasource;
    }

    /**
     * @param Field[] $fields
     * @return string[]
     */
    private function getNames(array $fields): array
    {
        $names = array_values(array_unique(array_map(fn(Field $field) => $field->getName(), $fields)));
        sort($names);

        return $names;
    }
}

class EipCrudConfig extends AbstractCrudConfig
{
    public function __construct(
        DatasourceInterface $datasource,
        private array $fieldsByKey,
        private array $tabs = [],
    ) {
        $this->datasource = $datasource;
        $this->setParameterBag(new ParameterBag(['lle_crudit.generate_default_role' => false]));
    }

    public function getFields(string $key): array
    {
        return $this->fieldsByKey[$key] ?? [];
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function getRootRoute(): ?string
    {
        return 'app_crudit_order';
    }
}

class EipAdditionalKeysCrudConfig extends EipCrudConfig
{
    public const ADDITIONAL_FIELDS_KEYS = ['SUBLIST'];
}
