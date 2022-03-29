<?php

namespace Lle\CruditBundle\Datasource;

class DatasourceParams
{
    protected $count = 100;

    protected int $limit;

    protected int $offset;

    protected array $sorts;

    protected array $filters;

    protected bool $enableFilters = true;

    public function __construct(int $limit, int $offset, array $sorts, array $filters = [])
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sorts = $sorts;
        $this->filters = $filters;
    }

    public function setCount(int $count)
    {
        $this->count = $count;
    }

    public function getCount(): int
    {
        return $this->count;
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

    public function isCurrent($page): bool
    {
        return (intdiv($this->offset, $this->limit) + 1) == $page;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return DatasourceParams
     */
    public function setLimit(?int $limit): DatasourceParams
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return DatasourceParams
     */
    public function setOffset(?int $offset): DatasourceParams
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * @param array $sorts
     * @return DatasourceParams
     */
    public function setSorts(array $sorts): DatasourceParams
    {
        $this->sorts = $sorts;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $sorts
     * @return DatasourceParams
     */
    public function setFilters(array $filters): DatasourceParams
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
}
