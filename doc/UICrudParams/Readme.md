# CRUD components


Liste des composants :

- ListComponent
    - PaginationComponent
- FilterComponent
- ActionComponent

- ShowComponent
    - TableComponent
    - TabComponent
    - HistoryComponent


#Add Ressource screen

crete your class

```php

<?php

namespace App\Configurator;

use App\Entity\Etablissment;
use Lle\CruditBundle\Brick\ListBrick\ListItem;
use Lle\CruditBundle\Contracts\AbstractCrudConfigurator;

class ClasseConfigurator extends AbstractCrudConfigurator
{

    public function getSubjectClass(): string
    {
        return Etablissment::class;
    }

    public function getBrickConfigurators(): array
    {
        return [
            ListItem::new()->addAuto(['id', 'name']),
        ];
    }
}

```

The menu and the page is create with a list of Etablissment. You can start to config and dev

(addAuto is an shortcur, you can use add(Field::new('id')))

