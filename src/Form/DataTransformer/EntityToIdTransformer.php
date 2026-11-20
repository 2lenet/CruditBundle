<?php

namespace Lle\CruditBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToIdTransformer implements DataTransformerInterface
{
    private EntityManagerInterface $em;
    private ?string $class = null;
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
            if ($value === null) {
                return '';
            }

            $result = [];

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
            // the same id twice is one entity: findBy() gives it once
            $ids = array_values(array_unique(explode(",", $value)));

            $entities = $this->em
                ->getRepository($class)
                ->findBy(["id" => $ids]);

            if (count($entities) !== count($ids)) {
                $found = array_map(fn (mixed $entity): string => (string) $entity->getId(), $entities);

                throw $this->notFound(array_diff($ids, $found));
            }

            return $entities;
        }

        // Single input
        $entity = $this->em
            ->getRepository($class)
            ->find($value);

        if (!$entity) {
            throw $this->notFound([(string) $value]);
        }

        return $entity;
    }

    /**
     * An id without entity (deleted, or hidden by a Doctrine filter): the form tells which one, its token can be
     * removed to save the form.
     *
     * @param string[] $ids
     */
    private function notFound(array $ids): TransformationFailedException
    {
        $list = implode(', ', $ids);
        $exception = new TransformationFailedException(sprintf("No entity of class %s with the id(s) %s", $this->class, $list));
        $exception->setInvalidMessage('crudit.autocomplete.not_found', ['{{ ids }}' => $list]);

        return $exception;
    }
}
