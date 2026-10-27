<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Brick\LinksBrick;

use Lle\CruditBundle\Brick\AbstractBrickConfig;
use Lle\CruditBundle\Dto\Action\AbstractAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Symfony\Component\HttpFoundation\Request;

class LinksConfig extends AbstractBrickConfig
{
    protected array $actions = [];
    protected bool $back = false;

    public static function new(array $options = []): self
    {
        return new self($options);
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function addAction(AbstractAction $action): self
    {
        $this->actions[] = $action;

        return $this;
    }

    public function addBack(): self
    {
        $this->back = true;

        return $this;
    }

    public function hasBack(): bool
    {
        return $this->back;
    }

    /** @return AbstractAction[] */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): self
    {
        // The batch actions are for the list (ListBrick), not for the header
        $this->actions = array_filter($actions, static fn (AbstractAction $a): bool => !($a instanceof ListAction && $a->isBatch()));

        return $this;
    }

    public function getConfig(Request $request): array
    {
        return [
            'title' => $this->options['title'] ?? "",
            'translation_domain' => $this->getCrudConfig()->getTranslationDomain(),
        ];
    }
}
