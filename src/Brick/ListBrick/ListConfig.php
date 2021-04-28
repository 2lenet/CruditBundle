<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Field\Field;

class ListConfig extends AbstractBrickConfig
{

    /** @var Field[] */
    private $fields = [];

    /** @var array  */
    private $options;

    /** @var ItemAction[] */
    private $actions = [];

    /** @var DatasourceInterface */
    private $datasource;

    /** @var string */
    private $className;

    /** @var string */
    private $fieldNameAssociation = null;

    /** @var ?callable */
    private $catchQueryAssociationCallback = null;

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
        return $this->datasource ?? $this->getCrudConfig()->getDatasource();
    }

    public function addAction(ItemAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
    
    public function setActions($actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    public function getConfig(): array
    {
        return [
            'fields' => $this->getFields(),
            'actions' => $this->getActions(),
            'detail' => null,
            'hidden_action' => false,
            'bulk' => false,
            'sort' => ['name' => 'id', 'direction' => 'ASC'],
            'canModifyNbEntityPerPage' => false
        ];
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function addFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }

    public function add(string $name, string $type = null, array $options = []): self
    {
        return $this->addField(Field::new($name, $type, $options));
    }
    
    public function setClassName(string $className): self
    {
        $this->className = $className;
        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasCatchQueryAssociation(): bool
    {
        return $this->catchQueryAssociationCallback !== null;
    }

    public function catchQueryAssociation(QueryAdapterInterface $query, string $alias): QueryAdapterInterface
    {
        if ($this->hasCatchQueryAssociation() && is_callable($this->catchQueryAssociationCallback)) {
            return \call_user_func($this->catchQueryAssociationCallback, $query, $alias);
        }
        return $query;
    }

    public function getFieldNameAssociation(): ?string
    {
        return $this->fieldNameAssociation;
    }

    public function setCatchQueryAssociation(callable $callback): self
    {
        $this->catchQueryAssociationCallback = $callback;
        return $this;
    }

    public function setFieldNameAssociation(string $fieldNameAssociation): self
    {
        $this->fieldNameAssociation = $fieldNameAssociation;
        return $this;
    }
}
