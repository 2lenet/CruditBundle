<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\ShowBrick\ShowConfig;
use Lle\CruditBundle\Brick\ShowBrick\ShowFactory;
use Lle\CruditBundle\Brick\ShowBrick\ShowStatus;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\FieldView;
use Lle\CruditBundle\Exception\CruditException;
use Lle\CruditBundle\Registry\FieldRegistry;
use Lle\CruditBundle\Resolver\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The sections of the record (lot 3.3.a): ids, groups refused by role, number of groups opened, columns.
 */
class ShowFactoryTest extends TestCase
{
    public function testTheIdsOfTheSectionsAreCleanedAndUnique(): void
    {
        $ids = $this->call('sectionIds', [['a b', 'a-b', 'Général', '!!!', ''], 'CLIENT']);

        self::assertSame([
            'a b' => 'crudit-show-client-a-b',
            'a-b' => 'crudit-show-client-a-b-2',
            'Général' => 'crudit-show-client-general',
            '!!!' => 'crudit-show-client-section-4',
            '' => 'crudit-show-client-section-5',
        ], $ids);
    }

    public function testTheIdsWithoutNameOfTheCrud(): void
    {
        self::assertSame(['Général' => 'crudit-show-general'], $this->call('sectionIds', [['Général']]));
    }

    public function testAGroupRefusedByRoleStillCountsAsOpened(): void
    {
        $sections = $this->sections([
            'one' => [],
            'two' => [$this->view('b')],
            'three' => [$this->view('c')],
        ], opened: 2);

        self::assertSame(['two', 'three'], array_column($sections, 'title'));
        self::assertSame([true, false], array_column($sections, 'open'));
    }

    public function testAGroupWithoutFieldIsNotShown(): void
    {
        self::assertSame([], $this->sections(['state' => []]));
    }

    public function testTheStatusIsLeftOutOfTheGroupsAndTheHighlights(): void
    {
        $status = Field::new('status', 'workflow');
        $name = Field::new('name');
        $fields = [$name, 'one' => [$status, $name], 'two' => [$status]];

        $result = $this->withoutStatus($fields, $status);

        self::assertSame($name, $result[0]);
        self::assertSame([$name], $result['one']);
        self::assertSame([], $result['two']);
        self::assertSame([$name], array_values($this->withoutStatus([$status, $name], $status)));
    }

    public function testTheStatusStaysWithoutHeader(): void
    {
        $status = Field::new('status', 'workflow');
        $fields = [$status, 'one' => [$status]];

        self::assertSame($fields, $this->withoutStatus($fields, $status, header: false));
        self::assertSame($fields, $this->withoutStatus($fields, null));
    }

    public function testTheCardWithoutTitleDoesNotCountForTheColumns(): void
    {
        $plain = ['title' => null];
        $group = ['title' => 'group'];

        self::assertSame(1, $this->call('getSectionColumns', [[$plain, $group]]));
        self::assertSame(1, $this->call('getSectionColumns', [[]]));
        self::assertSame(2, $this->call('getSectionColumns', [[$plain, $group, $group]]));
    }

    public function testTheFieldsOfNoGroupMakeACardWithoutTitle(): void
    {
        $sections = $this->sections([$this->view('a'), 'group' => [$this->view('b')], $this->view('c')]);

        self::assertSame([null, 'group', null], array_column($sections, 'title'));
        self::assertSame([null, 'crudit-show-group', null], array_column($sections, 'id'));
    }

    public function testTheColumnsOfASection(): void
    {
        $sections = $this->sections(['one' => [$this->view('a')], 'two' => [$this->view('b')]], sectionColumns: ['two' => 3]);

        self::assertSame([2, 3], array_column($sections, 'columns'));
    }

    public function testInvalidColumns(): void
    {
        $this->expectException(CruditException::class);
        $this->expectExceptionMessage('getShowSectionColumns()[two]');

        $this->sections(['two' => [$this->view('b')]], sectionColumns: ['two' => 5]);
    }

    /**
     * @param array<int|string, FieldView|FieldView[]> $fields
     * @param array<string, mixed> $sectionColumns
     * @return list<array<string, mixed>>
     */
    private function sections(array $fields, ?int $opened = null, array $sectionColumns = []): array
    {
        $config = $this->createStub(CrudConfigInterface::class);
        $config->method('getShowColumns')->willReturn(2);
        $config->method('getShowSectionColumns')->willReturn($sectionColumns);
        $config->method('getShowWideSections')->willReturn([]);
        $config->method('getShowNumberFieldGroupsOpened')->willReturn($opened);

        return $this->call('getSections', [$fields, $config]);
    }

    /**
     * @param array<int|string, Field|Field[]> $fields
     * @return array<int|string, Field|Field[]>
     */
    private function withoutStatus(array $fields, ?Field $status, bool $header = true): array
    {
        $showStatus = $this->createStub(ShowStatus::class);
        $showStatus->method('hasHeader')->willReturn($header);
        $showStatus->method('find')->willReturn($status);

        $config = ShowConfig::new()->setCrudConfig($this->createStub(CrudConfigInterface::class))->setPageKey(CrudConfigInterface::SHOW);

        return $this->call('withoutStatus', [$config, $fields], $showStatus);
    }

    private function view(string $name): FieldView
    {
        return (new FieldView(Field::new($name), $name))->setOptions([]);
    }

    /**
     * @param list<mixed> $arguments
     */
    private function call(string $method, array $arguments, ?ShowStatus $showStatus = null): mixed
    {
        $factory = new ShowFactory(
            $this->createStub(ResourceResolver::class),
            new RequestStack(),
            $showStatus ?? $this->createStub(ShowStatus::class),
            new FieldRegistry([]),
        );

        return (new \ReflectionMethod($factory, $method))->invoke($factory, ...$arguments);
    }
}
