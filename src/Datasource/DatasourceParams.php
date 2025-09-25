<?php

namespace Lle\CruditBundle\Datasource;

class DatasourceParams
{
    protected int $count = 100;
    protected int $limit = 30;
    protected int $offset = 0;
    protected array $sorts = [];
    protected array $filters = [];
    protected bool $enableFilters = true;
    protected string $listKey = 'list';

    public function __construct(int $limit = 30, int $offset = 0, array $sorts = [], array $filters = [], string $listKey = 'list')
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sorts = $sorts;
        $this->filters = $filters;
        $this->listKey = $listKey;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getFrom(): int
    {
        return $this->offset + 1;
    }

    public function getTo(): int
    {
        return min($this->offset + $this->limit, $this->count);
    }

    public function hasPrevious(): bool
    {
        return $this->offset > 0;
    }

    public function hasNext(): bool
    {
        return $this->getCurrentPage() < $this->getNbPages();
    }

    public function getNbPages(): int
    {
        if ($this->limit < 1) {
            return 1;
        }
        
        $rest = $this->count % $this->limit ? 1 : 0;

        return intdiv($this->count, $this->limit) + $rest;
    }

    public function getCurrentPage(): int
    {
        return intdiv($this->offset, $this->limit) + 1;
    }

    public function getPages(): array
    {
        $current = $this->getCurrentPage();
        $max = $this->getNbPages();
        $pages = [];
        if ($current > 2) {
            $pages[] = $current - 2;
        }
        if ($current > 1) {
            $pages[] = $current - 1;
        }
        $pages[] = $current;
        if ($current + 1 <= $max) {
            $pages[] = $current + 1;
        }
        if ($current + 2 <= $max) {
            $pages[] = $current + 2;
        }

        return $pages;
    }

    public function isCurrent(int $page): bool
    {
        return (intdiv($this->offset, $this->limit) + 1) == $page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getSorts(): array
    {
        return $this->sorts;
    }

    public function setSorts(array $sorts): self
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function isEnableFilters(): bool
    {
        return $this->enableFilters;
    }

    public function setEnableFilters(bool $enableFilters): void
    {
        $this->enableFilters = $enableFilters;
    }

    public function getListKey(): string
    {
        return $this->listKey;
    }

    public function setListKey(string $listKey): self
    {
        $this->listKey = $listKey;

        return $this;
    }

    public function addFilter(DatasourceFilter $filter): void
    {
        $this->filters[] = $filter;
    }

    public function setNonDefaultValuesOnSubDatasourceParams(DatasourceParams $datasourceParams): self
    {
        $defaultDatasourceParams = new DatasourceParams();
        if ($datasourceParams->getLimit() !== $defaultDatasourceParams->getLimit()) {
            $this->setLimit($datasourceParams->getLimit());
        }

        if ($datasourceParams->getOffset() !== $defaultDatasourceParams->getOffset()) {
            $this->setOffset($datasourceParams->getOffset());
        }

        if ($datasourceParams->getSorts() !== $defaultDatasourceParams->getSorts()) {
            $this->setSorts($datasourceParams->getSorts());
        }

        if ($datasourceParams->getFilters() !== $defaultDatasourceParams->getFilters()) {
            $this->setFilters($datasourceParams->getFilters());
        }

        if ($datasourceParams->getListKey() !== $defaultDatasourceParams->getListKey()) {
            $this->setListKey($datasourceParams->getListKey());
        }

        return $this;
    }
}
