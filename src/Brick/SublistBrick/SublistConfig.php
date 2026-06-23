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
    private array $fields = [];
    /** @var ItemAction[] */
    private array $actions = [];
    private ?DatasourceInterface $datasource = null;
    private ?DatasourceParams $datasourceParams = null;
    private string $className;
    private string $fieldname;
    private bool $sortable = false;
    protected CrudConfigInterface $subCrudConfig;

    public function __construct(string $fieldname, CrudConfigInterface $subCrudConfig, array $options = [])
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
            . "_sublist_" . $this->subCrudConfig->getName() . $request->attributes->get("id");

        $subDatasourceParams = $this->subCrudConfig->getDatasourceParams($request, $sessionKey);

        if ($this->getDatasourceParams()) {
            $subDatasourceParams->setNonDefaultValuesOnSubDatasourceParams($this->getDatasourceParams());
        }

        $this->setDatasourceParams($subDatasourceParams);

        $sortable = $this->isSortableActive();

        return [
            'fields' => $this->getFields(),
            'actions' => $this->getActions(),
            'batch_actions' => [],
            'name' => $this->subCrudConfig->getName(),
            'title' => $this->getTitle(),
            'titleCss' => $this->getTitleCss(),
            'datasource_params' => $this->getDatasourceParams(),
            'detail' => null,
            'hidden_action' => false,
            'bulk' => false,
            'sort' => ['name' => 'id', 'direction' => 'ASC'],
            'sortable' => $sortable,
            'sort_url' => $sortable
                ? ($this->subCrudConfig->getSortableUrl() ?? $this->subCrudConfig->getPath(CrudConfigInterface::SORT))
                : null,
            'canModifyNbEntityPerPage' => false,
            'choices_nb_items' => $this->subCrudConfig->getChoicesNbItems(),
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
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

    public function setActions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function getDatasourceParams(): ?DatasourceParams
    {
        return $this->datasourceParams;
    }

    public function setDatasourceParams(DatasourceParams $datasourceParams): self
    {
        $this->datasourceParams = $datasourceParams;

        return $this;
    }

    public function add(string $name, ?string $type = null, array $options = []): self
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

    public function withSortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    private function isSortableActive(): bool
    {
        if (!$this->sortable || $this->subCrudConfig->getSortableField() === null) {
            return false;
        }

        $sorts = $this->getDatasourceParams()?->getSorts();
        $primarySort = $sorts[0] ?? null;
        $primarySortField = is_array($primarySort) ? $primarySort[0] : $primarySort;
        $primarySortDir = is_array($primarySort) ? strtoupper($primarySort[1] ?? 'ASC') : 'ASC';

        return $primarySortField === $this->subCrudConfig->getSortableField() && $primarySortDir === 'ASC';
    }

    public function getSubCrudConfig(): CrudConfigInterface
    {
        return $this->subCrudConfig;
    }
}
