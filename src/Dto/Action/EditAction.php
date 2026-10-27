<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

class EditAction extends ItemAction
{
    public const CALLBACK = "canEdit";

    /**
     * The reason of setDisabledReason(), else the one of canEdit() of the resource.
     */
    public function getDisabledReason(): ?string
    {
        return parent::getDisabledReason() ?? $this->getCallbackReason(static::CALLBACK);
    }
}
