<?php

declare(strict_types=1);

namespace App\Crudit\Config;

use App\Crudit\Datasource\InterventionDatasource;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Field\WorkflowField;

/**
 * The interventions and their workflow (lot 3.4.c): a status in the list and on the record, its transitions.
 */
class InterventionCrudConfig extends AbstractCrudConfig
{
    public function __construct(InterventionDatasource $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * @return array<Field|Field[]>
     */
    public function getFields(string $key): array
    {
        $reference = Field::new('reference');
        $technician = Field::new('technician');
        $status = Field::new('status', WorkflowField::class);

        return match ($key) {
            CrudConfigInterface::INDEX, CrudConfigInterface::SHOW => [$reference, $technician, $status],
            default => [],
        };
    }

    public function getRootRoute(): string
    {
        return 'app_intervention';
    }
}
