<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

class EditAction extends ItemAction
{
    public const CALLBACK = "canEdit";

    public function getTitle(): string
    {
        if ($this->getResource() !== null) {
            $canEdit = true;
            if (method_exists($this->getResource(), static::CALLBACK)) {
                $canEdit = $this->getResource()->{static::CALLBACK}();
            }

            if (is_string($canEdit)) {
                return $canEdit;
            }
        }

        return parent::getLabel();
    }

    public function isDisabled(): bool
    {
        if ($this->getResource() !== null) {
            $canEdit = true;
            if (method_exists($this->getResource(), static::CALLBACK)) {
                $canEdit = $this->getResource()->{static::CALLBACK}();
            }

            return $canEdit !== true;
        }

        return false;
    }
}
