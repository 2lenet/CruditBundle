<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ListBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Brick\RuptureAwareTrait;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Dto\ResourceView;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListFactory extends AbstractBasicBrickFactory
{
    use RuptureAwareTrait;

    private FormFactoryInterface $formFactory;
    protected FieldRegistry $fieldRegistry;
    protected TranslatorInterface $translator;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        FieldRegistry $fieldRegistry,
        TranslatorInterface $translator,
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->formFactory = $formFactory;
        $this->fieldRegistry = $fieldRegistry;
        $this->translator = $translator;
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
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/list_items')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setPath($this->getPath($brickConfigurator))
                ->setData([
                    'lines' => $this->getLines($brickConfigurator),
                    'totals' => $this->getTotals($brickConfigurator),
                    'grouped_totals' => $this->getGroupedTotals($brickConfigurator),
                    'batch_actions' => $batchActions,
                    'auto_refresh' => $brickConfigurator->getCrudConfig()->getListAutoRefresh(),
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
    protected function getLines(ListConfig $brickConfigurator): array
    {
        $lines = [];

        $dsParams = $brickConfigurator->getDatasourceParams();
        $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
        $this->backToTheFirstPage($dsParams, $brickConfigurator);
        $this->prependRuptureSorts($dsParams, $brickConfigurator->getFields());
        $resources = $brickConfigurator->getDatasource()->list($dsParams);

        foreach ($resources as $resource) {
            $lines[] = $this->resourceResolver->resolve(
                $resource,
                $this->getFields($brickConfigurator),
                $brickConfigurator->getDatasource(),
                $brickConfigurator->getCrudConfig()
            );
        }

        return $this->annotateRuptureBreaks($lines);
    }

    /**
     * A page past the last one (a filter applied from page 3 leaves two pages, a list that shrank) shows nothing and no
     * way back: the list goes back to the first page, in the session too (else the next visit finds the same offset).
     */
    private function backToTheFirstPage(DatasourceParams $dsParams, ListConfig $brickConfigurator): void
    {
        if ($dsParams->getCount() < 1 || $dsParams->getOffset() < $dsParams->getCount()) {
            return;
        }

        $dsParams->setOffset(0);
        $session = $this->getRequest()->getSession();
        $key = $brickConfigurator->getCrudConfig()->getDatasourceParamsKey();
        $stored = $session->get($key);
        if (is_array($stored)) {
            $stored['offset'] = 0;
            $session->set($key, $stored);
        }
    }

    protected function getTotals(ListConfig $brickConfigurator): array
    {
        $crudConfig = $brickConfigurator->getCrudConfig();

        if (count($crudConfig->getTotalFields()) > 0) {
            $dsParams = $brickConfigurator->getDatasourceParams();
            $dsParams->setCount($brickConfigurator->getDatasource()->count($dsParams));
            /** @var array $totalByField */
            $totalByField = $brickConfigurator->getDatasource()->getTotals($dsParams, $crudConfig->getTotalFields());

            return $this->buildTotalFieldViews($crudConfig->getTotalFields(), $totalByField);
        }

        return [];
    }

    protected function getGroupedTotals(ListConfig $brickConfigurator): array
    {
        $crudConfig = $brickConfigurator->getCrudConfig();

        return $this->computeGroupedTotals(
            $brickConfigurator->getDatasource(),
            $brickConfigurator->getDatasourceParams(),
            $crudConfig->getTotalFields(),
            $brickConfigurator->getFields()
        );
    }

    /** @return Field[] */
    protected function getFields(ListConfig $brickConfigurator): array
    {
        return $brickConfigurator->getFields();
    }

    public function getRequestParametersScop(): array
    {
        return ['id'];
    }
}
