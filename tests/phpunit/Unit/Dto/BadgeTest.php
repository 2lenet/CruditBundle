<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto;

use Lle\CruditBundle\Dto\Badge;
use PHPUnit\Framework\TestCase;

/**
 * A badge has a tone (neutral by default, not checked: any tone of CruditUiBundle or of the project) and an optional
 * text for assistive technologies. The Bootstrap classes of 1.x are not converted.
 */
class BadgeTest extends TestCase
{
    public function testDefaults(): void
    {
        $badge = Badge::new('12');

        self::assertSame('12', $badge->getLibelle());
        self::assertSame('neutral', $badge->getTone());
        self::assertNull($badge->getLabel());
    }

    public function testToneAndLabel(): void
    {
        $badge = Badge::new('3', 'danger', 'menu.late_interventions');

        self::assertSame('danger', $badge->getTone());
        self::assertSame('menu.late_interventions', $badge->getLabel());
    }

    public function testAnyToneOfCruditUiBundleOrOfTheProject(): void
    {
        self::assertSame('primary', Badge::new('12', 'primary')->getTone());
        self::assertSame('project-colour', Badge::new('12', 'project-colour')->getTone());
    }

    public function testABootstrapClassOf1xIsKeptAsIs(): void
    {
        // Not converted: an unknown tone, shown in the neutral colours (UPGRADE: write 'danger')
        self::assertSame('bg-danger', Badge::new('3', 'bg-danger')->getTone());
        self::assertSame('badge-project', Badge::new('3', 'badge-project')->getTone());
    }
}
