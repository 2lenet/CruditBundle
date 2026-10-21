<?php

declare(strict_types=1);

namespace App\Crudit\Datasource;

use App\Entity\Client;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;

class ClientDatasource extends AbstractDoctrineDatasource
{
    public function getClassName(): string
    {
        return Client::class;
    }
}
