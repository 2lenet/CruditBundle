<?php

namespace Lle\CruditBundle\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FilterState
{
    private iterable $filtersets;
    private SessionInterface $session;
    private ?array $filterdata;

    public function __construct(iterable $filtersets, SessionInterface $session)
    {
        $this->filtersets = $filtersets;
        $this->session = $session;
        $this->filterdata = null;
    }

    public function handleRequest(Request $request):void
    {
        $filterdata = $this->session->get('crudit_filters');
        foreach ($this->filtersets as $filterset) {
            $filterId = $filterset->getId();
            if ($request->query->get($filterId.'_reset')) {
                $filterdata[$filterId] = [];
            } else {
                foreach ($filterset->getFilters() as $filterType) {
                    $key = "filter_" . $filterId . '_' . $filterType->getId();

                    $data = $request->query->get($key . '_value');
                    if ($data !== null && $data !== "") {
                        $filterdata[$filterId][$filterType->getId()]['value'] = $data;
                    }

                    $op = $request->query->get($key . '_op');
                    if ($op !== null && $op !== "") {
                        $filterdata[$filterId][$filterType->getId()]['op'] = $op;
                    }

                    foreach ($filterType->getAdditionnalKeys() as $addProps) {
                        $add_data = $request->query->get($key . '_'.$addProps);
                        if ($add_data !== null && $add_data !== "") {
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
}
