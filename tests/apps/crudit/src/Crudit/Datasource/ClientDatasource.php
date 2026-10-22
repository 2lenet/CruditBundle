<?php

declare(strict_types=1);

namespace App\Crudit\Datasource;

use App\Entity\Client;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;

class ClientDatasource extends AbstractDoctrineDatasource
{
    // The name is searched too (the initial fields of the datasource): the global search finds a client by its e-mail
    protected array $searchFields = ['email'];

    public function getClassName(): string
    {
        return Client::class;
    }
}
