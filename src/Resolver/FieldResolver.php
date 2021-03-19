<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FieldResolver
{

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PropertyAccessorInterface  */
    private $propertyAccessor;

    public function __construct(EntityManagerInterface $entityManager, PropertyAccessorInterface $propertyAccessor)
    {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
    }

    /** @param object|array $item */
    public function resolve(Field $field, $item): FieldView
    {
        return new FieldView(
            $field,
            $this->propertyAccessor->getValue(
                $item,
                (is_array($item)) ? '[' . $field->getName() . ']' : $field->getName()
            )
        );
    }

    public function toCamelCase(string $str, bool $capitaliseFirstChar = false): ?string
    {
        if ($capitaliseFirstChar) {
            $str[0] = strtoupper($str[0]);
        }
        return preg_replace_callback('/_([a-z])/', function ($c) {
            return "_" . strtolower($c[1]);
        }, $str);
    }
}
