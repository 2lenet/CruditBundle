<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

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
            'Général' => 'crudit-show-client-General',
            '!!!' => 'crudit-show-client-section-4',
            '' => 'crudit-show-client-section-5',
        ], $ids);
    }

    public function testTheIdsWithoutNameOfTheCrud(): void
    {
        self::assertSame(['Général' => 'crudit-show-General'], $this->call('sectionIds', [['Général']]));
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

    public function testAGroupOfTheStatusAloneIsNotShown(): void
    {
        self::assertSame([], $this->sections(['state' => []]));
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

    private function view(string $name): FieldView
    {
        return (new FieldView(Field::new($name), $name))->setOptions([]);
    }

    /**
     * @param list<mixed> $arguments
     */
    private function call(string $method, array $arguments): mixed
    {
        $factory = new ShowFactory(
            $this->createStub(ResourceResolver::class),
            new RequestStack(),
            $this->createStub(ShowStatus::class),
            new FieldRegistry([]),
        );

        return (new \ReflectionMethod($factory, $method))->invoke($factory, ...$arguments);
    }
}
