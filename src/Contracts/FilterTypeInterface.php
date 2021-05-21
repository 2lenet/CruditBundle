<?php

namespace Lle\CruditBundle\Contracts;

use Symfony\Component\HttpFoundation\Request;

/**
 * FilterTypeInterface
 */
interface FilterTypeInterface
{
    public function getTemplate();
    public function getStateTemplate();
}
