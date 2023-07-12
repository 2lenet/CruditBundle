<?php

namespace Lle\CruditBundle\Filter;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class FilterState
{
    private iterable $filtersets;
    private ?array $filterdata;
    private Security $security;
    private ?Request $request;

    public function __construct(iterable $filtersets, Security $security, RequestStack $requestStack)
    {
        $this->filtersets = $filtersets;
        $this->security = $security;
        $this->filterdata = null;
        $this->request = $requestStack->getMainRequest();
    }

    public function handleRequest(Request $request): void
    {
        $session = $request->getSession();
        $filterdata = $session->get('crudit_filters');

        foreach ($this->filtersets as $filterset) {
            $filterId = $filterset->getId();

            $sessionKey = "crudit_datasourceparams_" . $filterId;

            if (!isset($filterdata[$filterId])) {
                $filterdata[$filterId] = $this->initDefaultData($filterset);
            }

            if ($request->query->get($filterId . '_reset')) {
                $filterdata[$filterId] = $this->initDefaultData($filterset);
                // we remove cached sort & page
                $session->remove($sessionKey);
            } else {
                if ($request->query->get($filterId . '_filter')) {
                    //go back to the first page
                    $params = $session->get($sessionKey);
                    if (isset($params) && isset($params["offset"])) {
                        $params["offset"] = 0;
                        $session->set($sessionKey, $params);
                    }
                }

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
                        $addData = $request->query->get($key . '_' . $addProps);
                        if ($addData !== null) {
                            $filterdata[$filterId][$filterType->getId()][$addProps] = $addData;
                        }
                    }
                }
            }
        }
        $this->filterdata = $filterdata;
        $session->set('crudit_filters', $filterdata);
    }

    public function getData(string $filterSetId, string $filterId): ?array
    {
        $this->loadData();

        return $this->filterdata[$filterSetId][$filterId] ?? null;
    }

    protected function loadData(): void
    {
        if (!$this->filterdata && $this->request) {
            $this->filterdata = $this->request->getSession()->get('crudit_filters');
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
