<?php


namespace Lle\CruditBundle\Datasource;


class DatasourceParams
{

    protected $count = 100;

    public function __construct(int $limit, int $offset, array $sorts, array $filters)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sorts = $sorts;
        $this->filters = $filters;
    }

    function setCount($count) {
        $this->count = $count;
    }
    
    function getCount() {
        return $this->count;
    }

    function getFrom(): int {
        return $this->offset;
    }

    function getTo(): int {
        return $this->offset+ $this->limit-1;
    }

    function hasPrevious(): bool {
        return $this->offset >0;
    }

    function hasNext(): bool {
        return true;
    }

    function getNbPages(): int {
        $rest = $this->count % $this->limit? 1:0;
        return intdiv($this->count , $this->limit)+$rest;
    }

    function getCurrentPage(): int {
       return intdiv($this->offset, $this->limit)+1;
    }

    function getPages(): array {
        $current = $this->getCurrentPage();
        $max = $this->getNbPages();
        $pages = [1];
        if ($current>2) {
            $pages[] = $current-1;
            $pages[] = $current;
        }
        if ($current+1 < $max) {
            $pages[] = $current+1;
        }
        if ($current < $max) {
            $pages[] = $max;
        }
        return $pages;
    }

    function isCurrent($page): bool {
        return (intdiv($this->offset, $this->limit )+1) == $page;
    }
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }


    /**
     * @param array $filters
     * @return DatasourceParams
     */
    public function setFilters(array $filters): DatasourceParams
    {
        $this->filters = $filters;
        return $this;
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
     * @param int $offset
     * @return DatasourceParams
     */
    public function setOffset(?int $offset): DatasourceParams
    {
        $this->offset = $offset;
        return $this;
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

}
