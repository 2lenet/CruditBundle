<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\GroupedTotalsInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Exception\BadConfigException;
use Lle\CruditBundle\Field\DoctrineEntityField;
use Lle\CruditBundle\Field\EmailField;
use Lle\CruditBundle\Field\TelephoneField;
use Lle\CruditBundle\Filter\FilterState;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractDoctrineDatasource implements DatasourceInterface, GroupedTotalsInterface
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
                    && $metadata->getAssociationMapping($filter->getField())["type"] === ClassMetadata::MANY_TO_MANY
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
        if (str_contains($column, '(')) {
            $qb->addOrderBy($column, $order);

            return;
        }

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
            && ($metadata->getAssociationMapping($field)["type"] & ClassMetadata::TO_MANY)
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

    public function save(object $resource): bool
    {
        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        return true;
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

                    /** @var class-string $entityName */
                    $entityName = $associations[$field]["targetEntity"];

                    $value = $this->entityManager->getReference($entityName, $value);
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
            if (!$this->save($item)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getTotals(?DatasourceParams $requestParams, array $fields): iterable
    {
        $matchingIds = $this->getFilteredIds($requestParams);

        if (empty($matchingIds)) {
            return [];
        }

        /** @var EntityRepository $repository */
        $repository = $this->getRepository();
        $qb = $repository->createQueryBuilder('root');
        $qb->andWhere('root.id IN (:_matching_ids)');
        $qb->setParameter('_matching_ids', $matchingIds);

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

        return $qb->getQuery()->getOneOrNullResult();
    }

    /** @return int[] */
    protected function getFilteredIds(?DatasourceParams $requestParams): array
    {
        $filterQb = $this->buildQueryBuilder($requestParams);
        $this->applyFilters($filterQb, $requestParams);
        $filterQb->select('root.id');

        $rows = $filterQb->getQuery()->getScalarResult();

        return array_unique(array_column($rows, 'id'));
    }

    public function getGroupedTotals(
        ?DatasourceParams $requestParams,
        array $fields,
        string $ruptFieldPath,
        ?string $ruptDateFormat = null,
    ): array {
        $matchingIds = $this->getFilteredIds($requestParams);

        if (empty($matchingIds)) {
            return [];
        }

        /** @var EntityRepository $repository */
        $repository = $this->getRepository();
        $qb = $repository->createQueryBuilder('root');
        $qb->andWhere('root.id IN(:_matching_ids)');
        $qb->setParameter('_matching_ids', $matchingIds);

        [$selectExpr, $groupByExpr] = $this->buildRuptGroupExpression($qb, $ruptFieldPath, $ruptDateFormat);

        $qb->select($selectExpr . ' AS _rupt_key');

        $i = 1;
        foreach ($fields as $field => $data) {
            $expression = $data['type'] . '(root.' . $field . ')';
            if ($data['type'] === CrudConfigInterface::EXPRESSION) {
                $expression = $data['expression'];
            }

            $qb->addSelect($expression . ' AS _agg_' . $i);
            $i++;
        }

        $qb->groupBy($groupByExpr);

        $rows = $qb->getQuery()->getScalarResult();

        $result = [];
        $fieldCount = count($fields);
        foreach ($rows as $row) {
            $groupKey = (string) $row['_rupt_key'];
            $totals = [];
            for ($j = 1; $j <= $fieldCount; $j++) {
                $totals[$j] = $row['_agg_' . $j];
            }

            $result[$groupKey] = $totals;
        }

        return $result;
    }

    /** @return array{0: string, 1: string} */
    protected function buildRuptGroupExpression(QueryBuilder $qb, string $fieldPath, ?string $dateFormat): array
    {
        $parts = explode('.', $fieldPath);
        $field = array_pop($parts);

        if ($field === null) {
            return ["root.$fieldPath", "root.$fieldPath"];
        }

        $rootAlias = $qb->getRootAliases()[0];
        $joinAlias = $rootAlias;

        foreach ($parts as $segment) {
            $newAlias = '_rupt_' . $segment;
            if (!in_array($newAlias, $qb->getAllAliases())) {
                $qb->leftJoin($joinAlias . '.' . $segment, $newAlias);
            }

            $joinAlias = $newAlias;
        }

        if ($dateFormat !== null) {
            $sqlFormat = RuptDateFormat::toSql($dateFormat);
            $expr = "DATE_FORMAT($joinAlias.$field, '$sqlFormat')";

            return [$expr, '_rupt_key'];
        }

        /** @var class-string $className */
        $className = $this->getClassName();
        $metadata = $this->entityManager->getClassMetadata($className);

        if ($joinAlias === $rootAlias && $metadata->hasAssociation($field)) {
            return ["IDENTITY($joinAlias.$field)", "$joinAlias.$field"];
        }

        return ["$joinAlias.$field", "$joinAlias.$field"];
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
