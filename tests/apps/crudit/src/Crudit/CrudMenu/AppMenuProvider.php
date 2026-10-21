<?php

declare(strict_types=1);

namespace App\Crudit\CrudMenu;

use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Lle\CruditBundle\Dto\Badge;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Layout\CategoryElement;
use Lle\CruditBundle\Dto\Layout\ExternalLinkElement;
use Lle\CruditBundle\Dto\Layout\HeaderElement;
use Lle\CruditBundle\Dto\Layout\HorizontalSeparatorElement;
use Lle\CruditBundle\Dto\Layout\LinkElement;
use Lle\CruditBundle\Dto\Layout\TitleElement;
use Lle\CruditBundle\Dto\Path;

/**
 * One of each element of the menu: link (with icon, badge, without icon), sub-menu, category, title, header,
 * separator, external link. The routes of the pages have no underscore (see PageController).
 */
class AppMenuProvider implements MenuProviderInterface
{
    public function getMenuEntry(): array
    {
        $billing = LinkElement::new('menu.billing', null, Icon::new('euro-sign'));
        $billing->add(LinkElement::new('menu.invoices', Path::new('invoices')));
        $billing->add(LinkElement::new('menu.credits', Path::new('credits')));
        $reminders = LinkElement::new('menu.reminders', Path::new('reminders'));
        // A third level, which LinkElement::add() allows: it is not rendered
        $reminders->add(LinkElement::new('menu.lateReminders', Path::new('reminders')));
        $billing->add($reminders);

        $stock = LinkElement::new('menu.stock', null);
        $stock->add(LinkElement::new('menu.items', Path::new('items')));
        $stock->add(LinkElement::new('menu.movements', Path::new('movements')));
        // A link of a sub-menu that opens a new tab (a sub-menu only holds LinkElement)
        $stock->add(LinkElement::new('menu.map', Path::new('items'))->setTarget('_blank'));

        $clients = LinkElement::new('menu.clients', Path::new('app_client_index'), Icon::new('users'));
        $clients->addBadge(Badge::new('12'));

        return [
            LinkElement::new('menu.home', Path::new('home'), Icon::new('home')),
            CategoryElement::new('menu.operations'),
            $clients,
            LinkElement::new('menu.interventions', Path::new('interventions'), Icon::new('wrench')),
            $billing,
            $stock,
            HorizontalSeparatorElement::new(),
            TitleElement::new('menu.administration'),
            LinkElement::new('menu.users', Path::new('users'), Icon::new('user')),
            LinkElement::new('menu.settings', Path::new('settings'), Icon::new('cog')),
            HeaderElement::new('menu.help'),
            ExternalLinkElement::new('menu.documentation', 'https://example.org/doc'),
            LinkElement::new('menu.reports', Path::new('reports')),
            LinkElement::new('menu.planning', Path::new('planning')),
            LinkElement::new('menu.contracts', Path::new('contracts')),
            LinkElement::new('menu.suppliers', Path::new('suppliers')),
            LinkElement::new('menu.vehicles', Path::new('vehicles')),
            LinkElement::new('menu.agencies', Path::new('agencies')),
        ];
    }
}
