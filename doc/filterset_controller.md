# How to use a FilterSet in your own Controller

It is easily possible to apply the result of you filters within your own Controller.

_If you want to learn how to create filters and your FilterSet, please read the chapter about filters._

**1. Inject your FilterSet and the FilterState in your Controller**

    private SiteCollecteFilterset $filterset;
    private FilterState $filterState;

    public function __construct(
        SiteCollecteFilterset $filterset,
        FilterState $filterState
    ) {
        $this->filterset = $filterset;
        $this->filterState = $filterState;
    }

**2. Then, in your method, create your initial queryBuilder**

In the method:

      $qb = $this->siteCollecteRepository->findSiteCollecteWithGeoQb();

In the repository:

    public function findSiteCollecteWithGeoQb()
    {
        return $this->createQueryBuilder('root')
            ->andWhere('root.centreLat IS NOT NULL AND root.centreLng IS NOT NULL OR root.polygone IS NOT NULL')
            ;
    }

**3. In your method you can now retrieve your filters, apply your query to them and run the whole thing**

    $qb = $this->siteCollecteRepository->findSiteCollecteWithGeoQb();

    foreach ($this->filterset->getFilters() as $filter) {
        $filter->setData($this->filterState->getData($this->filterset->getId(), $filter->getId()));
        $filter->apply($qb);
    }

    $sitesCollectes = $qb->getQuery()->execute();

Tadaaaa, the sitesCollectes take into account both your query in the repository and what you have passed in your
filters.
