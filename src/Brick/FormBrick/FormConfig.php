<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FormBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\FormField;
use Lle\CruditBundle\Dto\Path;

class FormConfig extends AbstractBrickConfig
{
    private ?DatasourceInterface $dataSource = null;

    private ?string $form = null;

    /** @var FormField[] */
    private array $fields = [];

    private ?Path $successRedirectPath = null;

    private string $messageSuccess;

    private string $messageError;

    protected ?Path $cancelPath = null;

    /**
     * For sublist forms.
     * assocField contains the name of the parent property
     */
    protected ?string $assocProperty = null;

    protected bool $sublist = false;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->form = $options['form'] ?? null;
    }

    public function setSuccessRedirectPath(Path $path): self
    {
        $this->successRedirectPath = $path;
        return $this;
    }

    public function getSuccessRedirectPath(): Path
    {
        return $this->successRedirectPath ?? $this->getCrudConfig()->getPath();
    }

    public function setFlashMessageSuccess(string $message): self
    {
        $this->messageSuccess = $message;
        return $this;
    }

    public function setFlashMessageError(string $message): self
    {
        $this->messageError = $message;
        return $this;
    }

    public function getMessageError(): string
    {
        return $this->messageError ?? 'crudit.message.error';
    }

    public function getMessageSuccess(): string
    {
        return $this->messageSuccess ?? 'crudit.message.success';
    }

    public function getForm(?object $resource = null): ?string
    {
        return $this->form;
    }

    public function setForm(?string $form): self
    {
        $this->form = $form;
        return $this;
    }

    public function isSublist(): bool
    {
        return $this->sublist;
    }

    public function setSublist(?string $assocProperty): self
    {
        $this->sublist = true;
        $this->assocProperty = $assocProperty;

        return $this;
    }

    public function getAssocProperty(): ?string
    {
        return $this->assocProperty;
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
        if (!$this->successRedirectPath) {
            $this->setSuccessRedirectPath($crudConfig->getAfterEditPath());
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

    public function getCancelPath(): ?Path
    {
        return $this->cancelPath;
    }

    public function setCancelPath(?Path $cancelPath): FormConfig
    {
        $this->cancelPath = $cancelPath;

        return $this;
    }
}
