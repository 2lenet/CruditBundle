<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Filter;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\FilterTypeInterface;
use Lle\CruditBundle\Entity\SavedFilter;
use Lle\CruditBundle\Filter\FilterType\AbstractFilterType;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditUiBundle\Filter\PeriodPreset;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * The sets of filters a user saved for a list (lot 3.1.f).
 *
 * - **Per user**: the identifier of the user of the firewall (two firewalls, or two classes of users, with the same
 *   identifier share their sets: the owner is a string, not an association). Without a user, or with `saved_filters.enabled` false,
 *   nothing is available. A set of another user does not exist for this one (not found, not forbidden).
 * - **What is saved** is what the form of the filters sends, through the same checks as the page (FilterState): the
 *   filters the user may use and that are not `hidden`, those with a criterion only. A hidden filter keeps its value
 *   when a set is recalled (a link of "See also" may have set it).
 * - **A ready-made period is saved by its name** (`preset`) and its dates are worked out again when the set is
 *   recalled: "this month" stays true from one month to the next.
 * - A recalled set goes through the checks again, filter by filter: what no longer exists (a filter removed, a
 *   condition, a right) is left out.
 *
 * @phpstan-type Criteria array<string, array<string, string>>
 */
class SavedFilterManager
{
    /** The CSRF token id of the three actions. */
    public const string TOKEN_ID = 'crudit_saved_filters';

    public function __construct(
        private readonly FilterState $filterState,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ?ManagerRegistry $registry,
        private readonly bool $enabled,
        private readonly int $max,
        private readonly int $maxSize,
    ) {
    }

