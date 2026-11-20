<?php

declare(strict_types=1);

namespace phpunit\Unit\Filter;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Lle\CruditBundle\Datasource\AbstractFilterSet;
use Lle\CruditBundle\Entity\SavedFilter;
use Lle\CruditBundle\Filter\FilterState;
use Lle\CruditBundle\Filter\FilterType\BooleanFilterType;
use Lle\CruditBundle\Filter\FilterType\ChoiceFilterType;
use Lle\CruditBundle\Filter\FilterType\NumberFilterType;
use Lle\CruditBundle\Filter\FilterType\PeriodeFilterType;
use Lle\CruditBundle\Filter\FilterType\StringFilterType;
use Lle\CruditBundle\Filter\SavedFilterException;
use Lle\CruditBundle\Filter\SavedFilterManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * The sets of filters of a user, on a real database (SQLite in memory).
 */
class SavedFilterManagerTest extends TestCase
{
    private EntityManager $em;

    private Session $session;

    private ?string $user = 'alice';

    private bool $adminOnlyGranted = true;

    private FilterState $state;

    private \Lle\CruditBundle\Contracts\FilterSetInterface $set;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/../../../../src/Entity']);
        $config->enableNativeLazyObjects(true);
        $this->em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->session = new Session(new MockArraySessionStorage());

