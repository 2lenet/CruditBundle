<?php

declare(strict_types=1);

namespace phpunit\Unit\Builder;

use Lle\CruditBundle\Builder\PagerViewBuilder;
use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Datasource\DatasourceParams;
use Lle\CruditBundle\Dto\BrickView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The props of the Pager of CruditUiBundle for a list: page and rows per page from the datasource parameters, the
 * parameters of the 1.x (`<list>_offset`, `<list>_limit`), the choices of the rows per page.
 */
class PagerViewBuilderTest extends TestCase
{
    public function testThePageAndTheRowsComeFromTheDatasourceParameters(): void
    {
        $params = (new DatasourceParams(30, 60))->setCount(131);

        $pager = $this->build($params, ['choices_nb_items' => [10, 30, 50, 100]], '/clients?client_offset=60&q=lyon');

        self::assertSame(131, $pager['total']);
        self::assertSame(3, $pager['page']);
        self::assertSame(30, $pager['perPage']);
        self::assertSame([10, 30, 50, 100], $pager['perPageChoices']);
        self::assertSame('offset', $pager['mode']);
        self::assertSame('client_offset', $pager['pageParam']);
        self::assertSame('client_limit', $pager['limitParam']);
        self::assertSame('/clients', $pager['path']);
        self::assertSame(['client_offset' => '60', 'q' => 'lyon'], $pager['query'], 'the whole query: the Pager replaces the page and the limit itself');
        self::assertSame('brick1_pager', $pager['id']);
    }

    public function testTheLabelIsTheTitleWithoutItsFirstCapital(): void
    {
        $params = (new DatasourceParams())->setCount(5);

        self::assertSame('clients', $this->build($params, ['title' => 'Clients'])['label']);
        self::assertSame('équipes de Lyon', $this->build($params, ['title' => 'Équipes de Lyon'])['label'], 'only the first letter');
        self::assertSame('PDF reçus', $this->build($params, ['title' => 'PDF reçus'])['label'], 'an acronym keeps its capitals');
        self::assertSame('client', $this->build($params, ['title' => null])['label'], 'without title, the name of the list');
    }

    public function testTheListCanForbidTheChoiceOfTheRowsPerPage(): void
    {
        $params = (new DatasourceParams(10))->setCount(131);

        self::assertSame([], $this->build($params, ['choices_nb_items' => [10, 30], 'canModifyNbEntityPerPage' => false])['perPageChoices'], 'the sublist');
        self::assertSame([], $this->build($params, ['choices_nb_items' => []])['perPageChoices']);
    }

    public function testTheChoicesAreThoseOfTheListForThePagerToComplete(): void
    {
        $params = (new DatasourceParams(25))->setCount(131);

        self::assertSame([50, 10, 30], $this->build($params, ['choices_nb_items' => [50, 10, 30]])['perPageChoices'], 'the Pager adds the limit in use, sorts and drops the doubles');
    }

    public function testNothingWithoutDatasourceParametersOrName(): void
    {
        $view = new BrickView($this->brick());
        $view->setConfig(['name' => 'client']);
        self::assertNull($this->builder('/clients')->build($view));

        $view->setConfig(['name' => null, 'datasource_params' => new DatasourceParams()]);
        self::assertNull($this->builder('/clients')->build($view), 'no name, no "_offset" parameter');
    }

    public function testADedicatedKeyGivesTheLabelBeforeTheTitle(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => match ($id) {
            'crud.pager.label.client' => 'Clients actifs',
            'crud.title.list.client' => 'Liste des clients',
            default => $id,
        });
        $requests = new RequestStack();
        $requests->push(Request::create('/clients'));
        $builder = new PagerViewBuilder($requests, $translator);
        $params = (new DatasourceParams())->setCount(5);

        $view = new BrickView($this->brick());
        $view->setConfig(['name' => 'Client', 'title' => 'crud.title.list.client', 'datasource_params' => $params]);
        self::assertSame('clients actifs', $this->labelOf($builder, $view), 'the key of the pager');

        $view->setConfig(['name' => 'Other', 'title' => 'crud.title.list.client', 'datasource_params' => $params]);
        self::assertSame('liste des clients', $this->labelOf($builder, $view), 'no key of the pager: the title');

        $view->setConfig(['name' => 'Other', 'title' => 'crud.title.list.missing', 'datasource_params' => $params]);
        self::assertSame('other', $this->labelOf($builder, $view), 'a title that is not translated is not shown: the name');
    }

    public function testTheCurrentRequestIsRead(): void
    {
        $requests = new RequestStack();
        $requests->push(Request::create('/dashboard?other=1'));
        $requests->push(Request::create('/clients?client_offset=30&q=a'));
        $view = new BrickView($this->brick());
        $view->setConfig(['name' => 'client', 'datasource_params' => (new DatasourceParams())->setCount(131)]);

        $pager = (new PagerViewBuilder($requests, $this->translator()))->build($view);

        self::assertNotNull($pager);
        self::assertSame('/clients', $pager['path'], 'a list rendered in a sub-request has its own parameters');
        self::assertSame(['client_offset' => '30', 'q' => 'a'], $pager['query']);
    }

    private function labelOf(PagerViewBuilder $builder, BrickView $view): string
    {
        $pager = $builder->build($view);
        self::assertNotNull($pager);

        return (string) $pager['label'];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function build(DatasourceParams $params, array $config, string $request = '/clients'): array
    {
        $pager = $this->builder($request)->build($this->view($params, $config));
        self::assertNotNull($pager);

        return $pager;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function view(DatasourceParams $params, array $config): BrickView
    {
        $view = new BrickView($this->brick());
        $view->setConfig($config + ['name' => 'Client', 'title' => 'Clients', 'datasource_params' => $params, 'translation_domain' => 'app']);

        return $view;
    }

    private function brick(): BrickConfigInterface
    {
        $brick = $this->createStub(BrickConfigInterface::class);
        $brick->method('getId')->willReturn('brick1');
        $brick->method('getOptions')->willReturn([]);

        return $brick;
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id) => $id);

        return $translator;
    }

    private function builder(string $request): PagerViewBuilder
    {
        $requests = new RequestStack();
        $requests->push(Request::create($request));

        return new PagerViewBuilder($requests, $this->translator());
    }
}
