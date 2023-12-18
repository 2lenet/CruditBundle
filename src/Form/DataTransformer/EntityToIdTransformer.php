<?php

namespace Lle\CruditBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToIdTransformer implements DataTransformerInterface
{
    private EntityManagerInterface $em;
    private ?string $class;
    private bool $multiple = false;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param mixed $value entity to transform
     * @return mixed the identifier of the entity
     */
    public function transform(mixed $value): mixed
    {
        // Multiple input
        if ($this->isMultiple()) {
            $result = [];

            if ($value === null) {
                return $result;
            }

            foreach ($value as $e) {
                $result[] = $e->getId();
            }

            return implode(",", $result);
        }

        // Single input
        return $value?->getId();
    }

    /**
     * @param mixed $value the identifier
     * @return mixed entity
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (!$value) {
            return $this->multiple ? [] : null;
        }

        /** @var class-string $class */
        $class = $this->class;

        // Multiple input
        if ($this->isMultiple()) {
            $ids = explode(",", $value);

            $entities = $this->em
                ->getRepository($class)
                ->findBy(["id" => $ids]);

            if (count($entities) !== count($ids)) {
                throw new TransformationFailedException(
                // An identifier from the input wasn't found in database
                    "Number of found entities does not equal the number of input values"
                );
            }

            return $entities;
        }

        // Single input
        $entity = $this->em
            ->getRepository($class)
            ->find($value);

        if (!$entity) {
            throw new TransformationFailedException(
                sprintf(
                    "Entity of class %s with id '%s' does not exist!",
                    $this->class,
                    $value
                )
            );
        }

        return $entity;
    }
}
