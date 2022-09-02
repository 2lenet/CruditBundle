<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\SublistBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Field\Field;
use Symfony\Component\HttpFoundation\Request;

class SublistConfig extends AbstractBrickConfig
{
    /** @var Field[] */
    private $fields = [];

    /** @var ItemAction[] */
    private $actions = [];

    /** @var DatasourceInterface */
    private $datasource;

    /** @var DatasourceParams */
    private $datasourceParams;

    /** @var string */
    private $className;
    private $fieldname;

    protected CrudConfigInterface $subCrudConfig;

    public function __construct($fieldname, CrudConfigInterface $subCrudConfig, array $options = [])
    {
        $this->fieldname = $fieldname;
        $this->options = $options;
        $this->subCrudConfig = $subCrudConfig;
        $this->datasource = $subCrudConfig->getDatasource();
    }

    public static function new(string $fieldname, CrudConfigInterface $subCrudConfig, array $options = []): self
    {
        return new self($fieldname, $subCrudConfig, $options);
    }

    public function setCrudConfig(CrudConfigInterface $crudConfig): self
    {
        parent::setCrudConfig($crudConfig);
        if ($this->datasource === null) {
            $this->setDatasource($crudConfig->getDatasource());
        }

        return $this;
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->datasource ?? $this->getCrudConfig()->getDatasource();
    }

    public function setDatasource(DatasourceInterface $datasource): self
    {
        $this->datasource = $datasource;
        return $this;
    }

    public function addAction(ItemAction $action): self
    {
        $this->actions[] = $action;

        return $this;
    }

    public function getConfig(Request $request): array
    {
        $sessionKey = $this->crudConfig->getDatasourceParamsKey()
            . "_sublist_" . $this->subCrudConfig->getName() . $request->get("id");
        $this->setDatasourceParams($this->subCrudConfig->getDatasourceParams($request, $sessionKey));

        return [
            'fields' => $this->getFields(),
            'actions' => $this->getActions(),
            'batch_actions' => [],
            'name' => $this->subCrudConfig->getName(),
            'title' => $this->getCrudConfig()->getTitle('list'),
            'datasource_params' => $this->getDatasourceParams(),
            'detail' => null,
            'hidden_action' => false,
            'bulk' => false,
            'sort' => ['name' => 'id', 'direction' => 'ASC'],
            'canModifyNbEntityPerPage' => false,
            'choices_nb_items' => $this->subCrudConfig->getChoicesNbItems(),
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain()
        ];
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;
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

    public function getDatasourceParams(): DatasourceParams
    {
        return $this->datasourceParams;
    }

    public function setDatasourceParams(DatasourceParams $datasourceParams): self
    {
        $this->datasourceParams = $datasourceParams;
        return $this;
    }

    public function add(string $name, string $type = null, array $options = []): self
    {
        return $this->addField(Field::new($name, $type, $options));
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param mixed $fieldname
     * @return SublistConfig
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;
        return $this;
    }

    public function getSubCrudConfig(): CrudConfigInterface
    {
        return $this->subCrudConfig;
    }
}
