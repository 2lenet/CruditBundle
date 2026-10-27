<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

class DeleteAction extends ItemAction
{
    public const CALLBACK = "canDelete";

    /**
     * The reason of setDisabledReason(), else the one of canDelete() of the resource.
     */
    public function getDisabledReason(): ?string
    {
        return parent::getDisabledReason() ?? $this->getCallbackReason(static::CALLBACK);
    }
}
