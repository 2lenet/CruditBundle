<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Lib;

use Doctrine\ORM\QueryBuilder;

class Pager
{

    public const NBPERPAGE = 20;
    public const NBBTN = 5;
    public const ALL = 0;

    /** @var QueryBuilder  */
    private $queryBuilder;

    /** @var int  */
    private $page;

    /** @var ?int  */
    private $nbPage;

    /** @var ?int  */
    private $nbElements;

    /** @var int  */
    private $nbPerPage;

    /** @var bool  */
    private $unlimited;

    public function __construct(
        QueryBuilder $queryBuilder,
        int $page = 1,
        int $nbPerPage = null,
        bool $unlimited = false
    ) {
        $this->nbPerPage = ($nbPerPage) ? $nbPerPage : self::NBPERPAGE;
        $this->queryBuilder = clone $queryBuilder;
        $this->page = $page;
        if ($this->page != self::ALL) {
            $this->queryBuilder->setFirstResult(($this->page - 1) * $this->nbPerPage);
            $this->queryBuilder->setMaxResults($this->nbPerPage);
        }
        $this->unlimited = $unlimited;
        if ($unlimited) {
            $this->nbElements = null;
            $this->nbPage = null;
        } else {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $this->nbElements = (int) $queryBuilder
                ->select('count(' . $rootAlias . ') as nb')
                ->getQuery()
                ->getSingleResult()['nb'];
            $this->nbPage = (int) ceil($this->nbElements / $this->nbPerPage);
        }
    }

    public function getNbPerPage(): int
    {
        return $this->nbPerPage;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getEntities(): iterable
    {
        return $this->queryBuilder->getQuery()->getResult();
    }

    public function getNbPage(): ?int
    {
        return $this->nbPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function isUnlimited(): bool
    {
        return $this->unlimited;
    }

    public function getNbElements(): ?int
    {
        return $this->nbElements;
    }
    public function isPre(): bool
    {
        return ($this->page > 1);
    }

    public function isNext(): bool
    {
        if ($this->nbPage === null) {
            return true; //unlimited
        }
        return ($this->page < $this->nbPage);
    }

    public function isPPre(): bool
    {
        return ($this->page - 1 > 1);
    }

    public function isNNext(): bool
    {
        return ($this->page + 1 < $this->nbPage);
    }

    public function isCur(int $i): bool
    {
        return ($i == $this->page);
    }

    public function isShow(int $i): bool
    {
        $nbBtn = self::NBBTN;
        if ($nbBtn % 2 == 0) {
            $nbBtn++;
        }
        $lim = ($nbBtn - 1) / 2;
        $min = $this->page - $lim;
        $max = $this->page + $lim;
        return ($i >= $min && $i <= $max);
    }

    public function buttons(): array
    {
        $nbBtn = self::NBBTN;
        if ($nbBtn % 2 == 0) {
            $nbBtn++;
        }
        $lim = ($nbBtn - 1) / 2;
        $min = $this->page - $lim;
        if ($min <= 0) {
            $min = 1;
        }
        $max = $this->page + $lim;
        $l = $min;
        $return = [];
        while ($l <= $max) {
            $return[] = $l;
            $l++;
        }
        return $return;
    }

    public function getFirstPage(): int
    {
        return 1;
    }

    public function getLastPage(): ?int
    {
        return $this->nbPage;
    }

    public function getInfo(): array
    {
        return [
            'firstPage' => $this->getFirstPage(),
            'lastPage' => $this->getLastPage(),
            'nbElements' => $this->getNbElements(),
            'nbPage' => $this->getNbPage(),
            'nbPerPage' => $this->getNbPerPage(),
            'page' => $this->getPage(),
            'isNext' => $this->isNext(),
            'isNNext' => $this->isNNext(),
            'isPPre' => $this->isPPre(),
            'isPre' => $this->isPre(),
            'buttons' => $this->buttons()
        ];
    }
}
