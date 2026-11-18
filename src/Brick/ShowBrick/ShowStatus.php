<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\ShowBrick;

use Lle\CruditBundle\Brick\LinksBrick\LinksConfig;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Field\WorkflowField;
use Lle\CruditBundle\Registry\FieldRegistry;

/**
 * The status of a record (lot 3.3.a): the first workflow field of getFields(SHOW), shown beside the title by the
 * header (LinksBrick) and left out of its section by the record (ShowBrick), unless isShowStatusInHeader() is false
 * or the page of the record has no header.
 */
class ShowStatus
{
    public function __construct(private readonly FieldRegistry $fieldRegistry)
    {
    }

    public function find(CrudConfigInterface $crudConfig): ?Field
    {
        if (!$crudConfig->isShowStatusInHeader()) {
            return null;
        }

        foreach ($crudConfig->getFields(CrudConfigInterface::SHOW) as $field) {
            foreach (\is_array($field) ? $field : [$field] as $item) {
                if ($item instanceof Field && $this->isWorkflow($item)) {
                    return $item;
                }
            }
        }

        return null;
    }

    /** The page of the record has a header to show the status in (a LinksBrick, as by default) */
    public function hasHeader(CrudConfigInterface $crudConfig): bool
    {
        foreach ($crudConfig->getBrickConfigs()[CrudConfigInterface::SHOW] ?? [] as $brickConfig) {
            if ($brickConfig instanceof LinksConfig) {
                return true;
            }
        }

        return false;
    }

    /** A workflow is always declared (type workflow or WorkflowField): Doctrine guesses a string */
    private function isWorkflow(Field $field): bool
    {
        $type = $field->getType();
        if ($type === null) {
            return false;
        }

        try {
            return $this->fieldRegistry->get($type) instanceof WorkflowField;
        } catch (CruditException) {
            return false;
        }
    }
}
