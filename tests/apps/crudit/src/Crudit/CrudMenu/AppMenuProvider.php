<?php

declare(strict_types=1);

namespace App\Crudit\CrudMenu;

use App\Security\AllowAllVoter;
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
 * One of each element of the menu: link (with icon, badge, without icon), sub-menu, category, title (alone and with
 * children), header, separator, external link. Icons in Iconify names and one logical name (user). The elements that
 * MenuBuilder removes: a link with a role nobody has (AllowAllVoter), the category it leaves alone, a separator at the
 * start and a doubled one. The routes of the pages have no underscore (see PageController).
 */
class AppMenuProvider implements MenuProviderInterface
{
    public function getMenuEntry(): array
    {
        $billing = LinkElement::new('menu.billing', null, Icon::new('fa7-solid:euro-sign'));
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

        $clients = LinkElement::new('menu.clients', Path::new('app_client_index'), Icon::new('fa7-solid:users'));
        $clients->addBadge(Badge::new('12', 'danger', 'menu.clients_to_call'));

        $tools = TitleElement::new('menu.tools');
        $tools->addChild(LinkElement::new('menu.exports', Path::new('exports'), Icon::new('fa7-solid:file-export')));
        $tools->addChild(LinkElement::new('menu.secret', Path::new('secret'), null, AllowAllVoter::NOBODY));

        return [
            HorizontalSeparatorElement::new(),
            LinkElement::new('menu.home', Path::new('home'), Icon::new('fa7-solid:house')),
            CategoryElement::new('menu.operations'),
            $clients,
            LinkElement::new('menu.interventions', Path::new('interventions'), Icon::new('fa7-solid:wrench')),
            $billing,
            $stock,
            HorizontalSeparatorElement::new(),
            CategoryElement::new('menu.archives'),
            LinkElement::new('menu.secret', Path::new('secret'), null, AllowAllVoter::NOBODY),
            HorizontalSeparatorElement::new(),
            TitleElement::new('menu.administration'),
            LinkElement::new('menu.users', Path::new('users'), Icon::new('user')),
            LinkElement::new('menu.settings', Path::new('settings'), Icon::new('fa7-solid:gear')),
            $tools,
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
