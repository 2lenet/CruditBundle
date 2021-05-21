<?php


namespace Lle\CruditBundle\Contracts;


interface FilterSetInterface
{
    public function getFilters(): array;
    public function getId(): string;
}
