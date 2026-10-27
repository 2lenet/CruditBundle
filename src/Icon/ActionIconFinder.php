<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Icon;

use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\UX\Icons\IconFinderInterface;

/**
 * Icons of the actions for `ux:icons:lock` and the cache warmer of Symfony UX Icons, as MenuIconFinder for the menu:
 * the Iconify names (`prefix:name`) of the actions of the CrudConfig (list, lines, record), those of the DropdownAction
 * too. The logical names (`add`, `delete`) are found by the finder of CruditUiBundle.
 *
 * It runs without a request: a CrudConfig that fails there is logged and skipped, the command goes on.
 */
class ActionIconFinder implements IconFinderInterface
{
    /**
     * @param iterable<CrudConfigInterface> $configs
     */
    public function __construct(
        private iterable $configs,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @return list<string>
     */
    public function icons(): array
    {
        $icons = [];
        foreach ($this->configs as $config) {
            try {
                $actions = [...$config->getListActions(), ...$config->getItemActions(), ...$config->getShowActions()];
            } catch (\Throwable $exception) {
                $this->logger->warning('The icons of the actions of {config} cannot be found: {message}', [
                    'config' => $config::class,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
                continue;
            }
            $icons = [...$icons, ...$this->collect($actions)];
        }

        return array_values(array_unique($icons));
    }

    /**
     * @param iterable<mixed> $actions
     *
     * @return list<string>
     */
    private function collect(iterable $actions): array
    {
        $icons = [];
        foreach ($actions as $action) {
            if (is_array($action)) {
                // A group of a DropdownAction
                $icons = [...$icons, ...$this->collect($action)];
                continue;
            }
            if (!$action instanceof AbstractAction) {
                continue;
            }
            $icon = $action->getIcon()?->getIcon();
            if ($icon !== null && str_contains($icon, ':')) {
                $icons[] = $icon;
            }
            if ($action instanceof DropdownAction) {
                $icons = [...$icons, ...$this->collect($action->getActions())];
            }
        }

        return $icons;
    }
}
