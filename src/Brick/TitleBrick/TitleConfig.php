<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\TitleBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Symfony\Component\HttpFoundation\Request;

class TitleConfig extends AbstractBrickConfig
{
    private ?DatasourceInterface $datasource;

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

    public function getConfig(Request $request): array
    {
        return [
            'title' => $this->getCrudConfig()->getTitle('show'),
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
        ];
    }
}
