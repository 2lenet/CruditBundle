<?php

namespace Lle\CruditBundle\Contracts;

interface FilterSetInterface
{
    /**
     * An iterable of FilterTypes
     */
    public function getFilters(): array;

    /**
     * @deprecated since 2.0: no effect, the filters are the bar and the panel of the Filter:Bar (4 per row at the most).
     */
    public function getNumberDisplayed(): int;

    /**
     * Internal ID
     */
    public function getId(): string;
}
