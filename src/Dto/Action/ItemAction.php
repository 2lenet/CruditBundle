<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Action\ActionState;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;

class ItemAction extends AbstractAction
{
    protected ?object $resource = null;

    public static function new(string $label, Path $path, ?Icon $icon = null): static
    {
        return (new static($label))
            ->setPath($path)
            ->setIcon($icon)
            ->setHideLabel(false);
    }

    /** exists only when rendering */
    public function getResource(): ?object
    {
        return $this->resource;
    }

    public function setResource(?object $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * The reason given by a method of the resource (canEdit, canDelete): true when it can be used, else its reason (a
     * string), else a generic reason.
     */
    protected function getCallbackReason(string $callback): ?string
    {
        if ($this->resource === null || !method_exists($this->resource, $callback)) {
            return null;
        }
        $can = $this->resource->{$callback}();

        return match (true) {
            $can === true => null,
            is_string($can) && $can !== '' => $can,
            default => ActionState::REASON_UNAVAILABLE,
        };
    }

    protected function getReasonSubject(): mixed
    {
        return $this->resource;
    }

    public function isBatch(): bool
    {
        return false;
    }
}
