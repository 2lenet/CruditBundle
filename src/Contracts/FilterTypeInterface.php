<?php

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

/**
 * FilterTypeInterface
 */
interface FilterTypeInterface
{
    public function init($propertyName, $label);
    public function configure(array $config = []);
    public function apply($query_builder);
    public function getTemplate();
    public function getStateTemplate();
    public function updateDataFromRequest(Request $request);
}
