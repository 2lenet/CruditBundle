<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Extension;

use Lle\CruditBundle\Form\Extension\CollectionExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Collection (lot 3.2.c): the field of the position of a line (option sortable) and the name of a line (entry_label),
 * given to the form theme of CruditUiBundle.
 */
class CollectionExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [new CollectionExtension()];
    }

    public function testTheOptionsAreGivenToTheView(): void
    {
        $sortable = $this->factory->create(CollectionType::class, [], [
            'entry_type' => CollectionExtensionLineType::class,
            'allow_add' => true,
            'sortable' => 'position',
            'entry_label' => 'Piece %index%',
        ])->createView();
        $plain = $this->factory->create(CollectionType::class, [])->createView();

        self::assertSame('position', $sortable->vars['collection_sortable']);
        self::assertSame('Piece %index%', $sortable->vars['collection_entry_label']);
        self::assertNull($plain->vars['collection_sortable'], 'no order by default');
        self::assertNull($plain->vars['collection_entry_label'], '"Line %index%" of CruditUiBundle');
    }

    public function testTheFieldOfThePositionIsCheckedOnTheFirstLineWithoutPrototype(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('names the field "rank", which its entry type');
        $this->factory->create(CollectionType::class, [['part' => 'Pump', 'position' => 1]], [
            'entry_type' => CollectionExtensionLineType::class,
            'sortable' => 'rank',
        ])->createView();
    }

    public function testTheFieldOfThePositionMustBeHidden(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('which must be a HiddenType');
        $this->factory->create(CollectionType::class, [], [
            'entry_type' => CollectionExtensionLineType::class,
            'allow_add' => true,
            'sortable' => 'quantity',
        ])->createView();
    }

    public function testAnEmptyCollectionWithoutPrototypeHasNothingToCheck(): void
    {
        $view = $this->factory->create(CollectionType::class, [], ['entry_type' => TextType::class, 'sortable' => 'position'])->createView();

        self::assertSame('position', $view->vars['collection_sortable']);
    }
}

/**
 * A line of a collection in CollectionExtensionTest: a part, a quantity and the hidden field of its position.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class CollectionExtensionLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('part', TextType::class)
            ->add('quantity', IntegerType::class)
            ->add('position', HiddenType::class);
    }
}
