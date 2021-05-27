<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Field\DoctrineEntityField;
use Lle\CruditBundle\Filter\FilterState;

abstract class AbstractDoctrineDatasource implements DatasourceInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    protected ?FilterSetInterface $filterset;
    private FilterState $filterState;
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
            $qb->addOrderBy('root.' . $sort[0], $sort[1]);
        }

        if ($requestParams->getOffset()) {
            $qb->setFirstResult($requestParams->getOffset());
        }

        return $qb->getQuery()->execute();
    }

    public function query(string $queryColumn, $queryTerm, array $sorts): iterable
    {
        $qb = $this->buildQueryBuilder(null);
        $orStatements = $qb->expr()->orX();
        foreach ($this->searchFields as $field) {
            $orStatements->add(
                $qb->expr()->like('root.' . $field, $qb->expr()->literal($queryTerm.'%'))
            );
        }
        $qb->andWhere($orStatements);

        foreach ($sorts as $sort) {
            $qb->addOrderBy('root.' . $sort[0], $sort[1]);
        }

        return $qb->getQuery()->execute();
    }

    public function count(?DataSourceParams $requestParams): int
    {
        $qb = $this->buildQueryBuilder($requestParams);
        $qb->select('count(root.id)');
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
        return $this->entityManager
            ->getClassMetadata($this->getClassName())
            ->newInstance();
    }

    public function save(object $resource): void
    {
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
    }

    /** @return  class-string<T> $className */
    abstract public function getClassName(): string;

    private function getRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository($this->getClassName());
    }

    public function getType(string $property, object $resource): string
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($resource));
        $type = $metadata->getTypeOfField($property);
        if ($type === null) {
            if ($metadata->getAssociationMapping($property)) {
                $type = DoctrineEntityField::class;
            }
        }
        return $type;
    }

    //todometadata
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
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buildQueryBuilder(?DataSourceParams $requestParams): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->getRepository()->createQueryBuilder('root');

        if ($requestParams) {
            foreach ($requestParams->getFilters() as $filter) {
                $alias = $filter->getAlias() ?? 'root';
                $qb->andWhere($alias . '.' . $filter->getField() . $filter->getOperator() . $filter->getValue());
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

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb)
    {
        foreach ($this->filterset->getFilters() as $filter) {
            $filter->setData($this->filterState->getData($this->filterset->getId(), $filter->getId()));
            $filter->apply($qb);
        }
    }

}
