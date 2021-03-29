<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FormBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\FormField;

class FormConfig extends AbstractBrickConfig
{

    /** @var DatasourceInterface */
    private $dataSource;

    /** @var ?string */
    private $form = null;

    /** @var array */
    private $options = [];

    /** @var FormField[] */
    private $fields = [];

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->form = $options['form'] ?? null;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function setForm(?string $form): self
    {
        $this->form = $form;
        return $this;
    }

    public function add(FormField $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function addAuto(array $fields): self
    {
        foreach ($fields as $field) {
            $this->fields[] = FormField::new($field);
        }
        return $this;
    }

    /** @return FormField[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        parent::setCrudConfig($crudConfig);
        if ($this->dataSource === null) {
            $this->setDataSource($crudConfig->getDatasource());
        }
        return $this;
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
}
