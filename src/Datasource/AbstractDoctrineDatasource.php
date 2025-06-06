<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Exception\BadConfigException;
use Lle\CruditBundle\Field\DoctrineEntityField;
use Lle\CruditBundle\Field\EmailField;
use Lle\CruditBundle\Field\TelephoneField;
use Lle\CruditBundle\Filter\FilterState;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractDoctrineDatasource implements DatasourceInterface
{
    protected EntityManagerInterface $entityManager;
    protected ?FilterSetInterface $filterset;
    protected FilterState $filterState;
    protected array $searchFields = [];

    public function __construct(EntityManagerInterface $entityManager, FilterState $filterState)
    {
        $this->entityManager = $entityManager;
        $this->filterset = null;
        $this->filterState = $filterState;

        $entityClass = $this->getClassName();

        $this->searchFields = array_merge($this->searchFields, static::getInitSearchFields($entityClass));
    }

    public static function getInitSearchFields(string $entityClass): array
    {
        $searchFields = [];

        if (property_exists($entityClass, 'libelle')) {
            $searchFields[] = "libelle";
        }

        if (property_exists($entityClass, 'code')) {
            $searchFields[] = "code";
        }

        if (property_exists($entityClass, 'name')) {
            $searchFields[] = "name";
        }

        if (property_exists($entityClass, 'label')) {
            $searchFields[] = "label";
        }

        if (property_exists($entityClass, 'nom')) {
            $searchFields[] = "nom";
        }

        return $searchFields;
    }

    abstract public function getClassName(): string;

    public function get($id): ?object
    {
        return $this->getRepository()->find($id);
    }

    private function getRepository(): ObjectRepository
    {
        /** @var class-string $className */
        $className = $this->getClassName();

        return $this->entityManager->getRepository($className);
    }

    public function list(?DatasourceParams $requestParams): iterable
    {
        $qb = $this->buildQueryBuilder($requestParams);
        $qb->distinct();
        $this->applyFilters($qb, $requestParams);
        $this->applyLimit($qb, $requestParams);
        $this->applyOrders($qb, $requestParams);
        $this->applyOffset($qb, $requestParams);

        return $qb->getQuery()->execute();
    }

    public function buildQueryBuilder(?DatasourceParams $requestParams): QueryBuilder
    {
        /** @var EntityRepository $repository */
        $repository = $this->getRepository();

        $qb = $repository->createQueryBuilder("root");

        /** @var class-string $className */
        $className = $this->getClassName();

        $metadata = $this->entityManager->getClassMetadata($className);
        if ($requestParams) {
            $i = 0;
            foreach ($requestParams->getFilters() as $filter) {
                $alias = $filter->getAlias() ?? "root";
                $field = $alias . "." . $filter->getField();

                if (
                    $metadata->hasAssociation($filter->getField())
                    && $metadata->getAssociationMapping($filter->getField())["type"] === ClassMetadataInfo::MANY_TO_MANY
                ) {
                    // it's a ManyToMany, we need to join.
                    $joinAlias = $alias . "_" . $filter->getField();
                    $qb->join($alias . "." . $filter->getField(), $joinAlias);
                    $field = $joinAlias;
                }

                $parameterName = "filter_" . $filter->getAlias() . "_" . $i;
                if ($filter->getOperator() === "IN" || $filter->getOperator() === "NOT IN") {
                    $qb->andWhere($field . " " . $filter->getOperator() . "(:$parameterName)");
                } else {
                    $qb->andWhere($field . " " . $filter->getOperator() . " :$parameterName");
                }

                $qb->setParameter($parameterName, $filter->getValue());

                $i++;
            }
        }

        return $qb;
    }

    protected function applyFilters(QueryBuilder $qb, ?DatasourceParams $requestParams): void
    {
        if ($this->filterset && $requestParams?->isEnableFilters()) {
            foreach ($this->filterset->getFilters() as $filter) {
                $filter->setData($this->filterState->getData($this->filterset->getId(), $filter->getId()));
                $filter->apply($qb);
            }
        }
    }

    protected function addOrderBy(QueryBuilder $qb, string $column, ?string $order): void
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
                $qb->leftJoin($join . "." . $field, $alias);
            }

            $join = $alias;
            $field = array_shift($fields);
        }

        /** @var class-string $className */
        $className = $this->getClassName();

        $metadata = $this->entityManager->getClassMetadata($className);

        if (
            $metadata->hasAssociation($field)
            && ($metadata->getAssociationMapping($field)["type"] & ClassMetadataInfo::TO_MANY)
        ) {
            $alias = $alias . "_" . $field;
            $qb->leftJoin("$join.$field", $alias);
            $qb->addGroupBy($join);
            $qb->addOrderBy("COUNT($alias)", $order);
        } else {
            $qb->addOrderBy("$join.$field", $order);
        }
    }

    /**
     * @return string[]
     * @throws BadConfigException
     */
    protected function getAutocompleteSearchFields(): array
    {
        if (count($this->searchFields) == 0) {
            throw new BadConfigException('No searchFields found');
        }

        return $this->searchFields;
    }

    public function autocompleteQuery(
        string $queryTerm,
        array $sorts,
        ?DatasourceParams $requestParams = null,
    ): iterable {
        $qb = $this->buildQueryBuilder($requestParams);

        $qb = $this->initializeAutocompleteQueryBuilder($qb, $queryTerm);

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

    public function autocompleteCountQuery(string $queryTerm): int
    {
        $qb = $this->buildQueryBuilder(null);
        $qb->select('count(DISTINCT(root.id))');

        $qb = $this->initializeAutocompleteQueryBuilder($qb, $queryTerm);

        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function initializeAutocompleteQueryBuilder(QueryBuilder $qb, string $queryTerm): QueryBuilder
    {
        $orStatements = $qb->expr()->orX();
        foreach ($this->getAutoCompleteSearchFields() as $field) {
            $orStatements->add(
                $qb->expr()->like('root.' . $field, $qb->expr()->literal('%' . $queryTerm . '%'))
            );

            // allow null if the user didn't type anything
            if (!$queryTerm) {
                $orStatements->add(
                    $qb->expr()->isNull('root.' . $field)
                );
            }
        }

        $qb->andWhere($orStatements);

        return $qb;
    }

    public function count(?DatasourceParams $requestParams): int
    {
        $qb = $this->buildQueryBuilder($requestParams);
        $qb->select('count(DISTINCT(root.id))');

        $this->applyFilters($qb, $requestParams);

        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function delete($id): bool
    {
        /** @var class-string $className */
        $className = $this->getClassName();

        $resource = $this->entityManager->find($className, $id);
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

    public function newInstance(): object
    {
        $class = $this->getClassName();

        return new $class();
    }

    public function patch($id, array $data): ?object
    {
        return $this->newInstance();
    }

    public function setSearchFields(array $fields): self
    {
        $this->searchFields = $fields;

        return $this;
    }

    public function save(object $resource): void
    {
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
    }

    public function getType(string $property, object $resource): string
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($resource));

        if (in_array($property, ['email', 'mail'])) {
            return EmailField::class;
        }

        if (in_array($property, ['tel', 'telephone', 'mobile', 'portable', 'telephoneMobile'])) {
            return TelephoneField::class;
        }

        $type = $metadata->getTypeOfField($property);
        if ($type === null) {
            if (isset($metadata->getAssociationMappings()[$property])) {
                $type = DoctrineEntityField::class;
            } else {
                $type = "string";
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
        /** @var class-string $classname */
        $classname = $this->getClassName();

        $metadata = $this->entityManager->getClassMetadata($classname);
        foreach ($metadata->getAssociationMappings() as $associationMapping) {
            if ($associationMapping['targetEntity'] === $className) {
                return $associationMapping['fieldName'];
            }
        }

        return null;
    }

    public function isEntity(string $field): bool
    {
        /** @var class-string $className */
        $className = $this->getClassName();

        $associations = $this->entityManager->getClassMetadata($className)->associationMappings;

        return array_key_exists($field, $associations);
    }

    public function createQuery(string $alias): QueryAdapterInterface
    {
        /** @var EntityRepository $repository */
        $repository = $this->getRepository();

        return new DoctrineQueryAdapter($repository->createQueryBuilder($alias));
    }

    public function getFilterset(): ?FilterSetInterface
    {
        return $this->filterset;
    }

    public function fillFromData(object $item, array $data, array $params = []): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->getPropertyAccessor();

        foreach ($data as $field => $value) {
            if ($field === "id") {
                continue;
            }

            if ($value === "") {
                $value = null;
            } else {
                /** @var class-string $className */
                $className = $this->getClassName();

                if ($this->isEntity($field)) {
                    $associations = $this->entityManager->getClassMetadata(
                        $className
                    )->associationMappings;

                    $value = $this->entityManager->getReference($associations[$field]["targetEntity"], $value);
                } else {
                    $fields = $this->entityManager->getClassMetadata($className);
                    $type = $fields->fieldMappings[$field]['type'];

                    if (
                        $params
                        && array_key_exists($field, $params['options'])
                        && $params['options'][$field]['currency'] === 'currency_int'
                    ) {
                        $type = 'currency_int';
                    }

                    switch ($type) {
                        case "date":
                        case "datetime":
                            $value = new \DateTime($value);
                            break;
                        case "integer":
                        case "smallint":
                            $value = (int)$value;
                            break;
                        case "currency_int":
                            $value = (int)($value * 100);
                            break;
                        case "float":
                            $value = (float)$value;
                            break;
                        case "string":
                        case "text":
                        case "decimal":
                        default:
                            // do nothing
                    }
                }
            }
            $propertyAccessor->setValue($item, $field, $value);
        }
    }

    public function editData(string $id, array $data): bool
    {
        $item = $this->get($id);

        if ($item) {
            $this->fillFromData($item, $data);
            $this->save($item);

            return true;
        }

        return false;
    }

    public function getTotals(?DatasourceParams $requestParams, array $fields): iterable
    {
        $qb = $this->buildQueryBuilder($requestParams);

        foreach ($fields as $field => $data) {
            $expression = $data['type'] . '(root.' . $field . ')';
            if ($data['type'] === CrudConfigInterface::EXPRESSION) {
                $expression = $data['expression'];
            }
            if ($field === array_key_first($fields)) {
                $qb->select($expression);
            } else {
                $qb->addSelect($expression);
            }
        }

        $this->applyFilters($qb, $requestParams);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function applyLimit(QueryBuilder $qb, ?DatasourceParams $requestParams): void
    {
        if ($requestParams && $requestParams->getLimit()) {
            $qb->setMaxResults($requestParams->getLimit());
        }
    }

    public function applyOrders(QueryBuilder $qb, ?DatasourceParams $requestParams): void
    {
        if ($requestParams) {
            foreach ($requestParams->getSorts() as $sort) {
                $this->addOrderBy($qb, $sort[0], $sort[1]);
            }
        }
    }

    public function applyOffset(QueryBuilder $qb, ?DatasourceParams $requestParams): void
    {
        if ($requestParams && $requestParams->getOffset()) {
            $qb->setFirstResult($requestParams->getOffset());
        }
    }

    public function setFilterState(array $filterState): self
    {
        $this->filterState->setFilterdata($filterState);

        return $this;
    }

    public function getTags(object $resource): iterable
    {
        return ['tags' => [], 'currentTags' => []];
    }
}
