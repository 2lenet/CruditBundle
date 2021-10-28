<?php

namespace Lle\CruditBundle\Filter;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class FilterState
{
    private iterable $filtersets;
    private SessionInterface $session;
    private ?array $filterdata;
    private Security $security;

    public function __construct(iterable $filtersets, SessionInterface $session, Security $security)
    {
        $this->filtersets = $filtersets;
        $this->session = $session;
        $this->security = $security;
        $this->filterdata = null;
    }

    public function handleRequest(Request $request):void
    {
        $filterdata = $this->session->get('crudit_filters');

        foreach ($this->filtersets as $filterset) {
            $filterId = $filterset->getId();

            if (!isset($filterdata[$filterId])) {
                $filterdata[$filterId] = $this->initDefaultData($filterset);
            }

            if ($request->query->get($filterId.'_reset')) {
                $filterdata[$filterId] = $this->initDefaultData($filterset);
                // we remove cached sort & page
                $this->session->remove("crudit_datasourceparams_" . $filterId);
            } else {

                foreach ($filterset->getFilters() as $filterType) {
                    if ($filterType->getRole() != null && $this->security->isGranted($filterType->getRole()) == false) {
                        unset($filterdata[$filterId][$filterType->getId()]);
                        continue;
                    }
                    $key = "filter_" . $filterId . '_' . $filterType->getId();

                    $data = $request->query->get($key . '_value');
                    if ($data !== null) {
                        $filterdata[$filterId][$filterType->getId()]['value'] = $data;
                    }

                    $op = $request->query->get($key . '_op');
                    if ($op !== null && $op !== "") {
                        $filterdata[$filterId][$filterType->getId()]['op'] = $op;
                    }

                    foreach ($filterType->getAdditionnalKeys() as $addProps) {
                        $add_data = $request->query->get($key . '_'.$addProps);
                        if ($add_data !== null) {
                            $filterdata[$filterId][$filterType->getId()][$addProps] = $add_data;
                        }
                    }
                }
            }
        }
        $this->filterdata = $filterdata;
        $this->session->set('crudit_filters', $filterdata);
    }

    public function getData($set_id, $filter_id): ?array {
        $this->loadData();
        return $this->filterdata[$set_id][$filter_id] ?? null;
    }

    protected function loadData(): void {
        if (!$this->filterdata) {
            $this->filterdata = $this->session->get('crudit_filters');
        }
    }

    public function initDefaultData(FilterSetInterface $filterset): array
    {
        $filterdata = [];
        foreach ($filterset->getFilters() as $filterType) {
            if ($filterType->getRole() != null && $this->security->isGranted($filterType->getRole()) == false) {
                continue;
            }
            $data = $filterType->getDefault();
            if ($data !== null && $data !== "") {
                $filterdata[$filterType->getId()] = $data;
            }
        }
        return $filterdata;
    }


}
