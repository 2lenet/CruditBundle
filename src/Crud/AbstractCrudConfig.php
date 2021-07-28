<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Crud;

use App\Entity\Objet;
use Lle\CruditBundle\Brick\FilterBrick\FilterConfig;
use Lle\CruditBundle\Brick\FormBrick\FormConfig;
use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Brick\ListBrick\ListConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\TabBrick\TabConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\Action\DeleteAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Exporter\Exporter;
use Lle\CruditBundle\Exporter\ExportParams;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCrudConfig implements CrudConfigInterface
{
    protected DatasourceInterface $datasource;

    abstract public function getFields($key): array;

    public function autoFields($fieldnames): array
    {
        $fields = [];
        foreach ($fieldnames as $field) {
            $fields[] = Field::new($field);
        }

        return $fields;
    }

    public function getFilterset(): ?FilterSetInterface
    {
        return $this->getDatasource()->getFilterset();
    }

    /**
     * @param string $pageKey
     * @return string|null
     */
    protected function getFormType(string $pageKey): ?string
    {
        return str_replace(
            'App\Crudit\Config',
            'App\Form',
            str_replace('CrudConfig', 'Type', get_class($this))
        );
    }

    public function getListActions(): array
    {
        $actions = [];

        /**
         * Create new resource action
         */
        $actions[] = ListAction::new(
            'action.add',
            $this->getPath(CrudConfigInterface::NEW),
            Icon::new('plus')
        );

        /**
         * Export filtered list action
         */
        $actions[] = ListAction::new(
            "action.export",
            $this->getPath(CrudConfigInterface::EXPORT),
            Icon::new("file-export")
        )
            ->setModal("@LleCrudit/modal/_export.html.twig")
            ->setConfig(
                [
                    "export" => [Exporter::CSV, Exporter::EXCEL],
                ]
            );

        return $actions;
    }

    public function getItemActions(): array
    {
        $actions = [];
        $actions[] = ItemAction::new(
            'action.show',
            $this->getPath(CrudConfigInterface::SHOW),
            Icon::new('search')
        )->setCssClass('btn btn-primary btn-sm mr-1');
        $actions[] = ItemAction::new(
            'action.edit',
            $this->getPath(CrudConfigInterface::EDIT),
            Icon::new('edit')
        )->setCssClass('btn btn-secondary btn-sm mr-1');
        $actions[] = DeleteAction::new(
            'action.delete',
            $this->getPath(CrudConfigInterface::DELETE),
            Icon::new('trash-alt')
        )
            ->setCssClass('btn btn-danger btn-sm mr-1')
            ->setModal("@LleCrudit/modal/_confirm_delete.html.twig");

        return $actions;
    }

    public function getShowActions(): array
    {
        $actions = [];
        $actions[] = ItemAction::new(
            'action.list',
            $this->getPath(CrudConfigInterface::INDEX),
            Icon::new('list')
        )->setCssClass('btn btn-secondary btn-sm mr-1');

        $actions[] = ItemAction::new(
            'action.edit',
            $this->getPath(CrudConfigInterface::EDIT),
            Icon::new('edit')
        )->setCssClass('btn btn-secondary btn-sm mr-1');
        $actions[] = DeleteAction::new(
            'action.delete',
            $this->getPath(CrudConfigInterface::DELETE),
            Icon::new('trash-alt')
        )
            ->setCssClass('btn btn-danger btn-sm mr-1')
            ->setModal("@LleCrudit/modal/_confirm_delete.html.twig");

        return $actions;
    }

    public function getDatasource(): DatasourceInterface
    {
        return $this->datasource;
    }

    public function getDatasourceParams(Request $request): DatasourceParams
    {
        $limit = $request->query->get(strtolower($this->getName()) . '_limit', $this->getNbItems());
        $offset = $request->query->get(strtolower($this->getName()) . '_offset', 0);

        $sortField = $request->query->get(strtolower($this->getName()) . '_sort', "");
        $sortOrder = $request->query->get(strtolower($this->getName()) . '_sort_order', "");

        if ($sortField) {
            $sortArray = [[$sortField, $sortOrder]];
        } else {
            $sortArray = $this->getDefaultSort();
        }

        return new DatasourceParams(intval($limit), intval($offset), $sortArray, []);
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

    public function getTitle(string $key): ?string
    {
        return "crud.title." . strtolower($key) . "." . strtolower($this->getName());
    }

    public function getPath(string $context = self::INDEX, array $params = []): Path
    {
        $path = Path::new($this->getRootRoute() . '_' . $context, $params);
        $path->setRole(sprintf("ROLE_%s_%s",
            $this->getName(),
            $context
        ));

        return $path;
    }

    public function getBrickConfigs(): array
    {
        $indexBricks = [];
        $indexBricks[] = LinksConfig::new(['title' => $this->getTitle('list')])->setActions($this->getListActions());

        if ($this->getFilterset()) {
            $indexBricks[] = FilterConfig::new()
                ->setFilterset($this->getFilterset());
        }

        $indexBricks[] = ListConfig::new()
            ->addFields($this->getFields(CrudConfigInterface::INDEX))
            ->setActions($this->getItemActions())
            ->setBatchActions($this->getListActions());

        $showBricks = [];
        $showBricks[] = LinksConfig::new(['title' => $this->getTitle('show')])->setActions($this->getShowActions());
        $tabs = $this->getTabs();
        if ($tabs) {
            $tabConf = TabConfig::new();

            // default "show" tab
            $tabConf->add('tab.info', ShowConfig::new()->addFields($this->getFields(CrudConfigInterface::SHOW)));

            // additional tabs
            foreach ($tabs as $label => $bricks) {
                if (!is_array($bricks)) {
                    $bricks = [$bricks];
                }
                $tabConf->adds($label, $bricks);
            }
            $showBricks[] = $tabConf;
        } else {
            $showBricks[] = ShowConfig::new()->addFields($this->getFields(CrudConfigInterface::SHOW));
        }

        return [
            CrudConfigInterface::INDEX => $indexBricks,
            CrudConfigInterface::SHOW => $showBricks,
            CrudConfigInterface::EDIT => [
                FormConfig::new()
                    ->setForm($this->getFormType(CrudConfigInterface::EDIT))
                    ->setCancelPath($this->getPath(CrudConfigInterface::INDEX))
            ],
            CrudConfigInterface::NEW => [
                FormConfig::new()
                    ->setForm($this->getFormType(CrudConfigInterface::NEW))
                    ->setCancelPath($this->getPath(CrudConfigInterface::INDEX))
            ]
        ];
    }

    public function getTabs(): array
    {
        return [];
    }

    public function getForm($resource)
    {
        return null;
    }

    public function getDefaultSort(): array
    {
        $fields = $this->getFields(self::INDEX);

        return count($fields) > 0 ? [[
            $fields[0]->getName(),
            "ASC",
        ]] : [];
    }

    public function getExportParams(string $format): ExportParams
    {
        return ExportParams::new()
            ->setFilename($this->getName());
    }

    public function getAfterEditPath(): Path
    {
        return $this->getPath(CrudConfigInterface::INDEX);
    }

    public function getNbItems(): int
    {
        return 30;
    }
}
