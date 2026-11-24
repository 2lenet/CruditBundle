<?php

declare(strict_types=1);

namespace App\Crudit\Config;

use App\Crudit\Datasource\InterventionDatasource;
use App\Entity\Intervention;
use App\Security\AllowAllVoter;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\ShowAlert;
use Lle\CruditBundle\Field\CurrencyField;
use Lle\CruditBundle\Field\DateField;
use Lle\CruditBundle\Field\TextAreaField;
use Lle\CruditBundle\Field\WorkflowField;

/**
 * The interventions and their workflow (lot 3.4.c): a status in the list and on the record, its transitions.
 *
 * Their record (lot 3.3.a): the status beside the title, the technician as subtitle, the key information, an alert
 * when it is late, sections in two columns (the 2nd one with 2 fields per line, the 3rd one refused to everybody,
 * the 4th one folded and alone on its line), a field over 2 columns and a long text over the whole line.
 */
class InterventionCrudConfig extends AbstractCrudConfig
{
    public function __construct(InterventionDatasource $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * A second button of export with its own formats, added the way a project does (numeric key, not `export`): the
     * controller must accept its format although the action of the bundle does not offer it.
     */
    public function getListActions(): array
    {
        $actions = parent::getListActions();
        $actions[] = ListAction::new('Export XML', $this->getPath(CrudConfigInterface::EXPORT), Icon::new('download'))
            ->setModal('@LleCrudit/modal/_export.html.twig')
            ->setConfig(['export' => ['xml']])
            ->setRole(sprintf('ROLE_%s_%s', $this->getName(), CrudConfigInterface::EXPORT));
        // a button for a role that nobody has: its format is not asked by the URL
        $actions[] = ListAction::new('Export TXT', $this->getPath(CrudConfigInterface::EXPORT), Icon::new('download'))
            ->setModal('@LleCrudit/modal/_export.html.twig')
            ->setConfig(['export' => ['txt']])
            ->setRole(AllowAllVoter::NOBODY);

        return $actions;
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
            CrudConfigInterface::INDEX => [$reference, $technician, $status],
            CrudConfigInterface::SHOW => [
                'section.intervention' => [
                    $reference,
                    $status,
                    Field::new('technician', null, ['span' => 2]),
                    Field::new('plannedOn', DateField::class),
                ],
                'section.billing' => [
                    Field::new('amount', CurrencyField::class),
                    Field::new('description', TextAreaField::class),
                ],
                'section.internal' => [
                    Field::new('internalNote')->setRole(AllowAllVoter::NOBODY),
                ],
                'section.history' => [
                    Field::new('createdAt'),
                    Field::new('updatedBy'),
                ],
            ],
            default => [],
        };
    }

    public function getShowHighlights(): array
    {
        return [
            Field::new('plannedOn', DateField::class),
            Field::new('technician'),
            Field::new('amount', CurrencyField::class),
        ];
    }

    public function getShowSubtitle(object $resource): ?string
    {
        return $resource instanceof Intervention ? $resource->getTechnician() : null;
    }

    public function getShowAlert(object $resource): ?ShowAlert
    {
        if (!$resource instanceof Intervention || $resource->getDelay() === 0) {
            return null;
        }

        return ShowAlert::warning('alert.late', ['%count%' => $resource->getDelay()]);
    }

    public function getShowSectionColumns(): array
    {
        return ['section.billing' => 2];
    }

    public function getShowNumberFieldGroupsOpened(): ?int
    {
        return 3;
    }

    public function getShowWideSections(): array
    {
        return ['section.history'];
    }

    public function getRootRoute(): string
    {
        return 'app_intervention';
    }
}
