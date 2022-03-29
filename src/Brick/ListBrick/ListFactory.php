<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ListFactory extends AbstractBasicBrickFactory
{
    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory
    )
    {
        parent::__construct($resourceResolver, $requestStack);
        $this->formFactory = $formFactory;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (ListConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);

        if ($brickConfigurator instanceof ListConfig) {
            $batchActions = [];

            foreach ($brickConfigurator->getBatchActions() as $batchAction) {
                if ($batchAction->getForm()) {
                    $form = $this->formFactory->create($batchAction->getForm());
                    $batchActions[] = ['action' => $batchAction, 'form' => $form->createView()];
                } else {
                    $batchActions[] = ['action' => $batchAction, 'form' => ''];
                }
            }

            $view
                ->setTemplate('@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setPath($this->getPath($brickConfigurator))
                ->setData([
                    'lines' => $this->getLines($brickConfigurator),
                    'batch_actions' => $batchActions,
                ]);
        }

        return $view;
    }

    public function getPath(BrickConfigInterface $brickConfig): Path
    {
        return $brickConfig->getCrudConfig()->getPath(
            'brick',
            array_merge($this->getRequestParameters(), ['idBrick' => $brickConfig->getId(), '_format' => 'html'])
        );
    }

    /** @return ResourceView[] */
    private function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];

        if ($brickConfigurator->getDatasource()) {
            // normal list
            $dsParams = $brickConfigurator->getDatasourceParams();
            $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
            $resources = $brickConfigurator->getDatasource()->list($dsParams);

            foreach ($resources as $resource) {
                $lines[] = $this->resourceResolver->resolve(
                    $resource,
                    $this->getFields($brickConfigurator),
                    $brickConfigurator->getDatasource(),
                    $brickConfigurator->getCrudConfig()
                );
            }
        }

        return $lines;
    }

    /** @return Field[] */
    private function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    public function getRequestParametersScop(): array
    {
        return ['id'];
    }
}
