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
    /** @var Field[] */
    private $fields = [];
    /** @var DatasourceInterface */
    private $dataSource;
    private $actions;

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        parent::setCrudConfig($crudConfig);
        if ($this->dataSource === null) {
            $this->setDataSource($crudConfig->getDatasource());
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

    public function setDataSource(DatasourceInterface $dataSource): self
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    public function getDataSource(): DatasourceInterface
    {
        return $this->dataSource;
    }

    public function addAction(): self
    {
        return $this;
    }

    public function getActions(): array
    {
        return [];
    }

    public function setActions($actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function getConfig(Request $request): array
    {
        return [
            'fields' => $this->getFields(),
            'actions' => $this->getActions(),
            'name' => $this->getCrudConfig()->getName(),
            'title' => $this->getCrudConfig()->getTitle('show'),
            'hidden_action' => false,
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
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

    public function add(string $name, string $type = null, array $options = []): self
    {
        return $this->addField(Field::new($name, $type, $options));
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