    /**
     * Whether the sets can be used now: enabled, a database, a user.
     */
    public function isAvailable(): bool
    {
        return $this->enabled && $this->registry?->getManagerForClass(SavedFilter::class) !== null && $this->owner() !== null;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function createToken(): string
    {
        return $this->csrfTokenManager->getToken(self::TOKEN_ID)->getValue();
    }

    public function isTokenValid(?string $token): bool
    {
        return $token !== null && $this->csrfTokenManager->isTokenValid(new CsrfToken(self::TOKEN_ID, $token));
    }

    public function getFilterSet(string $filterSetId): ?FilterSetInterface
    {
        return $this->filterState->getFilterSet($filterSetId);
    }

    /**
     * The sets of the user for a list, by name.
     *
     * @return list<SavedFilter>
     * @phpstan-impure
     */
    public function list(string $filterSetId): array
    {
        $owner = $this->requireOwner();
        $sets = $this->manager()->getRepository(SavedFilter::class)->findBy(['owner' => $owner, 'filterset' => $filterSetId]);
        usort($sets, static fn (SavedFilter $a, SavedFilter $b): int => strcasecmp($a->getName(), $b->getName()));

        return $sets;
    }

    /**
     * Saves what the form of the filters sent, under a name: a name already taken (without regard to the case)
     * replaces that set.
     *
     * @param array<string, mixed> $query the parameters of the form (`filter_<set>_<filter>_…`)
     * @throws SavedFilterException
     */
    public function save(FilterSetInterface $filterset, string $name, array $query): SavedFilter
    {
        try {
            return $this->doSave($filterset, $name, $query);
        } catch (UniqueConstraintViolationException) {
            // Two requests saved the same name at once (a double click), the second one lost: the manager is closed by
            // the error. Once more, with a new manager: the set of the winner is found and replaced.
            $this->resetManager();

            return $this->doSave($filterset, $name, $query);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @throws SavedFilterException
     * @throws UniqueConstraintViolationException the name was saved by another request since the search of a duplicate
     */
    private function doSave(FilterSetInterface $filterset, string $name, array $query): SavedFilter
    {
        $owner = $this->requireOwner();
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ($name === '' || mb_strlen($name) > SavedFilter::NAME_LENGTH) {
            throw new SavedFilterException(SavedFilterException::NAME, SavedFilter::NAME_LENGTH);
        }

        $criteria = $this->criteria($filterset, $this->filterState->fromQuery($filterset, $query));
        if ($criteria === []) {
            throw new SavedFilterException(SavedFilterException::NO_FILTER);
        }
        if (strlen((string) json_encode($criteria)) > $this->maxSize) {
            throw new SavedFilterException(SavedFilterException::TOO_BIG);
        }

        // The same name is the one of the database: its collation decides (utf8mb4_unicode_ci: "Été" is "ete"), and it is
        // the unique index that would refuse the other. The comparison without regard to the case catches the rest
        // (SQLite and the binary collations tell "Late" from "late").
        $sets = $this->list($filterset->getId());
        $existing = $this->manager()->getRepository(SavedFilter::class)->findOneBy(['owner' => $owner, 'filterset' => $filterset->getId(), 'name' => $name]);
        if (!$existing instanceof SavedFilter) {
            $existing = null;
            foreach ($sets as $set) {
                if (mb_strtolower($set->getName()) === mb_strtolower($name)) {
                    $existing = $set;
                }
            }
        }
        if ($existing === null && count($sets) >= $this->max) {
            throw new SavedFilterException(SavedFilterException::TOO_MANY, $this->max);
        }

        $now = $this->now();
        $manager = $this->manager();
        if ($existing !== null) {
            $existing->setName($name)->setCriteria($criteria, $now);
            $saved = $existing;
        } else {
            $saved = new SavedFilter($owner, $filterset->getId(), $name, $criteria, $now);
            $manager->persist($saved);
        }
        $manager->flush();

        return $saved;
    }

    /**
     * Replaces the filters in use for a list with those of a set, and puts the list back on its first page. The hidden
     * filters keep what they have. The search is not touched (it is not in the session of the filters).
     *
     * @throws SavedFilterException
     */
    public function apply(Request $request, FilterSetInterface $filterset, int $id, ?\DateTimeInterface $now = null): SavedFilter
    {
        $set = $this->find($filterset->getId(), $id);
        $data = $this->criteriaToData($filterset, $set->getCriteria(), $now);
        if ($this->criteria($filterset, $data) === []) {
            throw new SavedFilterException(SavedFilterException::NOTHING_LEFT);
        }

        $kept = [];
        foreach ($filterset->getFilters() as $filter) {
            if ($filter instanceof AbstractFilterType && $filter->isHidden()) {
                $kept[$filter->getId()] = $this->filterState->getSetData($filterset->getId())[$filter->getId()] ?? null;
            }
        }
        $this->filterState->replaceSetData($request, $filterset->getId(), array_filter($kept, static fn (mixed $value): bool => $value !== null) + $data);

        return $set;
    }

    /**
     * @throws SavedFilterException
     */
    public function delete(string $filterSetId, int $id): SavedFilter
    {
        $set = $this->find($filterSetId, $id);
        $manager = $this->manager();
        $manager->remove($set);
        $manager->flush();

        return $set;
    }

    /**
     * The set that has exactly the criteria now applied, null if none has.
     *
     * @param list<SavedFilter> $sets
     * @phpstan-impure
     */
    public function current(FilterSetInterface $filterset, array $sets): ?SavedFilter
    {
        $applied = $this->comparable($filterset, $this->filterState->getSetData($filterset->getId()));
        if ($applied === []) {
            return null;
        }
        foreach ($sets as $set) {
            if ($this->comparable($filterset, $this->criteriaToData($filterset, $set->getCriteria())) === $applied) {
                return $set;
            }
        }

        return null;
    }

    /**
     * The saved criteria as the data of the filters, through the checks of the page: the filters that exist, that the
     * user may use, with a condition of their type. The dates of a ready-made period are worked out for $now (today).
     *
     * @param Criteria $criteria
     * @return array<string, mixed>
     */
    public function criteriaToData(FilterSetInterface $filterset, array $criteria, ?\DateTimeInterface $now = null): array
    {
        $query = [];
        foreach ($filterset->getFilters() as $filter) {
            $id = $filter->getId();
            if (!isset($criteria[$id]) || ($filter instanceof AbstractFilterType && $filter->isHidden())) {
                continue;
            }
            $values = $criteria[$id];
            $preset = PeriodPreset::fromName($values['preset'] ?? null);
            if ($preset !== null) {
                ['from' => $values['value'], 'to' => $values['to']] = $preset->range($now ?? $this->now());
            }
            $key = 'filter_' . $filterset->getId() . '_' . $id;
            foreach ($values as $name => $value) {
                $query[$key . '_' . $name] = $value;
            }
        }

        return $this->filterState->fromQuery($filterset, $query);
    }

    /**
     * The criteria of the data of a filter set: the filters that are visible and have a condition with a value (or
     * "is empty"), without the labels of the choices. Empty: nothing to save.
     *
     * @param array<string, mixed> $data
     * @return Criteria
     */
    private function criteria(FilterSetInterface $filterset, array $data): array
    {
        $criteria = [];
        foreach ($filterset->getFilters() as $filter) {
            $id = $filter->getId();
            $values = $data[$id] ?? null;
            if (!is_array($values) || ($filter instanceof AbstractFilterType && $filter->isHidden())) {
                continue;
            }
            unset($values['items']);
            $values = array_map('strval', array_filter($values, static fn (mixed $value): bool => is_scalar($value) && $value !== ''));
            $op = $values['op'] ?? null;
            // A boolean that was left alone sends "all": it is no criterion (as the chips and the count of the bar say).
            $hasValue = $filter instanceof BooleanFilterType
                ? in_array($values['value'] ?? null, ['true', 'false'], true)
                : ($values['value'] ?? '') !== '' || ($values['to'] ?? '') !== '';
            if ($op === FilterTypeInterface::OPERATOR_IS_NULL || $op === FilterTypeInterface::OPERATOR_IS_NOT_NULL || $hasValue) {
                $criteria[$id] = $values;
            }
        }

        return $criteria;
    }

    /**
     * What decides that two sets of criteria are the same: a period chosen by its name counts by its name, not by its
     * dates (which move with the day).
     *
     * @param array<string, mixed> $data
     * @return array<string, array<string, string>>
     */
    private function comparable(FilterSetInterface $filterset, array $data): array
    {
        $comparable = [];
        foreach ($this->criteria($filterset, $data) as $id => $values) {
            if (PeriodPreset::fromName($values['preset'] ?? null) !== null) {
                unset($values['value'], $values['to']);
            }
            ksort($values);
            $comparable[$id] = $values;
        }
        ksort($comparable);

        return $comparable;
    }

    /**
     * @throws SavedFilterException
     */
    private function find(string $filterSetId, int $id): SavedFilter
    {
        $owner = $this->requireOwner();
        $set = $this->manager()->getRepository(SavedFilter::class)->find($id);
        if (!$set instanceof SavedFilter || $set->getOwner() !== $owner || $set->getFilterset() !== $filterSetId) {
            throw new SavedFilterException(SavedFilterException::NOT_FOUND);
        }

        return $set;
    }

    /**
     * The identifier of the user, null without a user or with an identifier that the column cannot keep (an identifier
     * cut would be the one of another user). During a `switch_user` it is the user impersonated, as for the rest of the
     * application: the sets of an account are those of the one who acts in it.
     */
    private function owner(): ?string
    {
        $owner = $this->security->getUser()?->getUserIdentifier();

        return $owner === null || mb_strlen($owner) > SavedFilter::OWNER_LENGTH ? null : $owner;
    }

    /**
     * @throws SavedFilterException
     */
    private function requireOwner(): string
    {
        $owner = $this->enabled ? $this->owner() : null;
        if ($owner === null || $this->registry?->getManagerForClass(SavedFilter::class) === null) {
            throw new SavedFilterException(SavedFilterException::UNAVAILABLE);
        }

        return $owner;
    }

    /**
     * A new entity manager for the class, after an error closed the one in use.
     */
    private function resetManager(): void
    {
        if ($this->registry === null) {
            return;
        }
        $manager = $this->registry->getManagerForClass(SavedFilter::class);
        foreach (array_keys($this->registry->getManagerNames()) as $name) {
            if ($this->registry->getManager((string) $name) === $manager) {
                $this->registry->resetManager((string) $name);

                return;
            }
        }
    }

    private function manager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry?->getManagerForClass(SavedFilter::class);

        return $manager;
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
