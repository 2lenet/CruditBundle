<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\RessourceView;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class RessourceResolver
{

    /** @var FieldResolver */
    private $fieldResolver;

    /** @var EntityManagerInterface  */
    private $entityManager;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(
        EntityManagerInterface $entityManager,
        FieldResolver $fieldResolver,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->fieldResolver = $fieldResolver;
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $item
     * @param Field[] $fields
     */
    public function resolve(object $item, array $fields): RessourceView
    {
        return new RessourceView(
            $this->getIdentifier($item),
            $item,
            $this->getFieldViews($fields, $item)
        );
    }

    /** @return FieldView[] */
    private function getFieldViews(array $fields, object $item): array
    {
        $fieldViews = [];
        foreach ($fields as $field) {
            $fieldViews[] = $this->fieldResolver->resolve($field, $item);
        }
        return $fieldViews;
    }

    /** @return int|string */
    private function getIdentifier(object $item)
    {
        $identifierField = $this
            ->entityManager
            ->getClassMetadata(get_class($item))
            ->getSingleIdentifierColumnName();
        return $this->propertyAccessor->getValue($item, $identifierField);
    }
}
