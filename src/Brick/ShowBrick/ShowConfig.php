<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Symfony\Component\HttpFoundation\Request;

class ShowConfig extends AbstractBrickConfig
{
    private array $fields = [];

    private ?DatasourceInterface $datasource = null;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        parent::setCrudConfig($crudConfig);
        if ($this->datasource === null) {
            $this->setDatasource($crudConfig->getDatasource());
        }

        return $this;
    }

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function setDatasource(DatasourceInterface $datasource): self
    {
        $this->datasource = $datasource;

        return $this;
    }

    public function getDatasource(): DatasourceInterface
    {
        /** @var DatasourceInterface $result */
        $result = $this->datasource;

        return $result;
    }

    public function addAction(): self
    {
        return $this;
    }

    public function getConfig(Request $request): array
    {
        return [
            'fields' => $this->getFields(),
            'name' => $this->getCrudConfig()->getName(),
            'title' => $this->getCrudConfig()->getTitle('show'),
            'hidden_action' => false,
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
            'number_cards' => $this->getCrudConfig()->getNumberCards(),
            'css_class_columns_card' => $this->getCrudConfig()->getCssClassColumnsByCard(),
        ];
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    public function addArrayFields(string $cardTitle, array $fields): self
    {
        $this->fields[$cardTitle] = $fields;

        return $this;
    }

    public function addFields(array $fields): self
    {
        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $this->addArrayFields($key, $field);
            } else {
                $this->addField($field);
            }
        }

        return $this;
    }

    public function add(string $name, ?string $type = null, array $options = []): self
    {
        return $this->addField(Field::new($name, $type, $options));
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
