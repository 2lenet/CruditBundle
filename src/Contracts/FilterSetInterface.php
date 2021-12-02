<?php

namespace Lle\CruditBundle\Contracts;

interface FilterSetInterface
{
    /**
     * An iterable of FilterTypes
     */
    public function getFilters(): array;

    /**
     * Get the total number of filters to display, the others will be collapsed
     */
    public function getNumberDisplayed(): int;

    /**
     * Internal ID
     */
    public function getId(): string;
}
