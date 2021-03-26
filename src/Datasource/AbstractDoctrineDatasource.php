<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Datasource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Lle\CruditBundle\Contracts\DatasourceInterface;
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
        $ressource = $this->entityManager->getReference($this->getClassName(), $id);
        if ($ressource) {
            $this->entityManager->remove($ressource);
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

    public function save(object $ressource): void
    {
        $this->entityManager->persist($ressource);
        $this->entityManager->flush();
    }

    /** @return  class-string<T> $className */
    abstract public function getClassName(): string;

    private function getRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository($this->getClassName());
    }

    public function getType(string $property, object $ressource): string
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($ressource));
        $type = $metadata->getTypeOfField($property);
        if ($type === null) {
            if ($metadata->getAssociationMapping($property)) {
                $type = DoctrineEntityField::class;
            }
        }
        return $type;
    }

    public function getIdentifier(object $ressource): string
    {
        $identifierValue = '';
        $metadata = $this->entityManager->getClassMetadata($this->getClassName());
        foreach ($metadata->getIdentifier() as $identifier) {
            $identifierValue .= $metadata->getIdentifierValues($ressource)[$identifier];
        }
        return $identifierValue;
    }
}
