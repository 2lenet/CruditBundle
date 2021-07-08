<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

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

    public function resolveView(Field $field, object $resource, DatasourceInterface $datasource): FieldView
    {
        $subResource = $resource;
        $name = $field->getName();
        if ($field->hasCascade()) {
            $value = null;
            $cascade = explode('.', $field->getName());
            foreach ($cascade as $k => $name) {
                $value = $this->propertyAccessor->getValue($subResource, $name);
                if (\count($cascade) - 1 !== $k) {
                    $subResource = $value;
                }
            }
        } else {
            $value = $this->propertyAccessor->getValue($resource, $name);
        }

        $type = $field->getType();
        if ($type === null) {
            $type = $datasource->getType($name, $subResource);
            $field->setType($type);
        }
        $fieldView = (new FieldView($field, $value))
            ->setResource($resource)
            ->setParentResource($subResource);

        return $this->fieldRegistry->get($type)
            ->buildView($fieldView, $value);
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
