<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Lle\CruditBundle\Dto\Field\Field;

class ListConfig extends AbstractBrickConfig
{

    /** @var Field[] */
    private $fields = [];

    /** @var array  */
    private $options;

    /** @var DataSourceInterface */
    private $dataSource;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function setDataSource(DataSourceInterface $dataSource): self
    {
        $this->dataSource = $dataSource;
        return $this;
    }

    public function getDataSource(): ?DataSourceInterface
    {
        return $this->dataSource;
    }
    
    public function AddAction(DtoAction $action): self
    {
        return $this;
    }

    public function getActions(): array
    {
        return [];
    }

    public function getItemActions(): array
    {
        return [];
    }
    
    public function getConfig(): array
    {
        return [
            'fields' => $this->getFields(),
            'actions' => $this->getActions(),
            'item_actions' => $this->getItemActions(),
            'detail' => null,
            'hidden_action' => false,
            'bulk' => false,
            'sort' => ['name' => 'id', 'direction' => 'ASC'],
            'canModifyNbEntityPerPage' => false
        ];
    }
    
    public function addItemAction(){

    }

    public function add(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function addAuto(array $columns): self
    {
        foreach ($columns as $column) {
            $this->add(Field::new($column));
        }
        return $this;
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
