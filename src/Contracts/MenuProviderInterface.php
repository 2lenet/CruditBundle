<?php

use Lle\CruditBundle\Dto\MenuItem;

interface MenuProviderInterface
{
    public function getMenuEntry(): MenuItem[];
}
