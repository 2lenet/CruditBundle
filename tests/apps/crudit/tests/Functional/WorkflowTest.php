<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Intervention;
use Doctrine\ORM\EntityManagerInterface;
use Lle\CruditBundle\Workflow\WorkflowCounter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Lot 3.4.c: the state of an element in a workflow (field/workflow.html.twig): a Status in the tone of its place, the
 * button of a menu of the states the allowed transitions lead to (guards and roles), the confirmation in the dialog
 * of the layout, the transition sent in POST with its CSRF token (JSON: the new field and the message), the counters.
 */
class WorkflowTest extends AppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        // INT-1 has no technician: the guard of "plan" refuses it
        foreach (['to_plan', 'to_plan', 'planned', 'in_progress', 'done', 'cancelled'] as $i => $status) {
            $em->persist((new Intervention())->setReference('INT-' . ($i + 1))->setTechnician(0 === $i ? null : 'Camille')->setStatus($status));
        }
        $em->flush();
    }

    public function testTheStatesOfTheList(): void
    {
        $crawler = $this->client->request('GET', '/intervention/');
        self::assertResponseIsSuccessful();

        // INT-1: "plan" refused by its guard, "cancel" left, destructive
        $field = $this->field($crawler, 'INT-1');
        $trigger = $field->filter('button.crudit-workflow__trigger');
        self::assertSame('To plan, change the status of INT-1', $trigger->text());
        self::assertSame('false', $trigger->attr('aria-expanded'));
        self::assertSame('crudit-ui-status crudit-ui-tone--warning crudit-ui-status--small', $trigger->filter('.crudit-ui-status')->attr('class'));
        self::assertSame('New status for INT-1', $field->filter('.crudit-ui-menu')->attr('aria-label'));
        $item = $field->filter('.crudit-ui-menu__item');
        self::assertCount(1, $item);
        self::assertSame(['crudit-confirm', 'show-modal', 'cancel'], [$item->attr('commandfor'), $item->attr('command'), $item->attr('data-transition')]);
        self::assertSame('INT-1: move to “Cancelled”?', $item->attr('data-confirm-text'));
        self::assertSame('Confirm', $item->attr('data-confirm-label'));
        self::assertNotNull($item->attr('data-confirm-danger'));
        self::assertNotNull($item->attr('data-confirm-dispatch'));
        self::assertSame('crudit-ui-status crudit-ui-tone--gray crudit-ui-status--small', $item->filter('.crudit-ui-status')->attr('class'));

        // The data of the controller workflow, on a <div> (the Dropdown is a <div>)
        self::assertSame('div', $field->nodeName());
        self::assertSame('2lenet--crudit-bundle--workflow', $field->attr('data-controller'));
        self::assertSame('/intervention/workflow/' . $this->idOf('INT-1'), $field->attr('data-2lenet--crudit-bundle--workflow-url-value'));
        self::assertNotEmpty($field->attr('data-2lenet--crudit-bundle--workflow-token-value'));
        self::assertSame(['intervention', 'status'], [$field->attr('data-2lenet--crudit-bundle--workflow-name-value'), $field->attr('data-2lenet--crudit-bundle--workflow-field-value')]);

        // INT-2 has a technician: "plan" too, not destructive
        $items = $this->field($crawler, 'INT-2')->filter('.crudit-ui-menu__item');
        self::assertSame(['Planned', 'Cancelled'], $items->each(static fn (Crawler $item): string => $item->text()));
        self::assertNull($items->first()->attr('data-confirm-danger'));

        // INT-5 is done: "archive" refused by its role, a status alone; INT-6 has no transition. Not the colour alone.
        $done = $this->field($crawler, 'INT-5');
        self::assertSame('span', $done->nodeName());
        self::assertCount(0, $done->filter('button'));
        self::assertNull($done->attr('data-controller'));
        self::assertSame('Done', $done->filter('.crudit-ui-status.crudit-ui-tone--success')->text());
        self::assertSame('Cancelled', $this->field($crawler, 'INT-6')->filter('.crudit-ui-status.crudit-ui-tone--gray')->text());

        // Nothing of 1.x: no Bootstrap, no dialog per line, no class per state
        // Twice per line: in the table and in its card of the phones
        $fields = $crawler->filter('.crudit-value--workflow');
        self::assertCount(12, $fields);
        self::assertCount(0, $fields->filter('[data-bs-toggle], .badge, .dropdown-menu, .modal, [class*="crudit-wf-"]'));
        // No id: the table renders the HTML of a cell twice (its line and its card)
        self::assertCount(0, $fields->filter('[id]'));
    }

    public function testTheNameOfTheElementIsEscaped(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new Intervention())->setReference("O'Neil & <b>Co</b>")->setTechnician('Camille'));
        $em->flush();

        $crawler = $this->client->request('GET', '/intervention/');
        $field = $this->field($crawler, "O'Neil & <b>Co</b>");

        self::assertSame("To plan, change the status of O'Neil & <b>Co</b>", $field->filter('button.crudit-workflow__trigger')->text());
        self::assertSame("O'Neil & <b>Co</b>: move to “Planned”?", $field->filter('.crudit-ui-menu__item')->first()->attr('data-confirm-text'));
        self::assertCount(0, $field->filter('b'));
    }

    public function testATransitionAskedInJson(): void
    {
        $id = $this->idOf('INT-2');
        $crawler = $this->client->request('GET', '/intervention/');

        $this->client->request('POST', '/intervention/workflow/' . $id, [
            '_token' => $this->token($crawler, 'INT-2'),
            'transition' => 'plan',
            'name' => 'intervention',
            'field' => 'status',
        ], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        /** @var array{status: string, message: string, html: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('ok', $data['status']);
        self::assertSame('INT-2: status changed from “To plan” to “Planned”.', $data['message']);
        $field = (new Crawler($data['html']))->filter('.crudit-value--workflow');
        self::assertSame('Planned, change the status of INT-2', $field->filter('button.crudit-workflow__trigger')->text());
        self::assertSame(['In progress', 'Cancelled'], $field->filter('.crudit-ui-menu__item')->each(static fn (Crawler $item): string => $item->text()));
        self::assertSame('planned', $this->statusOf('INT-2'));
    }

    public function testATransitionWithoutJavaScriptGoesBackWithAMessage(): void
    {
        $crawler = $this->client->request('GET', '/intervention/');
        $this->client->request('POST', '/intervention/workflow/' . $this->idOf('INT-3'), [
            '_token' => $this->token($crawler, 'INT-3'),
            'transition' => 'start',
            'name' => 'intervention',
        ]);

        self::assertResponseRedirects();
        self::assertSame('in_progress', $this->statusOf('INT-3'));
        $this->client->followRedirect();
        self::assertAnySelectorTextContains('.crudit-ui-toast', 'INT-3: status changed from “Planned” to “In progress”.');
    }

    public function testWhatIsRefused(): void
    {
        $crawler = $this->client->request('GET', '/intervention/');
        $post = function (string $reference, string $transition, ?string $token = null) use ($crawler): array {
            $this->client->request('POST', '/intervention/workflow/' . $this->idOf($reference), [
                '_token' => $token ?? $this->token($crawler, $reference),
                'transition' => $transition,
                'name' => 'intervention',
            ], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array{status: string, message: string} $data */
            $data = json_decode((string) $this->client->getResponse()->getContent(), true);

            return [$this->client->getResponse()->getStatusCode(), $data['message']];
        };

        // A GET does not change anything
        $this->client->request('GET', '/intervention/workflow/' . $this->idOf('INT-2') . '?transition=plan&name=intervention');
        self::assertResponseStatusCodeSame(405);

        self::assertSame([403, 'The page has expired: reload it, then try again.'], $post('INT-2', 'plan', 'wrong'));
        // The token of an element is not the one of another
        self::assertSame(403, $post('INT-2', 'plan', $this->token($crawler, 'INT-3'))[0]);
        // The role is checked before the workflow: archive is refused to everybody (AllowAllVoter)
        self::assertSame([403, 'You are not allowed to make this change of status.'], $post('INT-2', 'archive'));
        // Refused by the guard (no technician), or not from this state
        self::assertSame([409, 'This change of status is no longer possible: the status has not changed.'], $post('INT-1', 'plan'));
        self::assertSame(409, $post('INT-3', 'finish')[0]);
        self::assertSame(['to_plan', 'to_plan', 'planned'], [$this->statusOf('INT-1'), $this->statusOf('INT-2'), $this->statusOf('INT-3')]);

        $this->client->request('POST', '/intervention/workflow/999', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testTheRecordShowsTheStatus(): void
    {
        $crawler = $this->client->request('GET', '/intervention/show/' . $this->idOf('INT-4'));

        self::assertSame('In progress, change the status of INT-4', $crawler->filter('.crudit-value--workflow button.crudit-workflow__trigger')->text());
    }

    public function testTheCounters(): void
    {
        $crawler = $this->client->request('GET', '/intervention/counters');
        self::assertResponseIsSuccessful();

        $counters = $crawler->filter('ul.crudit-ui-counters[aria-label="Interventions by state"] > li > .crudit-ui-counter');
        // In the order of the places, a state without element counts 0
        self::assertSame(['To plan 2', 'Planned 1', 'In progress 1', 'Done 1', 'Cancelled 1', 'Archived 0'], $counters->each(static fn (Crawler $counter): string => $counter->filter('.crudit-ui-status')->text() . ' ' . $counter->filter('.crudit-ui-counter__value')->text()));
        // The status of each state, in its tone
        self::assertSame(['warning', 'info', 'teal', 'success', 'gray', 'neutral'], $counters->each(static fn (Crawler $counter): string => (string) preg_replace('/^.*crudit-ui-tone--(\S+).*$/', '$1', (string) $counter->filter('.crudit-ui-status')->attr('class'))));
        $link = '/intervention/?filter_intervention_status_op=in&filter_intervention_status_value=to_plan&intervention_offset=0';
        self::assertSame($link, $counters->first()->attr('href'));

        // The link opens the list filtered on the state
        $list = $this->client->request('GET', $link);
        self::assertSame(['INT-1', 'INT-2'], $list->filter('tbody tr th[scope=row]')->each(static fn (Crawler $cell): string => trim($cell->text())));

        // In PHP: part of the elements (a QueryBuilder); without a CRUD, no link
        /** @var WorkflowCounter $counter */
        $counter = static::getContainer()->get(WorkflowCounter::class);
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $withTechnician = $em->createQueryBuilder()->select('i')->from(Intervention::class, 'i')->where('i.technician IS NOT NULL')->orderBy('i.reference');
        self::assertSame([1, 1, 1, 1, 1, 0], array_map(static fn ($count): int => $count->count, $counter->count(Intervention::class, null, $withTechnician)));
        self::assertNull($counter->count(Intervention::class)[0]->url);
    }

    private function field(Crawler $crawler, string $reference): Crawler
    {
        $row = $crawler->filter('tbody tr')->reduce(static fn (Crawler $row): bool => in_array($reference, $row->filter('td, th')->each(static fn (Crawler $cell): string => trim($cell->text())), true));
        self::assertCount(1, $row, $reference);

        return $row->filter('.crudit-value--workflow');
    }

    private function token(Crawler $crawler, string $reference): string
    {
        return (string) $this->field($crawler, $reference)->attr('data-2lenet--crudit-bundle--workflow-token-value');
    }

    private function idOf(string $reference): int
    {
        return (int) $this->intervention($reference)->getId();
    }

    private function statusOf(string $reference): string
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $this->intervention($reference)->getStatus();
    }

    private function intervention(string $reference): Intervention
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $intervention = $em->getRepository(Intervention::class)->findOneBy(['reference' => $reference]);
        self::assertNotNull($intervention, $reference);

        return $intervention;
    }
}
