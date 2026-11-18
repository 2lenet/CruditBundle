<?php

declare(strict_types=1);

namespace phpunit\Unit\Dto;

use Lle\CruditBundle\Dto\ShowAlert;
use PHPUnit\Framework\TestCase;

/**
 * The alert of a record (lot 3.3.a): a tone among info, warning and danger, a message and its parameters.
 */
class ShowAlertTest extends TestCase
{
    public function testTheTones(): void
    {
        $alerts = [ShowAlert::info('a'), ShowAlert::warning('b', ['%count%' => 5]), ShowAlert::danger('c')];

        self::assertSame(['info', 'warning', 'danger'], array_map(static fn (ShowAlert $alert): string => $alert->getTone(), $alerts));
        self::assertSame(['b', ['%count%' => 5]], [$alerts[1]->getMessage(), $alerts[1]->getParameters()]);
    }

    public function testAnotherToneIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The tone of a ShowAlert is one of info, warning, danger, "success" given.');

        new ShowAlert('success', 'alert.done');
    }
}
