# Upgrade from 1.x to 2.0

> **Work in progress.** This guide is filled in lot by lot while `feature/redesign` moves forward
> (see the migration plan in the `crudit-v2` mockup repository, `doc/plan-migration.md`).
> Each section says which lot completes it. Entries marked *draft* come from the inventory of the
> 26 projects using Crudit (`doc/inventaire-points-extension.md` in `crudit-v2`) and are not final.

Crudit 2.0 is a **breaking** release: new design, no more Bootstrap, no more Webpack in the bundle.
There is no compatibility layer between the old and the new theme.

**Crudit 2.0 and DashboardBundle 3.0 are released together.** The 2.x dashboard templates use
Bootstrap classes that Crudit 1.x used to provide: upgrade both, or load Bootstrap yourself.

## Checklist

1. [Install CruditUiBundle and Stimulus](#1-requirements-and-installation) *(lot 1.1)*
   - [Convert the icons to Iconify names and run `ux:icons:lock`](#icons) *(lot 1.3)*
2. [Change the layout you extend](#2-layout) *(lot 2.1)*
   - [Update your menu providers: icons, badges](#the-items-of-the-menu-lot-21a) *(lot 2.1.a)*
   - [Configure your logo, favicon and footer](#logo-and-footer-lot-21c) *(lot 2.1.c)*
   - [Log out in POST, give the user menu its name, role and email](#the-header-lot-22a) *(lot 2.2.a)*
   - [Check the header of your CRUD pages: one `<h1>`, the breadcrumb](#title-of-the-page-and-breadcrumb-lot-22b) *(lot 2.2.b)*
   - [Import the route of the global search and list its entities](#global-search-lot-22c) *(lot 2.2.c)*
3. [Replace the SCSS theme by CSS variables](#3-theme-scss-variables-to-css-variables) *(lot 1.2)*
4. [Decide what to do about Bootstrap](#4-bootstrap) *(lot 1.2)*
5. [Fix `setCssClass()` on actions: `setVariant()`, `setPrimary()`, the reasons](#5-actions) *(lot 3.1.a)*
6. [Review your `lle_crudit` configuration](#6-configuration) *(lots 2.1, 3.2)*
7. [Review your template overrides](#7-template-overrides) *(each lot)*
8. [Review your JavaScript](#8-javascript) *(lots 1.1, 3.2)*
9. [Check the other 2LE bundles you use](#9-other-bundles) *(lot 5.8)*
10. [Languages, dates and numbers](#10-languages-dates-and-numbers) *(lot 1.4)*

## 1. Requirements and installation

*Draft, completed by lot 1.1.*

- New dependency: `2lenet/crudit-ui-bundle` (installed automatically, design system shared with DashboardBundle).
- `symfony/stimulus-bundle` and `symfony/ux-twig-component` are required. If your project had no
  Stimulus before, Flex does not create the configuration: `assets/controllers.json`,
  `assets/bootstrap.js` (`startStimulusApp`) and, under Encore, `enableStimulusBridge()` in
  `webpack.config.js`. The Flex recipe of CruditUiBundle does it for you.
- The `intl` extension of PHP is required (by CruditUiBundle, lot 1.4): dates and numbers follow the
  locale, see [§10](#10-languages-dates-and-numbers).
- Works with **Webpack Encore** and **AssetMapper**. A project with a Vue front stays on Encore.
- Remove `vendor/2lenet/crudit-bundle/assets/sb-admin/...` imports (SCSS and JS).
- **Crudit is a Symfony UX package too** (`@2lenet/crudit-bundle`, keyword `symfony-ux`): its Stimulus controllers
  (`2lenet--crudit-bundle--confirm`, `2lenet--crudit-bundle--back-to-top`, more in the next lots) and `crudit.css`,
  the styles of the layout and of the CRUD screens. *(lot 2.1.0)*
  - **AssetMapper**: the bundle registers its `assets/dist` folder; Flex adds the controllers to
    `assets/controllers.json`.
  - **Webpack Encore**: add the package to your `package.json`, then `npm install`:

    ```json
    "@2lenet/crudit-bundle": "file:vendor/2lenet/crudit-bundle/assets"
    ```

    and enable its controllers in `assets/controllers.json` (Flex does it when it knows the package):

    ```json
    "@2lenet/crudit-bundle": {
        "back-to-top": {"enabled": true, "fetch": "eager"},
        "confirm": {"enabled": true, "fetch": "eager"}
    }
    ```

### Icons

*Lot 1.3. How a `CrudConfig` passes an icon (`Icon::new()`) is completed by the screen lots.*

- **No more icon font**: once lot 2.1 is merged, CruditBundle no longer loads Font Awesome (the `sb-admin` assets that
  load it go with the former layout; until then, the templates of `feature/redesign` still use Font Awesome classes).
  The icons are SVG rendered by Symfony UX Icons (`symfony/ux-icons` **3.5 or later**, required by CruditUiBundle for
  its finder), in the library chosen by the project (`lle_crudit_ui.icons.library`, Font Awesome 7 by default), see
  the [installation](https://github.com/2lenet/CruditUiBundle/blob/main/docs/installation.md#icons) and the [configuration](https://github.com/2lenet/CruditUiBundle/blob/main/docs/configuration.md#icons) of
  CruditUiBundle.
- **No Font Awesome CSS classes are understood (4, 5 or 6)**: `fa fa-truck`, `fas fa-truck`, `far fa-clock`,
  `fa-solid fa-truck` or `fab fa-github`. The icons of the project (actions, menu, tabs) become **Iconify names**:
  `fas fa-truck` and `fa-solid fa-truck` give `fa7-solid:truck`, `far fa-clock` gives `fa7-regular:clock`, and the
  brands (`fab fa-github`, menus and social links) give `fa7-brands:github`. Many names changed since Font Awesome 4
  and 5 (`fa-trash-o` is `fa7-regular:trash-can`; `fa-edit` is `fa7-regular:pen-to-square` from Font Awesome 4, an
  alias of the outlined `pencil-square-o`, and `fa7-solid:pen-to-square` from `fas fa-edit` in Font Awesome 5): check
  each one on [ux.symfony.com/icons](https://ux.symfony.com/icons). Any icon set of Iconify can be used
  (`lucide:truck`).
- **Before committing**, run `bin/console ux:icons:lock` and commit `assets/icons/`: production reads the icons from
  it, without calling Iconify. Remove `auto_lock`, which the recipe of Symfony UX Icons enables in development: it
  only copies the icons displayed while developing, so an icon never displayed would be missing in production.
  The icons the project builds in PHP or stores in the database need a finder of the project
  (`Symfony\UX\Icons\IconFinderInterface`), see the [installation of CruditUiBundle](https://github.com/2lenet/CruditUiBundle/blob/main/docs/installation.md#icons).
- **After changing the library** (`lle_crudit_ui.icons.library`), delete the folders of the Iconify prefixes of the
  former library (`assets/icons/fa7-solid/` and `assets/icons/fa7-regular/` for Font Awesome, `assets/icons/lucide/`
  for Lucide) and run `bin/console ux:icons:lock` again: the command deletes nothing, and the icons of the former
  library would stay in the repository.
- A project that keeps the Font Awesome classes stays on CruditBundle 1.x.

## 2. Layout

*Draft, completed by lot 2.1.*

The layout lost its `sb_admin` folder. In every template extending it:

```twig
{# before #}
{% extends '@LleCrudit/layout/sb_admin/layout.html.twig' %}
{# after #}
{% extends '@LleCrudit/layout/base.html.twig' %}
```

The blocks you most likely override keep their name and role: `stylesheets`, `javascripts`,
`page_title`, `favicon`, `brand`, `header`, `header_right`, `header_nav`, `menu`, `body`, `content`,
`footer`, `side_footer`, `main`. Also kept: `autorefresh`, `head_stylesheets`, `head_javascript`,
`body_javascript`, `modal_confirmation`, `scroll_top`, `logo`. *(lot 2.1.0)*

The blocks `logo`, `side_header`, `side_footer`, `header_nav` and `header_right` are in
`layout/base.html.twig` itself (in 1.x they were in included templates, where a template extending the layout could
not override them): you can now override any block of the layout, whatever the template that holds its content.

**The layout loads neither CSS nor JavaScript any more** (`bundles/llecrudit/sbadmin/app.css` and `app.js` are
gone): the blocks `stylesheets` and `javascripts` are empty. Load the CSS of CruditUiBundle, then the one of Crudit,
then yours, and your Stimulus application:

```twig
{# AssetMapper #}
{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('@2lenet/crudit-ui-bundle/ui.css') }}">
    <link rel="stylesheet" href="{{ asset('@2lenet/crudit-bundle/crudit.css') }}">
{% endblock %}
{% block javascripts %}{{ importmap('app') }}{% endblock %}
```

```js
// Webpack Encore, in your entry point (assets/app.js)
import '@2lenet/crudit-ui-bundle/dist/ui.css';
import '@2lenet/crudit-bundle/dist/crudit.css';
import './bootstrap.js';
```

`{{ parent() }}` in these blocks now adds nothing. The two scripts of FOSJsRouting are still in `<head>` *(to be
reviewed)*.

New markup, if you rewrite the block `body`: `<div id="crudit-sidebar" role="complementary">` (logo, filter, then
`<nav id="main-nav">` with the menu, then the button that reduces it; the logo is `<a class="crudit-brand">`, with
`crudit-brand__full`, `__logo`, `__name` and `__mark` for the rail), then `<div class="crudit-shell__body">` with `<header>`, `<main id="main"
tabindex="-1">` and the footer. **Keep the ids `main` and `main-nav`**: the skip links go there. Without Bootstrap,
the old markup of SB Admin (`#wrapper`, `#content-wrapper`, `container-fluid`) is not styled any more: the simplest
is to stop overriding `body`, and to keep only the blocks inside it (`brand`, `menu`, `header`, `footer`, `content`).

Out of the block `body`, so that a project that rewrites it keeps them: the skip links (block `skip_links`), the
toasts (block `toasts`), the global search (block `search_dialog`), the confirmation of the actions (block
`modal_confirmation`) and the back to the top button (block `scroll_top`). **Remove them from your `body` or `main` block** if you copied them there:
`{% include '@LleCrudit/layout/sb_admin/_flash.html.twig' %}` and
`{% include '@LleCrudit/modal/_confirm.html.twig' with {id: 'modal-confirm'} %}` (the first one no longer exists, the
second one would give two confirmation dialogs).

`<html>` gets `lang`, `dir` (right to left) and `data-theme` (the theme chosen by the user, cookie
`crudit_ui_theme`) from CruditUiBundle. The cookie `sidebarToggled` and the class `sidebar-toggled` of `<body>` are
gone: see "The menu" below.

### The menu *(lot 2.1.b)*

Three states of the same markup, driven by the Stimulus controller `sidebar` (declared in `assets/package.json`, nothing
to register with AssetMapper or Flex; with Webpack Encore, enable it in `controllers.json` like the others):

- **full menu** from 1024 px, **rail of icons** (72 px, `--sidebar-rail-w`) between 1024 and 1279 px by default, and
  **drawer** below 1024 px, opened by the burger of the header or by the skip link "Skip to menu";
- the choice of the user (button at the bottom of the menu, not shown below 1024 px) is in the cookie `crudit_sidebar`
  (`auto`, `collapsed`, `expanded`), read by the layout (`crudit_sidebar()`) and put in `data-sidebar` of
  `.crudit-shell`: the page arrives in the right state, without a jump. `auto` follows the width of the window; the
  cookie is deleted when the user's choice is what `auto` gives at that width, so the menu follows the window again;
- the menu is **rendered once**. It is a `<div role="complementary">`, not an `<aside>`: the drawer gives it the role
  `dialog` when it is open (`aria-modal`, the rest of the page `inert`, focus trapped, Escape and the backdrop close
  it, the focus goes back to the burger), and an `<aside>` cannot have that role;
- the **filter of the menu** (block `menu_filter`, to empty to remove it) hides the items that do not match, opens the
  sub-menus that hold matches and announces the count; the button that reduces the menu is the block `menu_toggle`;
- in the rail, the sub-menus are panels next to their button, and the items have a tooltip (component `Tooltip`,
  `beside`). An item without icon shows the first letter of its label.

Markup of the items (`layout/sidebar/`), if you override them: `data-menu-kind` (`item`, `heading` or
`separator`) on the `<li>`, `data-menu-label` on the items (the filter reads it), `aria-current="page"` on the link of
the current page, and the classes `crudit-nav__*`. A sub-menu is an `<li>` with the controller `disclosure` of
CruditUiBundle: a button (`aria-expanded`) and a panel `.crudit-nav__submenu`. **Your SCSS** on `.nav-link`,
`.sidebar-item`, `.btn-toggle`, `#sidebar-collapser` or `.sidebar-heading` has no effect any more.

New translation keys, domain `LleCruditBundle` (`crudit.layout.*`): `sidebar`, `open_menu`, `close_menu`,
`filter_menu`, `filter_none`, `filter_one`, `filter_many`, `collapse_menu`, `expand_menu`, `contains_current_page`,
`new_tab`.

| Before (`layout/sb_admin/…`) | After (`layout/…`) |
|---|---|
| `layout.html.twig` | `base.html.twig` |
| `brand.html.twig` (block `logo`), `menu.html.twig` (blocks `side_header`, `side_footer`) | blocks in `base.html.twig`, content in `sidebar/_logo.html.twig`, `sidebar/_menu.html.twig` |
| `header.html.twig` (blocks `header_nav`, `header_right`) | blocks in `base.html.twig`, content in `header/_burger.html.twig` and the block `user_menu` *(lot 2.2.a)* |
| `elements/_user`, `elements/_exit_impersonation` | `header/_user_menu`, `header/_impersonation` (the banner, *lot 2.2.a*) |
| `elements/_multisearch` (TomSelect, `urls` passed to the `include`) | **replaced by the global search**: button `header/_search` (block `header_search`) and palette `layout/_search` (block `search_dialog`), see [Global search](#global-search-lot-22c). Delete your override of `_multisearch` and your `include` |
| `footer.html.twig` | block `footer` of `base.html.twig`, `footer/_version.html.twig` |
| `elements/_link`, `_link_element`, `_external_link`, `_title`, `_category`, `_header`, `_h_separator` | `sidebar/_link` (link or sub-menu), `sidebar/_item` (link and external link), `sidebar/_submenu`, `sidebar/_title`, `sidebar/_category`, `sidebar/_header`, `sidebar/_separator` |
| `bricks/_bricks` | `crud/_bricks` |
| `elements/_v_separator`, `_search`, `_message`, `_notification` | removed, **no replacement**: delete your `{% include %}` of `_v_separator` (the top bar has no separator any more). `elements/_search` (an SB Admin demo form) is not `header/_search`, which is the button of the global search |

The folder `layout/sb_admin/` no longer exists: one folder per area of the layout (`sidebar/`, `header/`, `footer/`), and
`base.html.twig` with the fragments that belong to no area (`_skip_links`, `_toasts`, `_back_to_top`). The two new
fragments of the menu are `sidebar/_filter` (block `menu_filter`) and `sidebar/_toggle` (block `menu_toggle`).

Removed `Dto/Layout` elements: `TemplateElement`, `SearchElement`, `UserElement`, `VerticalSeparatorElement` (search is `Ctrl K`, the user menu is part of the header, the top bar has no separator). Kept: `LinkElement`, `TitleElement`, `CategoryElement`, `HeaderElement`, `ExternalLinkElement`, `HorizontalSeparatorElement` (a rule between two groups of the menu).

**Flash messages are toasts** (`layout/_toasts.html.twig`, component `Toasts` of CruditUiBundle; removed:
`layout/sb_admin/_flash.html.twig`, the element `#crudit-flash` and the class `crudit-flash`). Types: `success`,
`info` / `notice`, `warning`, `danger` / `error`; an error stays until it is closed. The keys `crudit.*` are
translated in the domain `LleCruditBundle`, the others in `messages`. **They are escaped**: a message with HTML
shows its tags (before, a type containing `nt` was output raw). Write the message as text.

If you overrode one of these files in `templates/bundles/LleCruditBundle/layout/sb_admin/`, rewrite it
on the new one: their markup changed completely.

The footer has **no copyright text** any more (block `footer_text` and key `crudit.footer.copyrights` removed: put your own text in the block `footer`), and **no default link**: add yours (accessibility statement, legal notice…) by
configuration or in the `footer` block.

### The items of the menu *(lot 2.1.a)*

- **Icons are Iconify names** (see *Icons* in §1): `Icon::new('users')` becomes `Icon::new('fa7-solid:users')`,
  `Icon::new('fas fa-book fa-lg')` becomes `Icon::new('fa7-solid:book')` (no size modifier: every icon of the menu has
  the same size). A logical name of CruditUiBundle (`Icon::new('user')`) also works. An icon that cannot be rendered
  (a Font Awesome class) is logged and replaced by the first letter of the label, as an item without icon.
- **`Icon::TYPE_IMG` is not rendered in the menu any more**: put the SVG in a folder of `assets/icons/` and give its
  name with the folder as prefix (`assets/icons/menu/home.svg` is `Icon::new('menu:home')`; a name without prefix is
  read as a logical name of CruditUiBundle). `Icon::new('/img/icons/home.svg', Icon::TYPE_IMG)` (arcade5) or
  `/bundles/llehermes/img/hermes.svg` (HermesBundle) are the cases to convert.
- **Run `bin/console ux:icons:lock`**: the icons of the menu are found by the finder of CruditBundle
  (`MenuIconFinder`, it reads your menu providers), the command imports them like the icons of the templates. It
  runs without a user (`ux:icons:lock`, `cache:warmup`): the icons of the items a provider adds only for some users
  (`if ($this->security->isGranted(…))`) are not found, declare them in `lle_crudit_ui.icons.map` or in the aliases
  of `ux_icons`; and what a provider computes (a badge counted in the database) also runs then.
- **Badges** (`Dto/Badge`): `Badge::new('12', 'danger', 'menu.visits_to_plan')`: the text shown, a tone
  (`neutral` by default, `info`, `success`, `warning`, `danger`) and a text read after it by screen readers only
  (a translation key: "12" alone means nothing). Any tone of the `Badge` component of CruditUiBundle works
  (`primary`, the extended palette, or a colour of the project with its CSS rule); a tone without its rule gives the
  neutral colours, without any error. The Bootstrap classes of 1.x (`'badge-danger'`, `'bg-danger'`) are
  **deprecated** and turned into their tone (`secondary`, `light` and `dark` give `neutral`): write `'danger'`.
  `cssClass` and `getCssClass()` are removed, and so is the default `badge-success`. In the rail, the badges become
  one dot, of the tone of the **first** badge (put the most important one first), and are shown in the tooltip.
- **The entry of the current page** (`aria-current`, sub-menu open, and the breadcrumb of lot 2.2.b): a link is active
  on its route and on the other pages of its CRUD (`app_client_index` on `app_client_show`, `_edit`, `_new`…). In
  1.x the route was cut at its last `_`, so a link to `app_home` was active on every `app_…` route: it no longer is.
  A link whose route ends with another word than an action of a CRUD (`app_client_list`) is active on that route
  only. A link to `lle_crudit_crud_index` without `resource` is never active (it was an error). A link to the list of a
  CRUD whose routes have no prefix (`client_index`, `client_show`) is active on its route only, as `app_index` is: give
  your routes a prefix (`app_client_index`), or make the link go to `lle_crudit_crud_index` with its `resource`. An
  action of the project with a `_` in its name (`app_client_import_csv`) is read as another CRUD
  (`app_client_contact_…`): `app_client_index` is not active on it. A link of the menu to another page of a CRUD
  (`app_client_new`) is active with the list of that CRUD, two links are then current: put the lists in the menu, the
  creation goes through the button of the list. *(lot 2.2)*
- **What the user cannot see is removed before rendering** (`MenuBuilder`, used by `crudit_menu_items()`): elements
  the user is not granted (`setRole()`), children included, a sub-menu or a title whose children are all removed (a
  title with children labels them only), a heading without children (title, category, header) followed by no item
  (an item or a heading with children; a separator ends the section), a separator at the start, at the end or after
  another separator. An implementation of `LayoutElementInterface` must add `withChildren()` (a copy of the element
  with other children: the menu is filtered on copies).
  The templates no longer call `is_granted()`. A `TitleElement` renders its children after it (it crashed before).
- `setCssClass()` with Bootstrap classes (`d-none d-md-block`) has no effect any more: remove it.
- The constructor of `Lle\CruditBundle\Twig\CruditExtension` takes a `MenuBuilder` instead of the `MenuRegistry`:
  nothing to do through the container, only a class that extends or decorates it is concerned.

### Logo and footer *(lot 2.1.c)*

The logo, the favicon, the version and the links of the footer are **configuration**, `lle_crudit.layout` (nothing by
default). The images are given to `asset()`: a logical path of AssetMapper (`images/logo.svg`), a public path
(`/img/logo.png`) or an address.

```yaml
# config/packages/lle_crudit.yaml
lle_crudit:
    layout:
        logo: 'images/logo.svg'           # the width of the menu, 40 px high at most
        logo_dark: 'images/logo-dark.svg' # dark theme (without it, logo in both themes)
        logo_small: 'images/mark.svg'     # square, collapsed menu (without it, the initial of app_name)
        favicon: 'images/favicon.svg'
        version: '%env(default::APP_VERSION)%'
        release_date: '%env(default::APP_RELEASE_DATE)%' # Y-m-d, shown "updated on 21 October 2026"
        footer_links:
            - { label: 'footer.accessibility', route: 'app_accessibility' } # translation key (messages) or text
            - { label: 'footer.legal', url: 'https://example.org/legal' }   # a route (route_params) or an url
```

- **Remove your blocks `brand`, `logo` and `favicon`** when they only put your image: `/img/logo.png` is no longer
  the default, neither for the logo nor for the favicon (without `favicon`, the browser asks for `/favicon.ico`), and
  the `apple-touch-icon` link is gone: add it in your block `favicon` if you need it. An inline `style` (kisscut6:
  `style="height:25px"`, a background colour) is refused by a strict CSP and is not needed any more: the logo takes
  the width of the menu.
- **The link of the logo is named "\<app_name\>, home"** (key `crudit.layout.home`) and the images are decorative
  (`alt=""`): do not put `alt="Logo"` back if you keep your block. Without `logo`, the name of the application is
  shown as text. It goes to `/`, as before (helyon used `path('homepage')`: keep your block `brand` for another
  address).
- **Logo per theme in CSS**: both images are in the page, the stylesheet shows the one of the theme (chosen by the
  user or by the system), without reloading; printed with the light one. `logo_dark` without `logo` is refused.
- **Footer**: the version comes from `version`, and from the Twig global `app_version` when it is empty.
  `release_date` adds "updated on …", in the language of the page; a date that is not `Y-m-d` is logged and not
  shown. No link by default; without link, version nor date, there is no `<footer>`. To remove it, empty the block
  `footer` (arhon).

### The header *(lot 2.2.a)*

The bar of the header (52 px, `--topbar-h`): the burger and the logo below 1024 px (the menu is then a drawer: the
logo is the block `brand`, rendered a second time, so your override counts there too: no `id` in it, it would be in
the page twice), the block `header_nav`, the
block `header_search` (the button of the [global search](#global-search-lot-22c)), then at the end the block
`header_right`,
which holds the block `user_menu`. Above the bar, the block `impersonation`. Out of the header: `header/_actions.html.twig`
(its content is the block `user_menu`), the block `user` (in an included template, it could not be overridden) and the
image `bundles/llecrudit/img/undraw_profile.svg` (the avatar shows the initials).

**The user menu** (`header/_user_menu.html.twig`, a `Dropdown` of CruditUiBundle) shows the initials, the name, the
role and the email of the user, the profile, the way back during an impersonation, the appearance (light, dark,
system: the controller `theme` of CruditUiBundle, declared in your `controllers.json`) and the logout. Nothing is shown
without a user. Name, role and email come from your user if it implements
`Lle\CruditBundle\Contracts\CruditUserInterface`; without it, the menu shows its name only (`__toString()`, else
`getUserIdentifier()`):

```php
use Lle\CruditBundle\Contracts\CruditUserInterface;

class User implements UserInterface, CruditUserInterface
{
    public function getDisplayName(): string { return $this->prenom . ' ' . $this->nom; }
    public function getRoleLabel(): ?string { return $this->profil?->getLabel(); } // CredentialBundle; kisscut6: $this->groupe?->getLibelle()
    // getEmail(): ?string, already in your entity
}
```

To change one part of the menu (a photo instead of the initials, more items), override
`@LleCrudit/layout/header/_user_menu.html.twig`; to replace it, override the block `user_menu`.

**The logout is a POST with a CSRF token** (it was a GET link to the route `logout`): a page of another site could log
your users out with an image. Configure the logout on your firewall, with the token:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            logout:
                path: logout # your route, or a path such as /logout
                enable_csrf: true
```

The menu sends the form to the logout of the firewall, with the CSRF field you configured (`csrf_parameter`,
`csrf_token_id`) in the body. Without `logout` on the firewall (SSO, stateless), the menu has no logout (a debug message is logged). A GET
link to the logout of your own templates no longer works with `enable_csrf`: make it a form (`method="post"`, the
hidden field `_csrf_token` with `csrf_token('logout')`, or your own parameter and id).

If `path` is your route and it is declared with `methods: [GET]` (or `#[Route(..., methods: ['GET'])]`), remove that
constraint: the router answers 405 to the POST before the firewall logs the user out. Or give a path without route
(`path: /logout`): the firewall handles it by itself.

The translation keys `crudit.action.profile` and `crudit.action.exit_impersonation` are removed (the menu uses
`crudit.layout.profile`, `crudit.layout.back_to_account` and `back_to_account_of`): move your overrides.

**The profile** (`add_connect_profile_link`, global `connect_url`) is an ordinary link to `<connect_url>profile`, in
a new tab, the application stays open (it was a popup opened by `window.open`, refused by a strict Content Security
Policy).

**The impersonation** (`switch_user` of Symfony). The option **`add_exit_impersonation_button` is removed**: remove
it from `config/packages/lle_crudit.yaml` (else Symfony stops on "Unrecognized option"). It showed a button "Exit
impersonation" in the header only if you asked for it; who uses which account is a matter of safety, so Crudit now
shows it always, without option. During an impersonation:

- **a banner above the header**, on every page: "You are browsing with the account of **Bob Durand**. Actions are
  recorded under this account." and a button **"Back to my account"**;
- **the avatar** of the user menu has a flag, read "(impersonation)" by screen readers;
- **the user menu** repeats the way back: "Back to my account (Alice Martin)", with the name of the user who
  impersonates.

The way back is the address of Symfony, `impersonation_exit_path()` (a GET: `?_switch_user=_exit`), which then goes to
the route of **`exit_impersonation_path`** (unchanged, `homepage` by default): set it to your home route if it has
another name. If the route does not exist, the way back goes to the root of the application (it was an error on every
page).

```yaml
# config/packages/security.yaml: what makes the impersonation possible (unchanged)
security:
    firewalls:
        main:
            switch_user: true # ?_switch_user=<identifier> with the role ROLE_ALLOWED_TO_SWITCH

# config/packages/lle_crudit.yaml
lle_crudit:
    exit_impersonation_path: 'app_home' # where "Back to my account" goes
    # add_exit_impersonation_button: true  ← remove
```

The names come from `CruditUserInterface` (`getDisplayName()`), else from `__toString()`. To change the banner,
override `@LleCrudit/layout/header/_impersonation.html.twig` or the block `impersonation` (empty it to hide it, at your
own risk), and its texts with the keys `crudit.layout.impersonation`, `impersonation_text` (with `%name%`),
`impersonation_detail` and `back_to_account` (domain `LleCruditBundle`). The Twig function
`crudit_add_exit_impersonation_button()` is removed with the option: in your templates, test the impersonation with
`is_granted('IS_IMPERSONATOR')`.

### Title of the page and breadcrumb *(lot 2.2.b)*

**The header of the CRUD pages** (`brick/links/index.html.twig`, the `LinksBrick` first on the list, the record and
the forms) is the `PageHeader` of CruditUiBundle: the breadcrumb, the title in the **only `<h1>` of the page**
(`#page-title`; it was a `<h4>` in a Bootstrap `navbar`) and the actions (see [Actions](#5-actions), lot 3.1.a). The
classes `navbar`, `navbar-brand`, `bg-light` of this header are gone: retarget your CSS on `.crudit-ui-page-header`.
`getCssClass()` of the resource is on the header. To replace the breadcrumb or the actions only, extend the template
and override its block `breadcrumb` or `page_actions`:

```twig
{# templates/bundles/LleCruditBundle/brick/links/index.html.twig #}
{% extends '@!LleCrudit/brick/links/index.html.twig' %}
{% block breadcrumb %}<twig:LleCruditUi:Breadcrumb :items="[…]"/>{% endblock %}
```

- **`<title>`**: "Page – Application" (`Clients – Gestio`), the page first, so that the tab and the screen reader give
  it before the application; it was "Application - Page". Without a title of the page, the name of the application
  alone (`app_name`, else `Crudit`; `CRUD-IT` is gone). A project that sets its own block `page_title` follows the
  same order.
- **Breadcrumb**: computed from the menu of the user, nothing to configure (it follows your menu providers): the home
  page, the group, the title and the sub-menu that hold the entry of the page, the entry, then on a CRUD the record
  (its page), "Edit" or "New". On a page of your own, `crudit_breadcrumb()` gives it (list of `{label, url?}`, the
  format of the `Breadcrumb` of CruditUiBundle), with the items of your page after it:
  `<twig:LleCruditUi:PageHeader title="…" :breadcrumb="crudit_breadcrumb([{label: client}])"/>`.
- **Focus of the title**: after a visit of Turbo, the controller `page-title` (on `<body>`, declared in
  `assets/package.json`) gives the focus to `<h1 id="page-title">`, so a screen reader starts at the new page. Not on
  a full load, nor on an address with an anchor. Nothing to do without Turbo.

### Global search *(lot 2.2.c)*

The search field of the top bar (TomSelect, `data-url` of each entity, results in the field) is replaced by the **global
search**: a button in the header and the palette `Ctrl K` / `Cmd K`, a window with results by group. It is shown by
default, with the screens of the menu. To search your entities, **import the route and list them**.

```yaml
# config/routes/lle_crudit.yaml
lle_crudit:
    resource: '@LleCruditBundle/Resources/config/routes.yaml'
```

```yaml
# config/packages/lle_crudit.yaml
lle_crudit:
    search:
        entities:                       # the groups, in this order
            - App\Crudit\Config\ClientCrudConfig             # the CrudConfig, alone…
            - { config: App\Crudit\Config\InvoiceCrudConfig, title: 'menu.invoices', limit: 3, subtitle: 'customer.name' }
        screens: true                   # the group of the links of the menu (true by default)
        limit: 10                       # results per group of entities (10 by default, 20 at most)
        min_length: 2                   # characters before the first search (2 by default)
        enabled: true                   # false: no button, no Ctrl K
```

- **The entities are listed, never "all that have an autocomplete"**: the old `urls` of the `include` (entity, title,
  `destUrl`, `url`, `limit`) become this list. The search asks the `autocomplete` of the datasource of each
  CrudConfig (`getAutocompleteSearchFields()`, `getDefaultAutocompleteSort()`): a datasource that has no search field
  raises `BadConfigException` when it is searched: the search logs the error and **leaves that group out**, the
  others and the screens still answer (when **every** group fails, the route answers 500 and the palette says so; in
  debug, the first error is always thrown). A class that is not a CrudConfig (a service tagged `crudit.config`) is refused
  when the container is built (`cache:clear`). `limit` is a ceiling **before the voters**: a restrictive voter can leave
  a group with fewer results. **Your own `/autocomplete` routes stay**: the form
  types use them; the search no longer does.
- A result opens the page `show` of the CrudConfig (`getPath('show', ['id' => …])`: your route `<root_route>_show`
  must exist). Its title is `(string) $resource`; `subtitle` is a property path (PropertyAccess) for a second line.
  The group is shown to a user granted `ROLE_<NAME>_SHOW`, each result is checked with the resource as subject (your
  voters apply).
- **The title of the group** is `title` (translation key of the domain of the CrudConfig, or text); without it, the
  translation of `getTitle('list')`, else the name of the CrudConfig.
- **The screens** are the links of the menu that the user sees (rights included, 5 at most, whatever `limit`), found by their label without regard
  to the accents or the case; the name of the sub-menu is the second line.
- The route is `GET /crudit/search?q=` (name `lle_crudit_search`, `SearchController`): JSON, never cached. **Without
  the import of the route, the search is simply not shown** (no error on the pages).
- **Blocks**: `header_search` (the button, in the bar of the header) and `search_dialog` (the palette, out of the block
  `body` like the toasts): empty them to remove the search. Ctrl K works wherever the header comes from. A page has one
  palette with the shortcut. The button shows `⌘ K` on a Mac (`Ctrl K` elsewhere) and hides itself when the page has no
  palette (`header_search` kept, `search_dialog` emptied): controller `search-trigger`.
- Removed with it: the `multisearch` script of `sb-admin` and the translation `crudit.multisearch.placeholder`
  (`crudit.search.*` in their place). If you override `_multisearch.html.twig` (arhon-backend), delete the override and
  use the configuration above; to change the rendering, override `layout/_search.html.twig` (the component
  `LleCruditUi:CommandPalette`, documented in CruditUiBundle).

## 3. Theme: SCSS variables to CSS variables

*Completed by lot 1.2, except the layout variables (lot 2.1).*

Remove the `@import '.../crudit-bundle/assets/sb-admin/css/app.scss'` and the Sass variables declared
before it. The theme is now the design tokens of CruditUiBundle, CSS custom properties overridden with no
compilation (CruditUiBundle `docs/customization.md`):

```css
:root {
    --brand: #b3261e;
}
```

`--brand` is the only colour to provide: the primary colours (buttons, links, focus ring, first chart series) are
derived from it, in the light and in the dark theme. Choose it dark enough for white text (4.5:1).

| Sass variable | CSS variable |
|---|---|
| `$primary` | `--brand` |
| `$body-color` | `--c-text` (`--c-text-muted` for secondary text) |
| `$success`, `$danger`, `$warning`, `$info` | `--c-success-*`, `--c-danger-*`, `--c-warning-*`, `--c-info-*` (`-bg`, `-text`, `-border`; `-solid` for success and danger) |
| `$secondary`, `$dark` | `--c-neutral-bg` / `--c-neutral-text`, `--c-inverse-bg` / `--c-inverse-text` |
| `$blue`, `$teal`, `$green`, `$yellow`, `$orange`, `$red`, `$pink`, `$purple`, `$indigo` | `--c-<hue>-bg` / `--c-<hue>-text`, 11 hues with a contrast of 4.5:1 in both themes (`$cyan`: `teal` or `blue`) |
| `$border-color` | `--c-border` (`--c-border-strong` for the form controls) |
| `$border-radius` | `--radius-sm` (controls), `--radius-md` (panels), `--radius-lg` |
| `$box-shadow-sm` | `--shadow-sm` (`--shadow-md`, `--shadow-lg`) |
| `$font-family-sans-serif` | `--font-sans` (Inter by default, the system font when the project does not load it) |
| `$font-size-base` | `--font-size-md` (16 px) |
| `$spacer` | `--space-4` (scale `--space-1` to `--space-8`, 4 px steps) |
| `$topbar-bg-color`, `$topbar-color` | none: the header is on `--c-surface`, in `--c-text`, like the menu; to colour it, a rule on `.crudit-shell__header` in your CSS (outside any layer, it wins), with a contrast of 4.5:1 for its text *(lot 2.2.a)* |
| `$sidebar-bg-color`, `$sidebar-color`, `$sidebar-bg-event-color` | *to be filled (lot 2.1)* |

A colour you override is defined twice: on `:root` for the light theme and on `:root[data-theme='dark']` for the dark
theme. **Dark mode is new**: it follows the system preference, and the user can choose. If you do not extend the
layout of Crudit, put the choice on `<html>` (`data-theme="{{ ui_theme() }}"`, see CruditUiBundle
`docs/installation.md`).

Variables with no equivalent (`$input-*`, `$card-*`, `$btn-*`, `$table-*`, `$alert-*`, `$h2-font-size`…)
are gone: those components are driven by the design tokens. The `media()` mixin is gone too
(breakpoints: 768, 1024, 1280, 1600).

Logo (with a dark-theme variant) and primary color are also configurable: see `lle_crudit.theme`
*(to be filled)*.

## 4. Bootstrap

*Draft, completed by lot 1.2.*

Crudit no longer loads Bootstrap (CSS, JS, form theme) and no longer depends on `jquery`, `popper`,
CKEditor or EasyMDE. Your own templates keep working **only if you load Bootstrap yourself**, in the
`stylesheets` and `javascripts` blocks of your layout.

**Load Bootstrap in a CSS layer**, between the two layers of CruditUiBundle, and declare their order before the
stylesheet of CruditUiBundle (CruditUiBundle `docs/customization.md`, *Keeping Bootstrap*):

```css
@layer crudit-ui-base, bootstrap, crudit-ui;
@import url('bootstrap/dist/css/bootstrap.min.css') layer(bootstrap);
```

Loaded as usual (outside a layer), its reboot wins over the components of Crudit (`button { border-radius: 0 }`
removes the radius of their buttons). In its layer, the classes of Bootstrap win over the reset of Crudit and the
components of Crudit over the reboot of Bootstrap.

Bundle classes are prefixed (`crudit-ui-…`), so they do not clash with Bootstrap, and your CSS, outside the layers,
wins over both without `!important`.

In the templates you override, the grid and the utilities of Bootstrap have a prefixed equivalent (CruditUiBundle
`docs/styles.md`): `row` → `crudit-ui-grid`, `col-lg-6` → `crudit-ui-col-lg-6`, `mb-3` → `crudit-ui-mb-3`,
`d-none d-md-flex` → `crudit-ui-d-none crudit-ui-d-md-flex`, `visually-hidden` → `crudit-ui-visually-hidden`,
`d-print-none` → `crudit-ui-print-hidden`. The spacing scale is not the same: `crudit-ui-mb-3` is `--space-3` (0.75rem).

Things that stop working without Bootstrap loaded: `data-bs-toggle`, `data-bs-target`,
`bootstrap.Modal`, `bootstrap.Tooltip`… in your own templates and scripts, and every Bootstrap
class in your templates (`row`, `col-*`, `card`, `btn`, `form-control`, `d-flex`, `mb-*`…).

## 5. Actions

*Lot 3.1.a.*

The actions are the `Button`, `IconButton`, `Dropdown` and `MenuItem` of CruditUiBundle: no `btn` class, no
`data-bs-toggle`, no Font Awesome `<i>`.

**The look: `setVariant()`, not `setCssClass()`.** `setCssClass('btn btn-sm btn-primary')` (found in most projects)
no longer styles anything: the classes `btn` and `btn-*` are **dropped**, with a deprecation, there is no
compatibility layer. The other classes are kept on the element (a hook of yours). Give the role of the action
instead:

| 1.x | 2.0 |
|---|---|
| `setCssClass('btn btn-primary …')` on the main action of the page | `setPrimary()`: one primary action per page (the first one wins), in the bar even on a phone |
| `setCssClass('btn btn-primary …')`, `btn-secondary`, `btn-light` | nothing (`secondary`, the default) |
| `btn-danger`, `btn-outline-danger` | `setVariant('danger')`, `setVariant('danger-outline')` |
| `btn-warning`, `btn-link` | `setVariant('warning')`, `setVariant('link')` |
| `btn-sm`, `mt-2 ms-1 mt-md-0`, `crudit-action` | nothing: the size and the spacing are the bar's |

The variants are those of the `Button` of CruditUiBundle (`Lle\CruditUiBundle\Component\ButtonVariant`: `primary`,
`secondary`, `ghost`, `warning`, `danger`, `danger-outline`, `link`); another value throws an exception.
`make:crudit` and the EasyAdmin converter write `setPrimary()` for `btn-primary` and `setVariant()` for the others
(`btn-outline-danger` → `danger-outline`). `setVariant('primary')` on an action that is not the primary one of the
page gives a secondary button: one primary button per page.

**The header of the pages** (`brick/links`, the `PageHeader`), as `PageActions.vue` of the mockup:

- the actions in their order, the primary one (`setPrimary()`; by default *Add* on the list, *Edit* on the record)
  in primary, the others in the variant of their own;
- `setDropdown(true)`: in the menu **"More actions"** (an icon button, named), by group (`setGroup('group.x')`, a
  heading, after those without group);
- a `DropdownAction`: a button at its label with a menu, its groups (string keys) under a heading;
- on a phone (below 768 px), only the primary action stays in the bar, the others go into "More actions";
- `PrintAction::new()`: prints the page (controller `print`), no inline script.
- the way back to the list goes back by the history of the navigation; without it, to the list, checked against
  its role, and without the `?id=…` of the record that 1.x added to its address. It is marked with `setBack()`: the
  action `list` of the CRUD and the button of `LinksConfig::addBack()` are; a way back of yours (renamed, or built
  without `AbstractCrudConfig`) needs `->setBack()`: the label `action.list` does not make a way back any more, and
  the constant `CrudConfigInterface::LABEL_ACTION_LIST` is removed. The default one (list, record, `addBack()`) is named `action.back` (it was
  `action.list`), to translate in the `messages` of the project as `action.add`… (e.g. "Go back"): it goes back to
  the previous page, not always a list.

**The lines of a list** (`brick/list_items/_actions.html.twig`, the lists and the sub-lists), as `RowActions.vue`:
the edition (`EditAction`) as an icon button named "Edit Bob", the other actions in the menu of the line named "More
actions for Bob". The label of an action of a line is no longer hidden behind its icon.

| Action of a line | 1.x | 2.0 |
|---|---|---|
| the edition, nothing set | shown | shown |
| another action, nothing set | shown | **in the menu of the line** |
| `setDropdown(false)` | shown | shown (an icon button named "Archive Bob", a small button at its label without icon) |
| `setDropdown(true)` | in the menu | in the menu, the edition too |

To keep an action of a line shown, as in 1.x, add `->setDropdown(false)`. A `DropdownAction` shown is an icon
button with its menu, named after it and the line. `setDropdown()` now takes `null` (the default: the choice of the
bundle), `getDropdown()` gives it; in the header, `null` is shown, as before. An action of yours that overrides
`setDropdown(bool $dropdown)` has to take `?bool`, else PHP stops on the signature.

**Disabled, hidden** (decision of 20/10: `hideIfDisabled` is for the actions of context only):

| Why the action cannot be used | 2.0 |
|---|---|
| `setDisplayIf()` (or `setDisplayIfByRequest()`) returns false | hidden, as in 1.x |
| its role (`setRole()`, or the role of its path) is refused | hidden when `hideIfDisabled` (the action, else `hide_if_disabled`, else `delete_hide_if_disabled` for a deletion), else disabled with the reason "You do not have the rights for this action" |
| a rule of the business: `canEdit()` / `canDelete()` of the resource, `setDisabledReason()` | **always shown**, disabled, with its reason |

- `canEdit()` / `canDelete()` return `true`, or the reason (a key of translation of the CRUD), or `false` (a generic
  reason). In 1.x, `hideIfDisabled` also hid an action refused by `canDelete()`: it is shown now, with its reason.
- `setDisabledReason('client.archived')`, or a callable given the resource of the line or of the record, which
  returns the reason or null: for any action. A string is always a reason, never called (`'count'` is a key); a
  callable is a closure or `[$object, 'method']`.
- An action of yours that overrides `isDisabled()` (and `getTitle()` for its reason), as in 1.x, still works: it is
  disabled by it, with the reason of `getTitle()`, else a generic one. Both overrides are **deprecated**: use
  `setDisabledReason()`, or override `getDisabledReason()`.
- `hide_if_disabled` is now `true` by default: an action refused by a right is hidden unless you set it to `false`.
- A disabled action stays focusable (`aria-disabled`), its reason in a tooltip and read with it (in a menu, under its
  label); it has no colour of danger.

**Confirmation** (`setConfirmModal(true)`): the question names the element, "Delete “Bob”?" (the key
`modal.confirm.<label>` of your domain, given `%entity%`, else `crudit.modal.confirm.question_entity`; `%entity%` is
the element as a string, or `#<id>` when it has no `__toString()`), the button
that confirms is the action ("Delete"), in danger for an action in `danger`; the focus is on *Cancel*.

**Modal of an action** (`setModal('client/_email_modal.html.twig')`): a template that extends
`@LleCrudit/modal/_base_modal.html.twig`, with free content, with or without fields (a `<form>` to the route of the
action, its submit button in the block `footer` with `form="…"`). Variables: `id` (`modal_<id of the action>`, then
the id of the line in a list), `action`, `params`. It is opened by the button of the action (`commandfor`), the
focus goes back to it, or to the menu, when it closes.

**Icons**: `Icon::new()` takes a logical name of CruditUiBundle (`add`, `delete`, `back`, `download`, `print`…) or
an Iconify name (`fa7-solid:file-export`), locked by `ux:icons:lock` (`ActionIconFinder`). The Font Awesome names of
1.x are no longer known: `plus` → `add`, `trash-alt` → `delete`, `arrow-circle-left` → `back`, `file-export` →
`download`, `search` and `edit` are unchanged. The type (`Icon::TYPE_FA`, `TYPE_FAR`) has no effect,
`Icon::getCssClass()` is deprecated.

**`ActionInterface`** has new methods (`getVariant()`, `setVariant()`, `isPrimary()`, `setPrimary()`,
`getTranslationDomain()`, `setTranslationDomain()`, `isBack()`, `setBack()`, `getGroup()`, `setGroup()`,
`getDisabledReason()`, `setDisabledReason()`): a class that implements it without extending `AbstractAction` has
to add them.

**New keys** (`LleCruditBundle`): `crudit.action.more`, `more_for`, `named`, `print`, `reason.forbidden`,
`reason.unavailable`, `crudit.modal.confirm.question`, `question_entity`.

## 6. Configuration

*Draft, completed by lots 2.1 and 3.2.*

| Key | Change |
|---|---|
| `css_class_columns_form`, `css_class_columns_show`, `css_class_columns_card` | **Removed** (Bootstrap grid classes). *Replacement to be filled (lot 3.2.a).* |
| `number_cards_per_row` | **Removed.** *Replacement to be filled.* |
| `default_currency_alignment`, `default_integer_alignment`, `default_number_alignment` | Kept. `left` / `right` are rendered as logical `start` / `end` (right-to-left support) |
| `hide_if_disabled` | **`true` by default** (was `false`), and only for the actions refused by a right: see [Actions](#5-actions) |
| `delete_hide_if_disabled` | `null` by default: as `hide_if_disabled`. Only for the deletions refused by a right |
| `add_connect_profile_link`, `exit_impersonation_path`, `generate_default_role`, `ignore_referer_routes` | Unchanged |
| `add_exit_impersonation_button` | **Removed**: the banner of the impersonation is always shown during one (and the way back in the user menu), see [The header](#the-header-lot-22a). Remove the key |
| `layout` (`logo`, `logo_dark`, `logo_small`, `favicon`, `version`, `release_date`, `footer_links`) | New: logo, favicon and footer, see [Logo and footer](#logo-and-footer-lot-21c) |
| `search` (`entities`, `screens`, `limit`, `min_length`, `enabled`) | New: the global search, see [Global search](#global-search-lot-22c) |
| `lle_crudit_ui.icons.library`, `lle_crudit_ui.icons.map` | New (CruditUiBundle): icon library and replacement of any icon, see [Icons](#icons) |

## 7. Template overrides

*Draft, completed lot by lot.*

**Modals** (`modal/_base_modal.html.twig`): same path, same variable `id` and blocks `content`, `header`, `body`,
`footer`, but a native `<dialog>` (controller `dialog` of CruditUiBundle) instead of a Bootstrap modal. *(lot 2.1.0)*

- Open it with a button: `<button type="button" commandfor="<id>" command="show-modal">` instead of
  `data-bs-toggle="modal" data-bs-target="#<id>"`; in JavaScript, `document.getElementById(id).showModal()` instead
  of `bootstrap.Modal`.
- The actions of Crudit open their modal with `commandfor` (lot 3.1.a). Until the workflows (lot 3.4.c) and the
  batch actions (lot 3.1.c), `data-bs-toggle="modal" data-bs-target="#<id>"` and `data-bs-dismiss="modal"` still
  work: the controller `modal-bridge`, on `<body>`, opens and closes the dialog on a click. Yours work too, as long as
  the target is a `<dialog>` (`_base_modal`); a Bootstrap modal written by hand no longer does.
- Close it with `commandfor="<id>" command="close"` instead of `data-bs-dismiss="modal"`. The close button is in the
  header: remove your `btn-close`.
- The block `header` is the title of the dialog, inside a `<h2>` that names it (`aria-labelledby`): put text only in it, no `<h5 class="modal-title">` (a heading in a heading).
- `TelephoneType`: the behaviour is ported (controller `telephone`), not its style yet (`sb-admin/css/components/forms/_telephone.scss`, built on Bootstrap): until the forms are migrated, the calling code and the country select are side by side. *(to be done with the forms)*
- `.modal-header`, `.modal-body`, `.modal-footer` are gone (`crudit-ui-dialog__…`).

**Confirmation of the actions** (`modal/_confirm.html.twig`, block `modal_confirmation`): a `Dialog` with the
controller `confirm`, id `crudit-confirm` (was `modal-confirm`; `#modal-confirm-text` and `#modal-confirm-link` are
gone). The element that asks for it keeps `data-confirm-link` and `data-confirm-text` (the question, now the title of
the dialog); `data-bs-toggle` and `data-bs-target` are no longer needed. A button can also open it with
`commandfor="crudit-confirm" command="show-modal"`. New: `data-confirm-label` (the label of the button that confirms,
else "Confirm") and `data-confirm-danger` (that button in danger, else in primary).

**Actions** *(lot 3.1.a)*: the paths of the header and of the lines are kept, their markup is the components of
CruditUiBundle (see [Actions](#5-actions)); the actions are sorted in PHP (`PageActionsBuilder`), the templates are
given an `item` (`ActionView`: `action`, `state`, `url`, `params`, `variant`, `entityLabel`) besides `action`, `url`,
`params`, `disabled`.

- Read the state of the action from `item.state` (`disabled`, `reason`), not from `action.disabled` or `action.title`:
  as in 1.x, an `ItemAction` is one instance for all the lines, each `action.*` call asks again `canEdit()` of the
  resource.
- Your template of an action (`setTemplate()`) is given `mobile_only`: a secondary action of the bar is rendered
  twice, in the bar (`false`) and in the menu "More actions" below md (`true`); give no fixed `id` in it.

| Template | 2.0 |
|---|---|
| `brick/links/_actions.html.twig` | The bar (`view.data.page_actions`). Blocks `links_actions`, `dropdown_actions`, `actions`, `kebab_dropdown_actions` **removed** |
| `brick/links/actions/_actions.html.twig` | One action of the bar (block `actions_item` kept), with its reason |
| `brick/links/actions/_action.html.twig` | A `Button` (block `action` kept; `attributes`, `icon`, `label` **removed**: the attributes go through `setCssClass()`, `setTarget()`) |
| `brick/links/actions/_dropdown_actions.html.twig`, `_dropdown_action.html.twig` | A `Dropdown`, a `MenuItem` (block `action` kept; `dropdown_actions_button`, `dropdown_actions_menu`, `dropdown_actions_item` **removed**) |
| `brick/links/actions/_list.html.twig` | No longer set on the way back to the list (`rel="noreferrer"` is given to it); kept for the projects that set it |
| `brick/list_items/_actions.html.twig` | The actions of a line (block `items_actions` kept): `row_actions.inline` (shown), `row_actions.others` (its menu), `row_actions.entityLabel` |
| `brick/list_items/actions/_actions.html.twig` | One action shown in a line (block `actions_item` kept), with its reason; a `DropdownAction` as a `Dropdown` |
| `brick/list_items/actions/_action.html.twig` | An `IconButton` (a small `Button` without icon) (block `action` kept, `attributes`, `icon`, `label` **removed**) |
| `brick/list_items/actions/_dropdown_actions.html.twig` | The menu of a line |
| `brick/links/actions/_kebab_dropdown_action(s).html.twig`, `brick/list_items/actions/_kebab_dropdown_action(s).html.twig` | **Removed**: the menu "More actions", the menu of the line |

The Twig function `crudit_hide_if_disabled()` is **removed**: `crudit_action_state(action, resource)` gives
`visible`, `disabled`, `reason`.

**The list** (`brick/list_items/index.html.twig`, *lot 3.1.b*): the table is the `LleCruditUi:Table` component of
CruditUiBundle (a table from `md` up, cards below, the same data), fed by the Twig function `crudit_table(view)`
(`TableViewBuilder`: columns, rows, groups, totals, sort, actions of the rows). The template only calls the component.

- **Kept**: the path of the template and the block `autorefresh`; the ids of the rows (`list-<name>-<id>`) and of the
  cells (`list-<name>-<id>-<field>-<label>`), so the scripts that refresh a cell still find it (the cards repeat them
  as `data-row-id` and `data-cell-id`, the page has each id once); `Field::linkTo()` (the cell is a link and the row
  opens it); the ruptures, subtotals and totals; the manual order (`getSortableField()`).
- **Removed blocks**: `heading_cell`, `items_actions`, `dropdown_actions`, `actions`, `kebab_dropdown_actions`.
  The templates `_heading_cell.html.twig` and `_footer.html.twig` are deleted. To add something above the table
  (a help text, which used to hijack `autorefresh`), extend the template and put it in its block `before` (just above the table).
- **Actions of a row** are data, not templates, sorted as in section 3.1.a (`crudit_row_actions()`, rights and
  reasons by `ActionResolver`): the `EditAction` is the icon button ("Edit *name of the row*"), every other action is in
  the menu "More actions for *name of the row*", a `DropdownAction` is a group of that menu (one per named group of its
  actions). The Table shows icon buttons only: an action `setDropdown(false)` without an icon stays in the menu. A
  disabled action stays focusable and its reason is read; it is inert (neither its modal nor its confirmation opens),
  a disabled `DropdownAction` is one disabled entry of the menu. `setModal()` opens its `<dialog>` with `commandfor`:
  the template of the modal receives `action`, `id`, `params`, `line` and `view`, and `action.resource` is bound to the
  resource of its row (use the variable `id`, the one of the `commandfor`, rather than rebuilding it from `action.id`).
  `setConfirmModal()` opens the dialog `crudit-confirm`, whose question names the row.
  **`setTemplate()` on an item action has no effect any more**: replace the conditional ones (for instance an action
  shown only in some states) by `setDisplayIf()`. `setCssClass()`: see section 5. The URL is `getUrl()` as is (it was
  `absolute_url()`).
- **Behaviours that change**:
  - the label of a rupture (`ruptGroup1.display`) is shown as text: the HTML is stripped, and `ruptGroup1.cssClass`
    is not used any more;
  - a rupture by date (`setRuptDateFormat()`) needs the DQL function `DATE_FORMAT`, which the bundle does not declare:
    declare it in your project (`beberlei/doctrineextensions`, see [doc/crud.md](doc/crud.md#how-to-group-rows-by-a-date-part-year-month-day));
  - the default way back (list and record) is labelled `action.back` (not `action.list`): add it to the `messages`
    of your project, as the other default actions; a way back of yours at the label `action.list` needs
    `->setBack()` (see section 5);
  - the field options `view.options.noHeader` and `field.options.tableCssClass` are ignored;
  - of `getCssClass()` on the resource, only a tone is kept: `danger`, `warning`, `success` or `info` when the class
    contains the word (`table-danger`, `text-info`…); any other class is lost;
  - the `path` of a total is not a link any more (it never worked: it used the variable of the last row);
  - the name of a row ("Edit *name*") is `__toString()` of the resource when it has one, else the text of its first
    non-empty cell: give `__toString()` to the resources whose first column is a badge, an image or a date;
  - the pager is shown below the table only, no more above (it comes back with lot 3.1.d).
- Icons of the actions: a Font Awesome name of 1.x that has an equivalent in the design system is converted
  (`trash-alt`, `pencil`…), an Iconify name (`mdi:pencil`) is used as it is.
- **Not in this lot** (the old markup stays for now): the pager (3.1.d), the batch actions (3.1.c: the selection boxes
  and the bar of the batch actions are **not shown** until then), the filters (3.1.e). The sublist (3.3.c) still uses
  the old templates (`list_items/_actions.html.twig` and `list_items/actions/*`).

These paths and their main blocks are **kept**, because many projects extend them:
`brick/form/index.html.twig`, `modal/_base_modal.html.twig`, `field/*.html.twig`
(`value`, `icon`, `label`, `attributes`), `filter/type/*`, `filter/state/*`,
`crud/index.html.twig`.
Their *inner markup* changes (Bootstrap classes removed): check your overrides visually.

The form theme `form/custom_types.html.twig` is renamed `form/theme.html.twig` *(lot 3.2)*.

Renamed or removed blocks: *to be filled lot by lot.*

CSS hooks you may rely on: `crudit-field` and `crudit-action` are kept. `crudit-flash` is gone
(toasts). Workflow states: `crudit-wf-state-<state>` is **removed**: declare a color per state in the workflow configuration (named palette, or your own `--crudit-ui-badge-<name>-bg` / `-fg` variables) *(details: lot 3.4)*. Edit in place `crudit-eip-*`:
renamed *(lot 3.4.b)*.

## 8. JavaScript

*Draft, completed by lots 1.1 and 3.2.*

- The modules of `assets/sb-admin/js` are removed. The behavior is provided by Stimulus controllers.
- New controller `print` (`PrintAction`, lot 3.1.a): enable it in your `assets/controllers.json`, as the other
  controllers of `@2lenet/crudit-bundle`.
- Importing `form.js`, `filters.js` or `editinplace.js` from `vendor/` (`initTomSelect`,
  `initChoiceTomSelect`…) no longer works. *Replacement to be filled (lot 3.2.c).*
- Tom Select, CKEditor and EasyMDE are replaced by in-house components. HTML already saved by
  CKEditor is read as is. FOSCKEditorBundle is no longer needed.
- If your project imported `bootstrap`, `jquery` or `@popperjs/core` thanks to Crudit's
  `package.json`, declare them in yours.

## 9. Other bundles

*Completed by lot 5.8.* HermesBundle, ConfigBundle, EntityFileBundle, CredentialBundle and
CruditPlatformBundle must be at a version compatible with Crudit 2.0: *versions to be filled*.

## 10. Languages, dates and numbers

*Lot 1.4.*

**Translations.** The bundles are translated in fr, en, de and it. No key of the `LleCruditBundle` domain
is renamed or removed. New keys: `crudit.action.edit`, `delete`, `show`, `add`, `export`, `filter`,
`search`, `reset`, `close`. A project in another language (Arabic…) translates the `LleCruditBundle`
and `LleCruditUiBundle` domains in its own `translations/`.

**Language and direction.** The layout puts `lang` and `dir` on `<html>`, from the locale of the request
(`ui_lang()`, `ui_dir()` of CruditUiBundle): the page is right-to-left in Arabic, Persian, Hebrew and
Urdu (`lle_crudit_ui.rtl_languages`). If you overrode the layout, do the same.

**Dates and numbers follow the locale** (breaking: they were in the French format whatever the
locale). The fields, the filter badges, the history and the creation and update dates use the format
of the locale of the request, always in Latin digits and in the Gregorian calendar:

| Field | Before | After, by default |
|---|---|---|
| `DateField` | `format: 'd/m/Y'` | `format: null`: `13/10/2026` in fr, `10/13/2026` in en |
| `DateTimeField` | `format: 'd/m/Y H:i'` | `format: null`: `13/10/2026 09:05` in fr, `10/13/2026, 9:05 AM` in en |
| `NumberField` | `decimal_separator: ','`, `thousands_separator: ' '` | `null`: `1 234,56` in fr, `1,234.56` in en |
| `NumberField`, one separator given | the other one: `,` or `' '` | unchanged: the other one keeps its former French default (`thousands_separator: '.'` gives `1.234,50`, in en too) |
| `IntegerField` | `thousands_separator: ' '` | `null`: separator of the locale |
| `CurrencyField` | `locale`: locale of the main request | `locale: null`: locale of the request, in Latin digits |

The dates always have the year in 4 digits, the rest follows the short format of the locale, leading zeros included
(`05/01/2026` in fr, `1/5/2026` in en). The history brick follows the locale too (dates, times, in the
timezone of Twig as the date of each change).

The French screens look the same, but **the thousands are now separated by a narrow no-break space
(U+202F)** instead of a space: the functional tests of your project that compare texts (Behat, Panther,
snapshots) must be updated, `1 234,56` no longer matches. To keep a fixed format, give it to the field:

```php
DateField::new('dateLivraison')->setOptions(['format' => 'd/m/Y']);
NumberField::new('poids')->setOptions(['decimal_separator' => ',', 'thousands_separator' => ' ']);
CurrencyField::new('montant')->setOptions(['locale' => 'fr']);
```

The exports (CSV, Excel) do not change: a date without format is exported as `d/m/Y` (`d/m/Y H:i`
for a date and time).

In your templates, replace `|date('d/m/Y')`, `|number_format(…)` and the `format_*` filters by the
filters of CruditUiBundle, which follow the locale: `ui_date('short')`, `ui_datetime('short', 'short')`,
`ui_number`, `ui_currency('EUR')` (see `docs/languages.md` of CruditUiBundle).

If you extended these classes: the constructor of `CurrencyField` no longer takes the `RequestStack`,
the one of `HistoryFactory` takes the `FormatExtension` of CruditUiBundle.
