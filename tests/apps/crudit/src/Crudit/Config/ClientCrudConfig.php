<?php

declare(strict_types=1);

namespace App\Crudit\Config;

use App\Crudit\Datasource\ClientDatasource;
use App\Form\ClientType;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Dto\Field\Field;

class ClientCrudConfig extends AbstractCrudConfig
{
    public function __construct(ClientDatasource $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * @return array<Field|Field[]>
     */
    public function getFields(string $key): array
    {
        $name = Field::new('name');
        $email = Field::new('email');
        $active = Field::new('active');
        $createdAt = Field::new('createdAt');

        return match ($key) {
            CrudConfigInterface::INDEX, CrudConfigInterface::SHOW => [$name, $email, $active, $createdAt],
            default => [],
        };
    }

    protected function getFormType(string $pageKey): ?string
    {
        return ClientType::class;
    }

    public function getRootRoute(): string
    {
        return 'app_client';
    }
}
