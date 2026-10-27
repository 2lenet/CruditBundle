<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto;

use Lle\CruditBundle\Dto\Badge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A badge has a tone (neutral by default, not checked: any tone of CruditUiBundle or of the project) and an optional
 * text for assistive technologies. The Bootstrap classes of 1.x are deprecated and turned into their tone.
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function bootstrapClasses(): iterable
    {
        yield 'badge-danger' => ['badge-danger', 'danger'];
        yield 'bg-success' => ['bg-success', 'success'];
        yield 'badge-secondary' => ['badge-secondary', 'neutral'];
        yield 'bg-dark' => ['bg-dark', 'neutral'];
    }

    #[DataProvider('bootstrapClasses')]
    public function testBootstrapClassIsDeprecatedAndTurnedIntoItsTone(string $class, string $tone): void
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $badge = Badge::new('3', $class);
        } finally {
            restore_error_handler();
        }

        self::assertSame($tone, $badge->getTone());
        self::assertSame([\sprintf(
            'Since 2lenet/crudit-bundle 2.0: The tone "%s" of a badge (a Bootstrap class) is deprecated, use "%s".',
            $class,
            $tone,
        )], $deprecations);
    }

    public function testUnknownBootstrapLikeToneIsKept(): void
    {
        self::assertSame('badge-project', Badge::new('3', 'badge-project')->getTone());
    }
}
