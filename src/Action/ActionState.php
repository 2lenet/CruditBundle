<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Action;

/**
 * What an action is on a page (ActionResolver): hidden, disabled with its reason, or usable.
 */
final class ActionState
{
    /** The right of the action is refused (a context: hidden with hideIfDisabled) */
    public const REASON_FORBIDDEN = 'crudit.action.reason.forbidden';

    /** canEdit()/canDelete() returned false, without a reason of its own */
    public const REASON_UNAVAILABLE = 'crudit.action.reason.unavailable';

    private function __construct(
        public readonly bool $visible,
        public readonly ?string $reason = null,
    ) {
    }

    public static function hidden(): self
    {
        return new self(false);
    }

    public static function enabled(): self
    {
        return new self(true);
    }

    public static function disabledBy(string $reason): self
    {
        return new self(true, $reason);
    }

    public function isDisabled(): bool
    {
        return $this->reason !== null;
    }

    /**
     * The domain of the translation of the reason: the bundle for its own reasons, else the one of the CRUD (null).
     */
    public function getReasonDomain(): ?string
    {
        return in_array($this->reason, [self::REASON_FORBIDDEN, self::REASON_UNAVAILABLE], true) ? 'LleCruditBundle' : null;
    }
}
