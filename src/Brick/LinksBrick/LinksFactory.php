<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Action\PageActionsBuilder;
use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\ShowBrick\ShowStatus;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Lle\CruditBundle\Service\NavigationStack;
use Symfony\Component\HttpFoundation\RequestStack;

class LinksFactory extends AbstractBasicBrickFactory
{
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        protected NavigationStack $navigationStack,
        protected PageActionsBuilder $pageActionsBuilder,
        protected ShowStatus $showStatus,
    ) {
        parent::__construct($resourceResolver, $requestStack);
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (LinksConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        /** @var LinksConfig $brickConfigurator */
        /** @var \Stringable $resource */
        $resource = $this->getItem($brickConfigurator);

        $actions = $this->getActions($brickConfigurator);

        /** @var LinksConfig $brickConfigurator */
        $view = new BrickView($brickConfigurator);
        $view
            ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/links')
            ->setConfig($brickConfigurator->getConfig($this->getRequest()))
            ->setData([
                // The actions as given, for the templates of the projects; sorted for the bar (brick/links/_actions)
                'actions' => $actions,
                'page_actions' => $this->pageActionsBuilder->build($actions, $resource),
                'entity' => (string)$resource,
                'resource' => $resource,
            ] + $this->getRecordHeader($brickConfigurator, $resource));

        return $view;
    }

    /**
     * The header of a record (lot 3.3.a): its status (the first workflow field, isShowStatusInHeader()) beside the
     * title, a subtitle (getShowSubtitle()) and when it was created and modified, by whom (getCreatedAt(),
     * getCreatedBy(), getUpdatedAt(), getUpdatedBy() of the element, as in the 1.x where they were at the foot of
     * the record).
     *
     * @return array{status?: FieldView, subtitle?: string, meta?: array<string, mixed>}
     */
    private function getRecordHeader(LinksConfig $brickConfig, ?object $resource): array
    {
        if ($resource === null || $brickConfig->getPageKey() !== CrudConfigInterface::SHOW) {
            return [];
        }

        $crudConfig = $brickConfig->getCrudConfig();
        $header = [];

        $field = $this->showStatus->find($crudConfig);
        if ($field !== null) {
            // The role of the field is checked by the resolver: no status if it is refused
            $status = $this->resourceResolver
                ->resolve($resource, [$field], $brickConfig->getDataSource(), $crudConfig)
                ->getFields()[0] ?? null;
            if ($status instanceof FieldView) {
                $header['status'] = $status;
            }
        }

        $subtitle = $crudConfig->getShowSubtitle($resource);
        if ($subtitle !== null && $subtitle !== '') {
            $header['subtitle'] = $subtitle;
        }

        $meta = [];
        foreach (['createdAt', 'createdBy', 'updatedAt', 'updatedBy'] as $property) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($resource, $getter)) {
                $value = $resource->$getter();
                // An author is shown by its text: none when it has none (an entity without __toString())
                if (str_ends_with($property, 'By') && !\is_string($value) && !$value instanceof \Stringable) {
                    $value = null;
                }
                $meta[$property] = $value;
            }
        }
        if (($meta['createdAt'] ?? null) !== null || ($meta['updatedAt'] ?? null) !== null) {
            $header['meta'] = $meta;
        }

        return $header;
    }

    public function getActions(LinksConfig $brickConfig): array
    {
        $backUrl = $this->navigationStack->peek();
        $actions = $brickConfig->getActions();

        // Resolve the back URL for "back to list" actions (covers ListAction on list pages and ItemAction on show pages)
        foreach ($actions as $action) {
            if ($action->isBack() && $action->getUrl() === null && $backUrl !== null) {
                $action->setUrl($backUrl);
            }
        }

        if ($brickConfig->hasBack()) {
            $action = ListAction::new('action.back', $brickConfig->getCrudConfig()->getPath())
                ->setBack();
            if ($backUrl !== null) {
                $action->setUrl($backUrl);
            }
            $actions[] = $action;
        }

        return $actions;
    }

    private function getItem(LinksConfig $brickConfigurator): ?object
    {
        $id = $this->getRequest()->attributes->get('id');
        if ($id) {
            $resource = $brickConfigurator->getDataSource()->get($id);

            return $resource;
        } else {
            return null;
        }
    }
}
