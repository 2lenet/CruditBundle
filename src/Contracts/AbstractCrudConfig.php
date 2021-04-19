<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Contracts;

use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

abstract class AbstractCrudConfig implements CrudConfigInterface
{
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
                    ->addAction(ListAction::new('add', $this->getPath(CrudConfigInterface::NEW), Icon::new('plus')))
                    ->addAction(ListAction::new('import', Path::new('import_collecte'), Icon::new('download'))),
                ListConfig::new()->addFields($this->getDefaultFields(CrudConfigInterface::INDEX))
                    ->addAction(ItemAction::new('show', $this->getPath(CrudConfigInterface::SHOW), Icon::new('eye')))
                    ->addAction(ItemAction::new('edit', $this->getPath(CrudConfigInterface::EDIT), Icon::new('edit')))
            ],
            CrudConfigInterface::SHOW => [
                LinksConfig::new()->addBack(),
                ShowConfig::new()->addFields($this->getDefaultFields(CrudConfigInterface::SHOW))
            ],
            CrudConfigInterface::EDIT => [
                LinksConfig::new()->addBack(),
                FormConfig::new()->setForm($this->getDefaultFormType(CrudConfigInterface::EDIT))
            ],
            CrudConfigInterface::NEW => [
                LinksConfig::new()->addBack(),
                FormConfig::new()->setForm($this->getDefaultFormType(CrudConfigInterface::NEW))
            ]
        ];
    }

    protected function getDefaultFields(string $pageKey): array
    {
        return [];
    }

    protected function getDefaultFormType(string $pageKey): ?string
    {
        return null;
    }
}
