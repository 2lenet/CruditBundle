<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Resolver;

use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Dto\RessourceView;

class RessourceResolver
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
    public function resolve(object $item, array $fields, DatasourceInterface $datasource): RessourceView
    {
        return new RessourceView(
            $datasource->getIdentifier($item),
            $item,
            $this->getFieldViews($fields, $item, $datasource)
        );
    }

    /**
     * @return FieldView[]
     */
    private function getFieldViews(array $fields, object $item, DatasourceInterface $datasource): array
    {
        $fieldViews = [];
        foreach ($fields as $field) {
            $fieldViews[] = $this->fieldResolver->resolveView($field, $item, $datasource);
        }
        return $fieldViews;
    }
}
