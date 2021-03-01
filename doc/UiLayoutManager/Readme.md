Navigation entry are provided from an api in json.

This json can be created from multiple bundle and merged but the result is a ui config file like the one provided as example.

## custom the menu

you can add menu withe the MenuInterface::getMenuEntry

This method return an array of LayoutElementInterface (you can create your own Element)

```php
<?php

namespace App\Services;

use Lle\CruditBundle\{
    Contracts\MenuProviderInterface,
    Dto\Layout\LinkElement,
    Dto\Path
};

class Menu implements MenuProviderInterface
{

    public function getMenuEntry(): array
    {
        return [
            LinkElement::new('babar', Path::new('lle_crudit_dashboard_index'))->setPriority(5),
            LinkElement::new('babar2', Path::new('lle_crudit_dashboard_index'))->setPriority(1)
        ];
    }
}
```

this code show Babar then babar2

if the priority is the same, the first entry is the first.

