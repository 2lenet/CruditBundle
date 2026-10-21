<?php

declare(strict_types=1);

namespace phpunit\Unit\Builder;

use Lle\CruditBundle\Builder\MenuBuilder;
use Lle\CruditBundle\Contracts\LayoutElementInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Layout\CategoryElement;
use Lle\CruditBundle\Dto\Layout\ExternalLinkElement;
use Lle\CruditBundle\Dto\Layout\HeaderElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Registry\MenuRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The menu shown to the user: elements not granted, empty sub-menus, headings left alone and needless separators are
 * removed, without changing the elements of the providers.
 */
class MenuBuilderTest extends TestCase
{
    public function testAllGranted(): void
    {
        $elements = [
            LinkElement::new('home', Path::new('home')),
            HorizontalSeparatorElement::new(),
            CategoryElement::new('operations'),
            LinkElement::new('clients', Path::new('clients')),
        ];

        self::assertSame(['home', '---', 'operations', 'clients'], $this->labels($this->build($elements)));
    }

    public function testElementNotGrantedIsRemoved(): void
    {
        $elements = [
            LinkElement::new('home', Path::new('home')),
            LinkElement::new('users', Path::new('users'), role: 'ROLE_ADMIN'),
            ExternalLinkElement::new('doc', 'https://example.org', role: 'ROLE_ADMIN'),
        ];

        self::assertSame(['home'], $this->labels($this->build($elements)));
    }

    public function testChildrenNotGrantedAreRemovedFromACopy(): void
    {
        $billing = LinkElement::new('billing', null);
        $billing->add(LinkElement::new('invoices', Path::new('invoices')));
        $billing->add(LinkElement::new('credits', Path::new('credits'), role: 'ROLE_ADMIN'));

        $menu = $this->build([$billing]);

        self::assertSame(['billing' => ['invoices']], $this->labels($menu));
        self::assertNotSame($billing, $menu[0]);
        self::assertCount(2, $billing->getChildren(), 'the element of the provider is not changed');
    }

    public function testParentWithoutGrantedChildIsRemoved(): void
    {
        $billing = LinkElement::new('billing', null);
        $billing->add(LinkElement::new('invoices', Path::new('invoices'), role: 'ROLE_ADMIN'));

        self::assertSame(['home'], $this->labels($this->build([LinkElement::new('home', Path::new('home')), $billing])));
    }

    public function testHeadingLeftAloneIsRemoved(): void
    {
        $elements = [
            LinkElement::new('home', Path::new('home')),
            CategoryElement::new('operations'),
            LinkElement::new('clients', Path::new('clients'), role: 'ROLE_ADMIN'),
            HeaderElement::new('help'),
            HorizontalSeparatorElement::new(),
            TitleElement::new('administration'),
            LinkElement::new('settings', Path::new('settings')),
            CategoryElement::new('end'),
        ];

        self::assertSame(['home', '---', 'administration', 'settings'], $this->labels($this->build($elements)));
    }

    public function testTitleWithChildren(): void
    {
        $title = TitleElement::new('administration');
        $title->addChild(LinkElement::new('users', Path::new('users'), role: 'ROLE_ADMIN'));
        $title->addChild(LinkElement::new('settings', Path::new('settings')));

        self::assertSame(['administration' => ['settings']], $this->labels($this->build([$title])));
    }

    /**
     * A title with children labels them only: without any of them, it goes, even if items follow it.
     */
    public function testTitleWithoutGrantedChildIsRemoved(): void
    {
        $title = TitleElement::new('reports');
        $title->addChild(LinkElement::new('sales', Path::new('sales'), role: 'ROLE_ADMIN'));

        $menu = $this->build([$title, LinkElement::new('home', Path::new('home'))]);

        self::assertSame(['home'], $this->labels($menu));
    }

    /**
     * Title > sub-menu > refused link: the sub-menu is a copy without the link, so the title must be a copy too, even
     * if it keeps one child.
     */
    public function testRefusedLinkInASubMenuOfATitle(): void
    {
        $billing = LinkElement::new('billing', null);
        $billing->add(LinkElement::new('invoices', Path::new('invoices')));
        $billing->add(LinkElement::new('credits', Path::new('credits'), role: 'ROLE_ADMIN'));
        $title = TitleElement::new('administration');
        $title->addChild($billing);

        $menu = $this->build([$title]);

        self::assertSame(['administration' => ['billing' => ['invoices']]], $this->labels($menu));
        self::assertCount(2, $billing->getChildren(), 'the element of the provider is not changed');
    }

    public function testSeparators(): void
    {
        $elements = [
            HorizontalSeparatorElement::new(),
            LinkElement::new('home', Path::new('home')),
            HorizontalSeparatorElement::new(),
            HorizontalSeparatorElement::new(),
            LinkElement::new('clients', Path::new('clients')),
            HorizontalSeparatorElement::new(),
            LinkElement::new('users', Path::new('users'), role: 'ROLE_ADMIN'),
            HorizontalSeparatorElement::new(),
        ];

        self::assertSame(['home', '---', 'clients'], $this->labels($this->build($elements)));
    }

    public function testUserWithoutRoles(): void
    {
        $elements = [
            CategoryElement::new('operations'),
            LinkElement::new('clients', Path::new('clients')),
            HorizontalSeparatorElement::new(),
        ];

        self::assertSame([], $this->build($elements, []));
    }

    /**
     * @param LayoutElementInterface[] $elements
     * @param string[]                 $roles    roles of the user
     *
     * @return LayoutElementInterface[]
     */
    private function build(array $elements, array $roles = ['ROLE_USER']): array
    {
        $provider = new class ($elements) implements MenuProviderInterface {
            /** @param LayoutElementInterface[] $elements */
            public function __construct(private array $elements)
            {
            }

            public function getMenuEntry(): iterable
            {
                return $this->elements;
            }
        };

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(
            static fn (mixed $role): bool => \in_array($role, $roles, true),
        );

        return (new MenuBuilder(new MenuRegistry([$provider]), $authorizationChecker))->build('menu_side');
    }

    /**
     * Labels of the elements ('---' for a separator), the children of an element under its label.
     *
     * @param LayoutElementInterface[] $elements
     *
     * @return array<int|string, mixed>
     */
    private function labels(array $elements): array
    {
        $labels = [];
        foreach ($elements as $element) {
            $label = method_exists($element, 'getLibelle') ? $element->getLibelle() : '---';
            if ($element->getChildren() !== []) {
                $labels[$label] = $this->labels($element->getChildren());
            } else {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
