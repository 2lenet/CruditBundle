<?php

declare(strict_types=1);

namespace phpunit\Unit\Icon;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\PrintAction;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Icon\ActionIconFinder;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * The Iconify names of the actions of the CrudConfig (list, lines, record, the DropdownAction too), for
 * ux:icons:lock; a CrudConfig that fails is logged and skipped.
 */
class ActionIconFinderTest extends TestCase
{
    public function testIcons(): void
    {
        $config = $this->config(
            [
                ListAction::new('add', Path::new('add'), Icon::new('add')),
                ListAction::new('import', Path::new('import'), Icon::new('lucide:upload')),
                DropdownAction::new('reports', ['sales' => [ListAction::new('month', Path::new('month'), Icon::new('fa7-solid:chart-line'))]], Icon::new('lucide:file')),
                PrintAction::new(),
            ],
            [ItemAction::new('note', Path::new('note'), Icon::new('lucide:upload'))],
            [ItemAction::new('mail', Path::new('mail'), Icon::new('fa7-solid:envelope')), ItemAction::new('none', Path::new('none'))],
        );

        self::assertSame(
            ['lucide:upload', 'lucide:file', 'fa7-solid:chart-line', 'fa7-solid:envelope'],
            (new ActionIconFinder([$config]))->icons(),
        );
    }

    public function testFailingConfigIsSkipped(): void
    {
        $failing = $this->createMock(CrudConfigInterface::class);
        $failing->method('getListActions')->willThrowException(new \RuntimeException('no database'));
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->messages[] = $level . ' ' . $message;
            }
        };
        $config = $this->config([ListAction::new('import', Path::new('import'), Icon::new('lucide:upload'))], [], []);

        self::assertSame(['lucide:upload'], (new ActionIconFinder([$failing, $config], $logger))->icons());
        self::assertSame(['warning The icons of the actions of {config} cannot be found: {message}'], $logger->messages);
    }

    /**
     * @param list<mixed> $list
     * @param list<mixed> $item
     * @param list<mixed> $show
     */
    private function config(array $list, array $item, array $show): CrudConfigInterface
    {
        $config = $this->createMock(CrudConfigInterface::class);
        $config->method('getListActions')->willReturn($list);
        $config->method('getItemActions')->willReturn($item);
        $config->method('getShowActions')->willReturn($show);

        return $config;
    }
}
