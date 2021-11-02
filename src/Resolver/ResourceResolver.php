<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\ResourceView;

class ResourceResolver
{

    /** @var FieldResolver */
    private $fieldResolver;

    public function __construct(
        FieldResolver $fieldResolver
    ) {
        $this->fieldResolver = $fieldResolver;
    }

    /**
     * @param Field[] $fields
     */
    public function resolve(
        object $resource,
        array $fields,
        DatasourceInterface $datasource,
        ?CrudConfigInterface $crudConfig = null
    ): ResourceView
    {
        return new ResourceView(
            $datasource->getIdentifier($resource),
            $resource,
            $this->getFieldViews($fields, $resource, $datasource, $crudConfig)
        );
    }

    /**
     * @return FieldView[]
     */
    private function getFieldViews(
        array $fields,
        object $resource,
        DatasourceInterface $datasource,
        ?CrudConfigInterface $crudConfig = null
    ): array
    {
        $fieldViews = [];
        foreach ($fields as $field) {
            $fieldViews[] = $this->fieldResolver->resolveView($field, $resource, $datasource, $crudConfig);
        }

        return $fieldViews;
    }
}
