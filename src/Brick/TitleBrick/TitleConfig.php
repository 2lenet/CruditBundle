<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TitleBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Symfony\Component\HttpFoundation\Request;

class TitleConfig extends AbstractBrickConfig
{
    private ?DatasourceInterface $dataSource;

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

    public function getConfig(Request $request): array
    {
        return [
            'title' => $this->getCrudConfig()->getTitle('show'),
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain()
        ];
    }
}
