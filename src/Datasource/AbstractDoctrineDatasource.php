<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Field\DoctrineEntityField;
use Lle\CruditBundle\Field\EmailField;
use Lle\CruditBundle\Field\TelephoneField;
use Lle\CruditBundle\Filter\FilterState;

abstract class AbstractDoctrineDatasource implements DatasourceInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    protected ?FilterSetInterface $filterset;
    protected FilterState $filterState;
    protected array $searchFields = [];

    public function __construct(EntityManagerInterface $entityManager, FilterState $filterState)
    {
        $this->entityManager = $entityManager;
        $this->filterset = null;
        $this->filterState = $filterState;

        $entityClass = $this->getClassName();

        if (property_exists( $entityClass , 'libelle')) {
            $this->searchFields[] = "libelle";
        }

        if (property_exists( $entityClass , 'code')) {
            $this->searchFields[] = "code";
        }

        if (property_exists( $entityClass , 'name')) {
            $this->searchFields[] = "name";
        }

        if (property_exists( $entityClass , 'label')) {
            $this->searchFields[] = "label";
        }

        if (property_exists( $entityClass , 'nom')) {
            $this->searchFields[] = "nom";
        }
    }

    public function get($id): ?object
    {
        return $this->getRepository()->find($id);
    }

    public function list(?DatasourceParams $requestParams): iterable
    {
        $qb = $this->buildQueryBuilder($requestParams);

        if ($this->filterset) {
            $this->applyFilters($qb);
        }

        if ($requestParams->getLimit()) {
            $qb->setMaxResults($requestParams->getLimit());
        }
        foreach ($requestParams->getSorts() as $sort) {
            $this->addOrderBy($qb, $sort[0], $sort[1]);
        }

        if ($requestParams->getOffset()) {
            $qb->setFirstResult($requestParams->getOffset());
        }
        return $qb->getQuery()->execute();
    }

    public function query(string $queryColumn, $queryTerm, array $sorts, $requestParams=null): iterable
    {
        $qb = $this->buildQueryBuilder($requestParams);
        $orStatements = $qb->expr()->orX();
        foreach ($this->searchFields as $field) {
            $orStatements->add(
                $qb->expr()->like('root.' . $field, $qb->expr()->literal($queryTerm.'%'))
            );
        }
        $qb->andWhere($orStatements);

        foreach ($sorts as $sort) {
            $this->addOrderBy($qb, $sort[0], $sort[1]);
        }
        if ($requestParams) {
            if ($requestParams->getLimit()) {
                $qb->setMaxResults($requestParams->getLimit());
            }
            if ($requestParams->getOffset()) {
                $qb->setFirstResult($requestParams->getOffset());
            }
        }
        return $qb->getQuery()->execute();
    }

    public function count_query(string $queryColumn, $queryTerm): int
    {
        $qb = $this->buildQueryBuilder(null);
        $qb->select('count( DISTINCT root.id)');
        $orStatements = $qb->expr()->orX();
        foreach ($this->searchFields as $field) {
            $orStatements->add(
                $qb->expr()->like('root.' . $field, $qb->expr()->literal($queryTerm.'%'))
            );
        }
        $qb->andWhere($orStatements);
        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function count(?DataSourceParams $requestParams): int
    {
        $qb = $this->buildQueryBuilder($requestParams);
        $qb->select('count( DISTINCT root.id)');

        if ($this->filterset) {
            $this->applyFilters($qb);
        }

        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function delete($id): bool
    {
        $resource = $this->entityManager->getReference($this->getClassName(), $id);
        if ($resource) {
            $this->entityManager->remove($resource);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function put($id, array $data): ?object
    {
        return $this->newInstance();
    }

    public function patch($id, array $data): ?object
    {
        return $this->newInstance();
    }

    public function setSearchFields(array $fields) {
        $this->searchFields = $fields;
    }

    public function newInstance(): object
    {
        $class = $this->getClassName();

        return new $class();
    }

    public function save(object $resource): void
    {
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
    }

    abstract public function getClassName(): string;

    private function getRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository($this->getClassName());
    }

    public function getType(string $property, object $resource): string
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($resource));

        if (in_array($property, ['email','mail'])) {
            return EmailField::class;
        }

        if (in_array($property, ['tel','telephone','mobile','portable','telephoneMobile'])) {
            return TelephoneField::class;
        }

        $type = $metadata->getTypeOfField($property);
        if ($type === null) {
            if ($metadata->getAssociationMapping($property)) {
                $type = DoctrineEntityField::class;
            }
        }
        return $type;
    }

    public function getIdentifier(object $resource): string
    {
        $identifierValue = '';
        $metadata = $this->entityManager->getClassMetadata(get_class($resource));
        foreach ($metadata->getIdentifier() as $identifier) {
            $identifierValue .= $metadata->getIdentifierValues($resource)[$identifier];
        }
        return $identifierValue;
    }

    public function getAssociationFieldName(string $className): ?string
    {
        $metadata = $this->entityManager->getClassMetadata($this->getClassName());
        foreach ($metadata->getAssociationMappings() as $associationMapping) {
            if ($associationMapping['targetEntity'] === $className) {
                return $associationMapping['fieldName'];
            }
        }
        return null;
    }

    public function createQuery(string $alias): QueryAdapterInterface
    {
        return new DoctrineQueryAdapter($this->getRepository()->createQueryBuilder($alias));
    }

    /**
     * @param DataSourceParams|null $requestParams
     * @return QueryBuilder
     */
    public function buildQueryBuilder(?DataSourceParams $requestParams): QueryBuilder
    {
        $qb = $this->getRepository()->createQueryBuilder("root")->distinct();

        if ($requestParams) {
            foreach ($requestParams->getFilters() as $filter) {
                $alias = $filter->getAlias() ?? "root";
                $qb->andWhere($alias . "." . $filter->getField() . $filter->getOperator() . $filter->getValue());
            }
        }

        return $qb;
    }

    /**
     * @return FilterSetInterface|null
     */
    public function getFilterset(): ?FilterSetInterface
    {
        return $this->filterset;
    }

    protected function applyFilters(QueryBuilder $qb)
    {
        foreach ($this->filterset->getFilters() as $filter) {
            $filter->setData($this->filterState->getData($this->filterset->getId(), $filter->getId()));
            $filter->apply($qb);
        }
    }

    protected function addOrderBy(QueryBuilder $qb, $column, $order)
    {
        // parts (e.g. : user.post.title => [user, post, title]
        $fields = explode(".", $column);

        // join alias
        $alias = null;

        // column to join (i.e. root.user, user.post, etc.)
        $join = $qb->getRootAliases()[0];

        $field = array_shift($fields);

        // while we aren't at the last part
        while (!empty($fields)) {
            $alias = $alias ? $alias . "_" . $field : $field;

            if (!in_array($alias, $qb->getAllAliases())) {
                $qb->join($join . "." . $field, $alias);
            }

            $join = $alias;
            $field = array_shift($fields);
        }

        $qb->addOrderBy($join . "." . $field, $order);
    }
}
