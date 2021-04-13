<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\QueryAdapterInterface;
use Lle\CruditBundle\Field\DoctrineEntityField;

abstract class AbstractDoctrineDatasource implements DatasourceInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function get($id): ?object
    {
        return $this->getRepository()->find($id);
    }

    public function list(): iterable
    {
        return $this->getRepository()->findAll();
    }

    public function delete($id): bool
    {
        $resource = $this->entityManager->getReference($this->getClassName(), $id);
        if ($resource) {
            $this->entityManager->remove($resource);
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
}
