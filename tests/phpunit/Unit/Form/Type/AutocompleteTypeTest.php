<?php

declare(strict_types=1);

namespace phpunit\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lle\CruditBundle\Form\Type\AutocompleteType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;

/**
 * The choices made of a list with search (AutocompleteType::buildView()): the hidden input of the list is made from
 * them, a choice left out would be removed when the form is saved.
 */
class AutocompleteTypeTest extends TestCase
{
    public function testAnEntityWithoutToStringIsShownWithItsId(): void
    {
        $entities = [3 => $this->stringable(3, 'Alice'), 7 => $this->notStringable(7)];

        $view = $this->buildView('7,3', true, $entities);

        self::assertSame(
            [['value' => '7', 'label' => '7'], ['value' => '3', 'label' => 'Alice']],
            $view->vars['combobox_selected'],
        );
    }

    public function testOneEntityWithoutToString(): void
    {
        $view = $this->buildView('7', false, [7 => $this->notStringable(7)]);

        self::assertSame([['value' => '7', 'label' => '7']], $view->vars['combobox_selected']);
    }

    public function testAnIdWithoutEntityStaysChosen(): void
    {
        // hidden by a Doctrine filter, or deleted: the form refuses it when saved, instead of removing it silently
        self::assertSame(
            [['value' => '3', 'label' => 'Alice'], ['value' => '9', 'label' => '9']],
            $this->buildView('3,9', true, [3 => $this->stringable(3, 'Alice')])->vars['combobox_selected'],
        );
        self::assertSame([['value' => '9', 'label' => '9']], $this->buildView('9', false, [])->vars['combobox_selected']);
    }

    public function testTheSameIdTwiceIsOneChoice(): void
    {
        self::assertSame(
            [['value' => '3', 'label' => 'Alice']],
            $this->buildView('3,3', true, [3 => $this->stringable(3, 'Alice')])->vars['combobox_selected'],
        );
    }

    /**
     * @param array<int, object> $entities
     */
    private function buildView(string $value, bool $multiple, array $entities): FormView
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturnCallback(fn (mixed $id) => $entities[(int) $id] ?? null);
        $repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => array_values(array_filter(
                array_map(fn (mixed $id) => $entities[(int) $id] ?? null, array_unique($criteria['id'])),
            )),
        );
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $view = new FormView();
        $view->vars['value'] = $value;
        (new AutocompleteType($this->createStub(RouterInterface::class), $em))->buildView(
            $view,
            $this->createStub(FormInterface::class),
            ['class' => 'App\Entity\Client', 'route' => null, 'url' => '/client/autocomplete', 'multiple' => $multiple],
        );

        return $view;
    }

    private function stringable(int $id, string $name): object
    {
        return new class ($id, $name) implements \Stringable {
            public function __construct(private int $id, private string $name)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function __toString(): string
            {
                return $this->name;
            }
        };
    }

    private function notStringable(int $id): object
    {
        return new class ($id) {
            public function __construct(private int $id)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
