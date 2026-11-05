<?php

declare(strict_types=1);

namespace App\Crudit\Datasource;

use App\Crudit\Datasource\Filterset\ClientFilterset;
use App\Entity\Client;
use Lle\CruditBundle\Datasource\AbstractDoctrineDatasource;
use Symfony\Contracts\Service\Attribute\Required;

class ClientDatasource extends AbstractDoctrineDatasource
{
    // The name is searched too (the initial fields of the datasource): the global search finds a client by its e-mail
    protected array $searchFields = ['email'];

    #[Required]
    public function setFilterset(ClientFilterset $filterset): void
    {
        $this->filterset = $filterset;
    }

    public function getClassName(): string
    {
        return Client::class;
    }
}
