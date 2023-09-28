<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\FilterBrick;

use Lle\CruditBundle\Brick\AbstractBasicBrickFactory;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Dto\BrickView;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Resolver\ResourceResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class FilterFactory extends AbstractBasicBrickFactory
{
    private FilterState $filterState;
    private Security $security;

    public function __construct(
        ResourceResolver $resourceResolver,
        RequestStack $requestStack,
        FilterState $filterState,
        Security $security,
    ) {
        parent::__construct($resourceResolver, $requestStack);
        $this->filterState = $filterState;
        $this->security = $security;
    }

    public function support(BrickConfigInterface $brickConfigurator): bool
    {
        return (FilterConfig::class === get_class($brickConfigurator));
    }

    public function buildView(BrickConfigInterface $brickConfigurator): BrickView
    {
        $view = new BrickView($brickConfigurator);
        if ($brickConfigurator instanceof FilterConfig) {
            /** @var FilterSetInterface $filterset */
            $filterset = $brickConfigurator->getCrudConfig()->getFilterset();
            $view
                ->setTemplate($brickConfigurator->getTemplate() ?? '@LleCrudit/brick/filter')
                ->setConfig($brickConfigurator->getConfig($this->getRequest()))
                ->setData([
                    'filters' => $this->buildFilterMap($filterset),
                    'filterset' => $filterset,
                ]);
        }

        return $view;
    }

    public function buildFilterMap(FilterSetInterface $filterset): array
    {
        $ret = [];
        foreach ($filterset->getFilters() as $filter) {
            if ($filter->getRole() !== null && $this->security->isGranted($filter->getRole()) === false) {
                continue;
            }

            $filter->setData($this->filterState->getData($filterset->getId(), $filter->getId()));
            $ret[$filterset->getId() . '_' . $filter->getId()] = $filter;
        }

        return $ret;
    }
}
