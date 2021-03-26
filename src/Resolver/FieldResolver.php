<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Registry\FieldRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FieldResolver
{

    /** @var FieldRegistry */
    private $fieldRegistry;

    /** @var PropertyAccessorInterface  */
    private $propertyAccessor;

    public function __construct(FieldRegistry $fieldRegistry, PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->fieldRegistry = $fieldRegistry;
    }

    public function resolveView(Field $field, object $item, DatasourceInterface $datasource): FieldView
    {
        if ($field->getType() === null) {
            $field->setType($datasource->getType($field->getName()));
        }
        return $this->fieldRegistry->get($field->getType())->buildView(
            $field,
            $this->propertyAccessor->getValue($item, $field->getName())
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
