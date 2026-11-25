<?php

declare(strict_types=1);

namespace phpunit\Unit\Brick;

use Lle\CruditBundle\Brick\TabBrick\Tab;
use Lle\CruditBundle\Brick\TabBrick\TabConfig;
use Lle\CruditBundle\Brick\TemplateBrick\TemplateConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The tabs of a record (lot 3.3.b): ids from the labels, unique, given or computed; the counter.
 */
class TabConfigTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function labels(): iterable
    {
        yield 'the id of the 1.x' => ['tab.history', 'history'];
        yield 'accents and spaces' => ['Pièces jointes', 'pieces_jointes'];
        yield 'dots and capitals' => ['tab.Client.Contacts', 'client_contacts'];
        yield 'kept: - and _' => ['tab.sub-list_2', 'sub-list_2'];
        yield 'nothing left' => ['tab.!!!', 'tab'];
    }

    #[DataProvider('labels')]
    public function testTheIdOfALabel(string $label, string $id): void
    {
        self::assertSame($id, TabConfig::idOf($label));
    }

    public function testTheIdsAreUniqueAndCanBeGiven(): void
    {
        $config = TabConfig::new()
            ->add('tab.notes', TemplateConfig::new(['template' => 'notes.html.twig']))
            ->adds('Notes', [TemplateConfig::new(['template' => 'more.html.twig'])])
            ->add('tab.other', TemplateConfig::new(['template' => 'other.html.twig']), id: 'internal')
            ->addTab(Tab::new('tab.notes'));

        self::assertSame(['notes', 'notes_2', 'internal', 'notes_3'], array_map(fn (Tab $tab) => $tab->getId(), $config->getTabs()));
    }

    public function testTheTabsGivenToTheConstructorHaveTheirIds(): void
    {
        $config = TabConfig::new(['tabs' => [Tab::new('tab.parts'), Tab::new('tab.parts')]]);

        self::assertSame(['parts', 'parts_2'], array_map(fn (Tab $tab) => $tab->getId(), $config->getTabs()));
    }

    public function testTheCounterIsComputedOnTheRecord(): void
    {
        $config = TabConfig::new()->add('tab.parts', TemplateConfig::new(['template' => 'parts.html.twig']), count: fn (\stdClass $record): int => \count($record->parts));
        $tab = $config->getTabs()[0];

        self::assertSame(3, $tab->getCount((object) ['parts' => [1, 2, 3]]));
        self::assertNull($tab->getCount(null), 'no record, no counter');
        self::assertNull(Tab::new('tab.none')->getCount((object) []), 'no counter set');
    }
}
