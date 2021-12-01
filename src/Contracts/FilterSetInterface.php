<?php

namespace Lle\CruditBundle\Contracts;

interface FilterSetInterface
{
    /**
     * An iterable of FilterTypes
     */
    public function getFilters(): array;

    /**
     * Get the total amount of filters to display, the others will be collapsed
     */
    public function getAmountDisplayed(): int;

    /**
     * Internal ID
     */
    public function getId(): string;
}
