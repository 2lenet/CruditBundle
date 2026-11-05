<?php

namespace Lle\CruditBundle\Filter;

use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FilterState
{
    /** Longest value kept, in characters, and longest list of values. */
    private const MAX_LENGTH = 1000;
    private const MAX_ITEMS = 500;

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
                    if (
                        $filterType->getRole() !== null &&
                        $this->security->isGranted($filterType->getRole()) === false
                    ) {
                        unset($filterdata[$filterId][$filterType->getId()]);
                        continue;
                    }
                    $key = "filter_" . $filterId . '_' . $filterType->getId();
                    $filterdata[$filterId] = $this->applyFilterRequest($request, $filterdata[$filterId], $filterType, $key);
                }
            }
        }
        $this->filterdata = $filterdata;
        $session->set('crudit_filters', $filterdata);
    }

    /**
     * What the form of one filter sends: `<key>_op`, `<key>_value` (a string, or a list for the boxes of a choice) and
     * the additional keys (`to`, `items`…).
     *
     * - A filter that sends its `_op` is in the form: without `_value`, it is emptied (a list with no box checked sends
     *   nothing).
     * - The operator must be one of those of the type (`getOperators()`, so the ones a project adds to its own filter
     *   type are accepted): with another one, nothing of that filter is kept and it keeps what it had.
     * - A list is joined with commas, the format stored since the 1.x; a value of another shape (an array of arrays) is
     *   ignored, and the size of a value and of a list is limited.
     *
     * @param array<string, mixed> $set the data of the filter set
     * @return array<string, mixed>
     */
    private function applyFilterRequest(Request $request, array $set, FilterTypeInterface $filterType, string $key): array
    {
        $query = $request->query->all();
        $id = $filterType->getId();
        $operators = array_keys($filterType->getOperators());

        $op = $this->scalar($query[$key . '_op'] ?? null);
        $hasOp = array_key_exists($key . '_op', $query);
        if ($hasOp && $op !== null && $op !== '' && !empty($operators) && !in_array($op, $operators, true)) {
            // A condition the filter does not have: nothing of what this filter sent is kept.
            return $set;
        }
        if ($op !== null && $op !== '') {
            $set[$id]['op'] = $op;
        }

        $keys = array_merge(['value'], method_exists($filterType, 'getAdditionnalKeys') ? $filterType->getAdditionnalKeys() : []);
        foreach ($keys as $name) {
            $name = (string) $name;
            if (array_key_exists($key . '_' . $name, $query)) {
                $value = $this->scalar($query[$key . '_' . $name]);
                if ($value !== null) {
                    $set[$id][$name] = $value;
                }
            } elseif ($hasOp && $name !== 'items') {
                // In the form, with nothing sent: emptied.
                $set[$id][$name] = '';
            }
        }
        // The labels of the chosen items (`items`, written by the autocomplete of the 1.x) no longer match a value that
        // changed without them: they are rebuilt from the value.
        if (array_key_exists($key . '_value', $query) && !array_key_exists($key . '_items', $query)) {
            unset($set[$id]['items']);
        } elseif ($hasOp && !array_key_exists($key . '_value', $query)) {
            unset($set[$id]['items']);
        }

        return $set;
    }

    /**
     * A string, or a list of strings joined with commas; null for anything else (the key is then left alone).
     *
     * @param mixed $value
     */
    private function scalar(mixed $value): ?string
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (!is_scalar($item) || is_bool($item)) {
                    return null;
                }
                $items[] = mb_substr((string) $item, 0, self::MAX_LENGTH);
                if (count($items) > self::MAX_ITEMS) {
                    return null;
                }
            }

            return implode(',', $items);
        }
        if (!is_scalar($value) || is_bool($value)) {
            return null;
        }

        return mb_substr((string) $value, 0, self::MAX_LENGTH);
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
            if ($filterType->getRole() !== null && $this->security->isGranted($filterType->getRole()) === false) {
                continue;
            }
            $data = $filterType->getDefault();
            if ($data !== null && $data !== "") {
                $filterdata[$filterType->getId()] = $data;
            }
        }

        return $filterdata;
    }

    /**
     * @param array|null $filterdata
     */
    public function setFilterdata(?array $filterdata): void
    {
        $this->filterdata = $filterdata;
    }
}
