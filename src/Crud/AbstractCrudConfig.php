<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Crud;

use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Contracts\AbstractCrudAutoConfig;
use Lle\CruditBundle\Contracts\DataSourceInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Symfony\Component\HttpFoundation\Request;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use App\Form\CollecteType;
use App\Crudit\Datasource\CollecteDatasource;

abstract class AbstractCrudConfig implements CrudConfigInterface
{
    public abstract function getFields($key): array;

    public function autoFields($fieldnames): array {
        $fields = [];
        foreach ($fieldnames as $field) {
            $fields[] = Field::new($field);
        }
        return $fields;
    }
    
    protected function getFormType(string $pageKey): ?string
    {
        return str_replace('App\Crudit\Config','App\Form',
            str_replace('CrudConfig','Type',get_class($this)));
    }
    
    public function getListActions(): array
    {
        $actions = [];
        $actions[] = ListAction::new('add', $this->getPath(CrudConfigInterface::NEW), Icon::new('plus'));
        return $actions;
    }
    
    public function getItemActions(): array
    {
        $actions = [];
        $actions[] = ItemAction::new('show', $this->getPath(CrudConfigInterface::SHOW));
        $actions[] = ItemAction::new('edit', $this->getPath(CrudConfigInterface::EDIT));
        $actions[] = DeleteAction::new('delete', $this->getPath(CrudConfigInterface::DELETE), Icon::new('trash-alt'));

        return $actions;
    }
    
    public function getDatasource(): DataSourceInterface
    {
        return $this->datasource;
    }

    public function getController(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        $className = get_class($this);
        return strtoupper(str_replace("CrudConfig", "", (substr($className, strrpos($className, '\\') + 1))));
    }

    public function getPath(string $context = self::INDEX, array $params = []): Path
    {
        return Path::new($this->getRootRoute() . '_' . $context, $params);
    }

    public function getBrickConfigs(): array
    {
        return  [
            CrudConfigInterface::INDEX => [
                LinksConfig::new()
                    ->setActions($this->getListActions()),
                ListConfig::new()->addFields($this->getFields(CrudConfigInterface::INDEX))
                    ->setActions($this->getItemActions())
            ],
            CrudConfigInterface::SHOW => [
                LinksConfig::new()->addBack(),
                ShowConfig::new()->addFields($this->getFields(CrudConfigInterface::SHOW))
            ],
            CrudConfigInterface::EDIT => [
                LinksConfig::new()->addBack(),
                FormConfig::new()->setForm($this->getFormType(CrudConfigInterface::EDIT))
            ],
            CrudConfigInterface::NEW => [
                LinksConfig::new()->addBack(),
                FormConfig::new()->setForm($this->getFormType(CrudConfigInterface::NEW))
            ]
        ];
    }

}
