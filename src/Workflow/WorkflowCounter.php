<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Workflow;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The number of elements in each state of a workflow, in the order of its places (a state without element counts 0),
 * with the tone of the state and the link to the list filtered on it. One query (GROUP BY on the property of the
 * marking): a workflow of type state_machine, whose marking is one place in a column.
 */
class WorkflowCounter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkflowStates $states,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param class-string      $class        the entity
     * @param string|null       $name         the workflow, else the first one of the entity
     * @param QueryBuilder|null $queryBuilder to count part of the elements (its root entity is $class)
     * @param CrudConfigInterface|null $crud the CRUD whose list the counters open
     * @param string|null       $filter       the id of the WorkflowFilterType of its filterset that the link fills
     *                                        ("status"; no link without a filterset)
     *
     * @return list<WorkflowCount>
     */
    public function count(
        string $class,
        ?string $name = null,
        ?QueryBuilder $queryBuilder = null,
        ?CrudConfigInterface $crud = null,
        ?string $filter = null,
    ): array {
        $workflow = $this->states->workflow((new \ReflectionClass($class))->newInstanceWithoutConstructor(), $name);
        $property = $this->markingProperty($workflow);

        $queryBuilder = $queryBuilder !== null
            ? clone $queryBuilder
            : $this->entityManager->createQueryBuilder()->from($class, 'e');
        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->select(sprintf('%1$s.%2$s AS place, COUNT(%1$s) AS total', $alias, $property))
            ->groupBy(sprintf('%s.%s', $alias, $property))
            ->resetDQLPart('orderBy')
            ->setFirstResult(null)
            ->setMaxResults(null);

        $totals = [];
        /** @var array{place: mixed, total: int|string} $row */
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            if (is_scalar($row['place'])) {
                $totals[(string) $row['place']] = (int) $row['total'];
            }
        }

        $counts = [];
        foreach ($workflow->getDefinition()->getPlaces() as $place) {
            $place = (string) $place;
            $counts[] = new WorkflowCount(
                $this->states->state($workflow, $place),
                $totals[$place] ?? 0,
                $this->url($place, $crud, $filter),
            );
        }

        return $counts;
    }

    /**
     * The list of the CRUD filtered on the state, as its filters send it (FilterState::handleRequest()), on page 1.
     */
    private function url(string $place, ?CrudConfigInterface $crud, ?string $filter): ?string
    {
        $filterset = $crud?->getFilterset();
        if ($crud === null || $filter === null || $filterset === null) {
            return null;
        }

        $key = 'filter_' . $filterset->getId() . '_' . $filter;

        return $this->urlGenerator->generate($crud->getRootRoute() . '_index', [
            $key . '_op' => FilterTypeInterface::OPERATOR_IN,
            $key . '_value' => $place,
            strtolower((string) $crud->getName()) . '_offset' => 0,
        ]);
    }

    private function markingProperty(WorkflowInterface $workflow): string
    {
        $store = $workflow->getMarkingStore();
        $singleState = $store instanceof MethodMarkingStore
            ? (new \ReflectionProperty(MethodMarkingStore::class, 'singleState'))->getValue($store)
            : false;
        if (!$store instanceof MethodMarkingStore || $singleState !== true) {
            throw new \LogicException(sprintf(
                'The workflow "%s" cannot be counted: it must be a state_machine, its marking one place in a column.',
                $workflow->getName(),
            ));
        }

        /** @var string */
        return (new \ReflectionProperty(MethodMarkingStore::class, 'property'))->getValue($store);
    }
}