        $this->set = new class () extends AbstractFilterSet {
            public function getId(): string
            {
                return 'client';
            }

            public function getFilters(): array
            {
                return [
                    StringFilterType::new('name'),
                    ChoiceFilterType::new('status', ['a' => 'a', 'b' => 'b'], true)->setAdditionnalKeys(['items']),
                    NumberFilterType::new('amount'),
                    PeriodeFilterType::new('planned'),
                    BooleanFilterType::new('active'),
                    StringFilterType::new('owner')->setHidden(true),
                    StringFilterType::new('secret')->setRole('ROLE_ADMIN'),
                ];
            }
        };
    }

    private function manager(bool $enabled = true, int $max = 3, int $maxSize = 8192): SavedFilterManager
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturnCallback(fn () => $this->user === null ? null : new InMemoryUser($this->user, null));
        $security->method('isGranted')->willReturnCallback(fn (string $role): bool => $this->adminOnlyGranted);
        $this->state = new FilterState([$this->set], $security, new RequestStack());
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturnCallback(fn () => $this->em);
        $registry->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $registry->method('getManager')->willReturnCallback(fn () => $this->em);
        // What the real registry does after an error closed the manager: a new one, on the same connection.
        $registry->method('resetManager')->willReturnCallback(function (): EntityManager {
            $this->em = new EntityManager($this->em->getConnection(), $this->em->getConfiguration());

            return $this->em;
        });
        $tokens = $this->createMock(CsrfTokenManagerInterface::class);
        $tokens->method('isTokenValid')->willReturn(true);

        return new SavedFilterManager($this->state, $security, $tokens, $registry, $enabled, $max, $maxSize);
    }

    /**
     * The parameters of the form of the filters.
     *
     * @param array<string, string> $fields `<filter>_<key>` => value
     * @return array<string, string>
     */
    private function form(array $fields): array
    {
        $query = [];
        foreach ($fields as $name => $value) {
            $query['filter_client_' . $name] = $value;
        }

        return $query;
    }

    private function request(): Request
    {
        $request = Request::create('/client/', 'POST');
        $request->setSession($this->session);

        return $request;
    }

    public function testASetIsSavedWithTheCriteriaOfTheFormOnly(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, '  Late   clients ', $this->form([
            'name_op' => 'contains', 'name_value' => 'Ali',
            'status_op' => 'in', 'status_value' => 'a,b', 'status_items' => 'Label A,Label B',
            'amount_op' => 'gt', 'amount_value' => '',
            'owner_op' => 'eq', 'owner_value' => 'someone',
            'secret_op' => 'eq', 'secret_value' => 'x',
        ]));

        self::assertSame('Late clients', $saved->getName());
        self::assertSame('alice', $saved->getOwner());
        self::assertSame('client', $saved->getFilterset());
        // A filter without a value, a hidden one and the labels of the choices are left out.
        self::assertSame(
            ['name' => ['op' => 'contains', 'value' => 'Ali'], 'status' => ['op' => 'in', 'value' => 'a,b'], 'secret' => ['op' => 'eq', 'value' => 'x']],
            $saved->getCriteria(),
        );
    }

    public function testTwoRequestsThatSaveTheSameNameAtOnceEndWithOneSet(): void
    {
        $manager = $this->manager();
        $criteria = $this->form(['name_op' => 'contains', 'name_value' => 'Ali']);
        // The other request saves the name between the search of a duplicate and the insert: the unique index refuses ours.
        $this->em->getEventManager()->addEventListener(Events::onFlush, new class ($this->em->getConnection()) {
            public function __construct(private readonly \Doctrine\DBAL\Connection $connection)
            {
            }

            public function onFlush(OnFlushEventArgs $args): void
            {
                $this->connection->insert('crudit_saved_filter', ['owner' => 'alice', 'filterset' => 'client', 'name' => 'Same', 'criteria' => '{}', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
            }
        });

        $saved = $manager->save($this->set, 'Same', $criteria);

        self::assertSame('Same', $saved->getName());
        self::assertSame(['name' => ['op' => 'contains', 'value' => 'Ali']], $saved->getCriteria());
        self::assertCount(1, $manager->list('client'), 'the set of the other request is replaced, not doubled');
    }

    public function testAnIdentifierTheColumnCannotKeepMakesTheFeatureUnavailable(): void
    {
        $this->user = str_repeat('a', SavedFilter::OWNER_LENGTH);
        self::assertTrue($this->manager()->isAvailable());

        $this->user = str_repeat('a', SavedFilter::OWNER_LENGTH + 1);
        $manager = $this->manager();
        self::assertFalse($manager->isAvailable(), 'an identifier cut to fit would be the one of another user');
        $this->expectExceptionObject(new SavedFilterException(SavedFilterException::UNAVAILABLE));
        $manager->save($this->set, 'Mine', $this->form(['name_op' => 'contains', 'name_value' => 'Ali']));
    }

    public function testAFilterTheUserMayNotUseIsNotSaved(): void
    {
        $this->adminOnlyGranted = false;
        $saved = $this->manager()->save($this->set, 'Mine', $this->form(['name_op' => 'contains', 'name_value' => 'Ali', 'secret_op' => 'eq', 'secret_value' => 'x']));

        self::assertSame(['name' => ['op' => 'contains', 'value' => 'Ali']], $saved->getCriteria());
    }

    public function testIsEmptyIsACriterionWithoutAValue(): void
    {
        $saved = $this->manager()->save($this->set, 'No amount', $this->form(['amount_op' => 'isnull']));

        self::assertSame(['amount' => ['op' => 'isnull']], $saved->getCriteria());
    }

    public function testABooleanLeftAloneIsNoCriterion(): void
    {
        $manager = $this->manager();
        // What the form sends for a boolean nobody touched, with an empty text filter: nothing to save.
        try {
            $manager->save($this->set, 'Nothing', $this->form(['active_op' => 'eq', 'active_value' => 'all', 'name_op' => 'contains', 'name_value' => '']));
            self::fail('a boolean on "all" is not a criterion');
        } catch (SavedFilterException $e) {
            self::assertSame(SavedFilterException::NO_FILTER, $e->reason);
        }
        // Chosen, it is one; left alone beside another criterion, it is left out.
        self::assertSame(['active' => ['op' => 'eq', 'value' => 'false']], $manager->save($this->set, 'Inactive', $this->form(['active_op' => 'eq', 'active_value' => 'false']))->getCriteria());
        self::assertSame(['name' => ['op' => 'contains', 'value' => 'A']], $manager->save($this->set, 'Named', $this->form(['active_op' => 'eq', 'active_value' => 'all', 'name_op' => 'contains', 'name_value' => 'A']))->getCriteria());
    }

    public function testNothingToSaveWithoutACriterion(): void
    {
        $this->expectExceptionObject(new SavedFilterException(SavedFilterException::NO_FILTER));

        $this->manager()->save($this->set, 'Empty', $this->form(['name_op' => 'contains', 'name_value' => '']));
    }

    public function testTheNameIsRequiredAndLimited(): void
    {
        $manager = $this->manager();
        foreach (['', '   ', str_repeat('é', SavedFilter::NAME_LENGTH + 1)] as $name) {
            try {
                $manager->save($this->set, $name, $this->form(['name_op' => 'contains', 'name_value' => 'A']));
                self::fail('a name of "' . $name . '" is refused');
            } catch (SavedFilterException $e) {
                self::assertSame(SavedFilterException::NAME, $e->reason);
            }
        }
        // 100 characters, accents included, are accepted.
        self::assertSame(100, mb_strlen($manager->save($this->set, str_repeat('é', 100), $this->form(['name_op' => 'contains', 'name_value' => 'A']))->getName()));
    }

    public function testTheSameNameReplacesTheSetWithoutRegardToTheCase(): void
    {
        $manager = $this->manager(max: 1);
        $first = $manager->save($this->set, 'Late', $this->form(['name_op' => 'contains', 'name_value' => 'A']));
        $second = $manager->save($this->set, 'LATE', $this->form(['name_op' => 'contains', 'name_value' => 'B']));

        self::assertSame($first->getId(), $second->getId());
        self::assertSame('LATE', $second->getName());
        self::assertSame('B', $second->getCriteria()['name']['value']);
        self::assertCount(1, $manager->list('client'));
    }

    public function testTheNumberOfSetsIsLimitedPerListAndPerUser(): void
    {
        $manager = $this->manager(max: 2);
        $form = $this->form(['name_op' => 'contains', 'name_value' => 'A']);
        $manager->save($this->set, 'One', $form);
        $manager->save($this->set, 'Two', $form);
        try {
            $manager->save($this->set, 'Three', $form);
            self::fail('a third set is refused');
        } catch (SavedFilterException $e) {
            self::assertSame(SavedFilterException::TOO_MANY, $e->reason);
            self::assertSame(2, $e->limit);
        }
        // Replacing one is still possible; another user has her own sets.
        $manager->save($this->set, 'Two', $form);
        $this->user = 'bob';
        self::assertSame([], $manager->list('client'));
        $manager->save($this->set, 'One', $form);
        self::assertCount(1, $manager->list('client'));
    }

    public function testTheCriteriaAreLimitedInSize(): void
    {
        $this->expectExceptionObject(new SavedFilterException(SavedFilterException::TOO_BIG));

        $this->manager(maxSize: 256)->save($this->set, 'Big', $this->form(['name_op' => 'contains', 'name_value' => str_repeat('x', 300)]));
    }

    public function testASetOfAnotherUserDoesNotExist(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'Mine', $this->form(['name_op' => 'contains', 'name_value' => 'A']));
        $this->user = 'bob';

        foreach ([fn () => $manager->apply($this->request(), $this->set, (int) $saved->getId()), fn () => $manager->delete('client', (int) $saved->getId())] as $action) {
            try {
                $action();
                self::fail('the set of Alice is not found for Bob');
            } catch (SavedFilterException $e) {
                self::assertSame(SavedFilterException::NOT_FOUND, $e->reason);
            }
        }
        self::assertCount(1, $this->em->getRepository(SavedFilter::class)->findAll());
    }

    public function testASetOfAnotherListIsNotFound(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'Mine', $this->form(['name_op' => 'contains', 'name_value' => 'A']));

        $this->expectExceptionObject(new SavedFilterException(SavedFilterException::NOT_FOUND));
        $manager->delete('intervention', (int) $saved->getId());
    }

    public function testNothingIsAvailableWithoutAUserOrWhenTurnedOff(): void
    {
        self::assertTrue($this->manager()->isAvailable());
        self::assertFalse($this->manager(enabled: false)->isAvailable());
        $this->user = null;
        $manager = $this->manager();
        self::assertFalse($manager->isAvailable());

        $this->expectExceptionObject(new SavedFilterException(SavedFilterException::UNAVAILABLE));
        $manager->list('client');
    }

    public function testASavedSetReplacesTheFiltersInUseAndKeepsTheHiddenOnes(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'Late', $this->form(['name_op' => 'contains', 'name_value' => 'Ali']));
        // The page now has other filters, and a hidden one set by a link.
        $request = Request::create('/client/', 'GET', $this->form(['amount_op' => 'gt', 'amount_value' => '10', 'owner_op' => 'eq', 'owner_value' => '42']));
        $request->setSession($this->session);
        $this->state->handleRequest($request);
        $this->session->set('crudit_datasourceparams_client', ['offset' => 40, 'sort' => 'name']);

        $manager->apply($this->request(), $this->set, (int) $saved->getId());

        self::assertSame(['owner' => ['op' => 'eq', 'value' => '42'], 'name' => ['op' => 'contains', 'value' => 'Ali']], $this->state->getSetData('client'));
        // Back to the first page, the sort is kept.
        self::assertSame(['offset' => 0, 'sort' => 'name'], $this->session->get('crudit_datasourceparams_client'));
    }

    public function testARecalledPeriodIsWorkedOutAgain(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'This month', $this->form([
            'planned_op' => 'interval', 'planned_value' => '2026-10-01', 'planned_to' => '2026-10-31', 'planned_preset' => 'this_month',
        ]));
        self::assertSame('this_month', $saved->getCriteria()['planned']['preset']);

        $manager->apply($this->request(), $this->set, (int) $saved->getId(), new \DateTimeImmutable('2026-11-19'));

        self::assertSame(['op' => 'interval', 'value' => '2026-11-01', 'to' => '2026-11-30', 'preset' => 'this_month'], $this->state->getData('client', 'planned'));
    }

    public function testAPeriodTypedByHandStaysFixed(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'October', $this->form(['planned_op' => 'interval', 'planned_value' => '2026-10-01', 'planned_to' => '2026-10-31', 'planned_preset' => '']));

        $manager->apply($this->request(), $this->set, (int) $saved->getId(), new \DateTimeImmutable('2026-11-19'));

        self::assertSame(['op' => 'interval', 'value' => '2026-10-01', 'to' => '2026-10-31'], $this->state->getData('client', 'planned'));
    }

    public function testWhatNoLongerExistsIsLeftOutWhenRecalled(): void
    {
        $manager = $this->manager();
        // A set saved when the list had a filter "gone", a condition the type does not have, and a right since removed.
        $set = new SavedFilter('alice', 'client', 'Old', [
            'gone' => ['op' => 'eq', 'value' => '1'],
            'name' => ['op' => 'nonsense', 'value' => 'Ali'],
            'amount' => ['op' => 'gt', 'value' => '10'],
            'secret' => ['op' => 'eq', 'value' => 'x'],
        ]);
        $this->em->persist($set);
        $this->em->flush();
        $this->adminOnlyGranted = false;
        $manager = $this->manager();

        $manager->apply($this->request(), $this->set, (int) $set->getId());

        self::assertSame(['amount' => ['op' => 'gt', 'value' => '10']], $this->state->getSetData('client'));
    }

    public function testNothingChangesWhenNothingIsLeft(): void
    {
        $set = new SavedFilter('alice', 'client', 'Old', ['gone' => ['op' => 'eq', 'value' => '1']]);
        $this->em->persist($set);
        $this->em->flush();
        $manager = $this->manager();
        $request = Request::create('/client/', 'GET', $this->form(['name_op' => 'contains', 'name_value' => 'Ali']));
        $request->setSession($this->session);
        $this->state->handleRequest($request);

        try {
            $manager->apply($this->request(), $this->set, (int) $set->getId());
            self::fail('a set with nothing left is not applied');
        } catch (SavedFilterException $e) {
            self::assertSame(SavedFilterException::NOTHING_LEFT, $e->reason);
        }
        self::assertSame(['op' => 'contains', 'value' => 'Ali'], $this->state->getData('client', 'name'));
    }

    public function testTheSetInUseIsTheOneWithTheSameCriteria(): void
    {
        $manager = $this->manager();
        $late = $manager->save($this->set, 'Late', $this->form(['name_op' => 'contains', 'name_value' => 'Ali']));
        $month = $manager->save($this->set, 'This month', $this->form(['planned_op' => 'interval', 'planned_value' => '2026-10-01', 'planned_to' => '2026-10-31', 'planned_preset' => 'this_month']));
        $sets = $manager->list('client');

        self::assertNull($manager->current($this->set, $sets), 'no filter, no set in use');

        $manager->apply($this->request(), $this->set, (int) $late->getId());
        self::assertSame($late->getId(), $manager->current($this->set, $sets)?->getId());

        // Another day: the dates of the period moved, the set is still the one in use.
        $manager->apply($this->request(), $this->set, (int) $month->getId(), new \DateTimeImmutable('2026-12-02'));
        self::assertSame($month->getId(), $manager->current($this->set, $sets)?->getId());

        // A criterion changed: no set is in use.
        $request = Request::create('/client/', 'GET', $this->form(['name_op' => 'contains', 'name_value' => 'Bob']));
        $request->setSession($this->session);
        $this->state->handleRequest($request);
        self::assertNull($manager->current($this->set, $sets));
    }

    public function testASetIsDeleted(): void
    {
        $manager = $this->manager();
        $saved = $manager->save($this->set, 'Late', $this->form(['name_op' => 'contains', 'name_value' => 'A']));

        self::assertSame('Late', $manager->delete('client', (int) $saved->getId())->getName());
        self::assertSame([], $manager->list('client'));
    }
}
