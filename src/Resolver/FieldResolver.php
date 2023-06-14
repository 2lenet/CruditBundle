<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Registry\FieldRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class FieldResolver
{
    private FieldRegistry $fieldRegistry;
    private PropertyAccessorInterface $propertyAccessor;
    private PropertyInfoExtractorInterface $propertyInfoExtractor;

    public function __construct(
        FieldRegistry $fieldRegistry,
        PropertyAccessorInterface $propertyAccessor,
        PropertyInfoExtractorInterface $propertyInfoExtractor
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->fieldRegistry = $fieldRegistry;
        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

    public function resolveView(
        Field $field,
        object $resource,
        DatasourceInterface $datasource,
        ?CrudConfigInterface $crudConfig = null
    ): FieldView {
        $subResource = $resource;
        $name = $field->getName();

        if ($field->hasCascade()) {
            $value = null;
            $cascade = explode('.', $field->getName());
            $subClass = get_class($subResource);

            foreach ($cascade as $k => $name) {
                $types = $this->propertyInfoExtractor->getTypes($subClass, $name);

                if (!$types) {
                    throw new CruditException(
                        sprintf(
                            "Could not determine type for property '%s' of class '%s'.",
                            $name,
                            $subClass,
                        )
                    );
                    break;
                }

                $propertyType = $types[0];

                if ($subResource) {
                    $value = $this->propertyAccessor->getValue($subResource, $name);
                }

                // if we are not at the last iteration
                if (array_key_last($cascade) !== $k) {
                    // it should always be a class
                    $subClass = $propertyType->isCollection()
                        ? $propertyType->getCollectionValueTypes()[0]
                        : $propertyType->getClassName();

                    $subResource = $value;
                }
            }
        } else {
            $value = $this->propertyAccessor->getValue($resource, $name);
        }

        $type = $field->getType();
        if ($type === null) {
            $type = $datasource->getType($name, isset($subClass) ? new $subClass() : $subResource);
            $field->setType($type);
        }

        $fieldView = (new FieldView($field, $value))
            ->setResource($resource)
            ->setParentResource($subResource)
            ->setConfig($crudConfig);

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
