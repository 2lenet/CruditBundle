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
3. [Replace the SCSS theme by CSS variables](#3-theme-scss-variables-to-css-variables) *(lot 1.2)*
4. [Decide what to do about Bootstrap](#4-bootstrap) *(lot 1.2)*
5. [Fix `setCssClass()` on actions](#5-css-classes-on-actions) *(lot 3.1)*
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
`<nav id="main-nav">` with the menu, then the button that reduces it), then `<div class="crudit-shell__body">` with `<header>`, `<main id="main"
tabindex="-1">` and the footer. **Keep the ids `main` and `main-nav`**: the skip links go there. Without Bootstrap,
the old markup of SB Admin (`#wrapper`, `#content-wrapper`, `container-fluid`) is not styled any more: the simplest
is to stop overriding `body`, and to keep only the blocks inside it (`brand`, `menu`, `header`, `footer`, `content`).

Out of the block `body`, so that a project that rewrites it keeps them: the skip links (block `skip_links`), the
toasts (block `toasts`), the confirmation of the actions (block `modal_confirmation`) and the back to the top
button (block `scroll_top`). **Remove them from your `body` or `main` block** if you copied them there:
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
| `header.html.twig` (blocks `header_nav`, `header_right`) | blocks in `base.html.twig`, content in `header/_burger.html.twig`, `header/_actions.html.twig` |
| `elements/_user`, `elements/_multisearch`, `elements/_exit_impersonation` | `header/_user_menu`, `header/_search`, `header/_exit_impersonation` |
| `footer.html.twig` | block `footer` of `base.html.twig`, `footer/_version.html.twig` |
| `elements/_link`, `_link_element`, `_external_link`, `_title`, `_category`, `_header`, `_h_separator` | `sidebar/_link` (link or sub-menu), `sidebar/_item` (link and external link), `sidebar/_submenu`, `sidebar/_title`, `sidebar/_category`, `sidebar/_header`, `sidebar/_separator` |
| `bricks/_bricks` | `crud/_bricks` |
| `elements/_v_separator`, `_search`, `_message`, `_notification` | removed, **no replacement**: delete your `{% include %}` of `_v_separator` (the top bar has no separator any more). `elements/_search` (an SB Admin demo form) is not `header/_search`, which is the former `_multisearch` |

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
  neutral colours, **without any error**: `'badge-danger'` or `'bg-danger'` (the Bootstrap classes of 1.x) become
  neutral badges, write `'danger'`. `cssClass` and `getCssClass()` are removed, and so is the default `badge-success`. In the rail, a
  badge becomes a dot of its tone and is shown in the tooltip.
- **What the user cannot see is removed before rendering** (`MenuBuilder`, used by `crudit_menu_items()`): elements
  the user is not granted (`setRole()`), children included, a sub-menu or a title whose children are all removed (a
  title with children labels them only), a heading without children (title, category, header) followed by no item,
  a separator at the start, at the end or after another separator.
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
| `$topbar-bg-color`, `$topbar-color` | *to be filled (lot 2.1)* |
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

## 5. CSS classes on actions

*Draft, completed by lot 3.1.*

`setCssClass('btn btn-sm btn-primary')` on actions (found in most projects) no longer styles
anything: the classes are output unchanged but, without Bootstrap, they have no effect. There is no
compatibility layer. The `crudit-action` class stays as a styling hook.

Use the action options instead *(to be filled)*.

## 6. Configuration

*Draft, completed by lots 2.1 and 3.2.*

| Key | Change |
|---|---|
| `css_class_columns_form`, `css_class_columns_show`, `css_class_columns_card` | **Removed** (Bootstrap grid classes). *Replacement to be filled (lot 3.2.a).* |
| `number_cards_per_row` | **Removed.** *Replacement to be filled.* |
| `default_currency_alignment`, `default_integer_alignment`, `default_number_alignment` | Kept. `left` / `right` are rendered as logical `start` / `end` (right-to-left support) |
| `hide_if_disabled`, `delete_hide_if_disabled`, `add_connect_profile_link`, `add_exit_impersonation_button`, `exit_impersonation_path`, `generate_default_role`, `ignore_referer_routes` | Unchanged |
| `layout` (`logo`, `logo_dark`, `logo_small`, `favicon`, `version`, `release_date`, `footer_links`) | New: logo, favicon and footer, see [Logo and footer](#logo-and-footer-lot-21c) |
| `lle_crudit_ui.icons.library`, `lle_crudit_ui.icons.map` | New (CruditUiBundle): icon library and replacement of any icon, see [Icons](#icons) |

## 7. Template overrides

*Draft, completed lot by lot.*

**Modals** (`modal/_base_modal.html.twig`): same path, same variable `id` and blocks `content`, `header`, `body`,
`footer`, but a native `<dialog>` (controller `dialog` of CruditUiBundle) instead of a Bootstrap modal. *(lot 2.1.0)*

- Open it with a button: `<button type="button" commandfor="<id>" command="show-modal">` instead of
  `data-bs-toggle="modal" data-bs-target="#<id>"`; in JavaScript, `document.getElementById(id).showModal()` instead
  of `bootstrap.Modal`.
- Until lot 3.1, the triggers of Crudit (`<a data-bs-toggle="modal" data-bs-target="#<id>">`, `data-bs-dismiss="modal"`)
  still work: the controller `modal-bridge`, on `<body>`, opens and closes the dialog on a click. Yours work too, as
  long as the target is a `<dialog>` (`_base_modal`); a Bootstrap modal written by hand no longer does.
- Close it with `commandfor="<id>" command="close"` instead of `data-bs-dismiss="modal"`. The close button is in the
  header: remove your `btn-close`.
- The block `header` is the title of the dialog, inside a `<h2>` that names it (`aria-labelledby`): put text only in it, no `<h5 class="modal-title">` (a heading in a heading).
- `TelephoneType`: the behaviour is ported (controller `telephone`), not its style yet (`sb-admin/css/components/forms/_telephone.scss`, built on Bootstrap): until the forms are migrated, the calling code and the country select are side by side. *(to be done with the forms)*
- `.modal-header`, `.modal-body`, `.modal-footer` are gone (`crudit-ui-dialog__…`).

**Confirmation of the actions** (`modal/_confirm.html.twig`, block `modal_confirmation`): a `Dialog` with the
controller `confirm`, id `crudit-confirm` (was `modal-confirm`; `#modal-confirm-text` and `#modal-confirm-link` are
gone). The element that asks for it keeps `data-confirm-link` and `data-confirm-text` (the question, now the title of
the dialog); `data-bs-toggle` and `data-bs-target` are no longer needed. A button can also open it with
`commandfor="crudit-confirm" command="show-modal"`.

These paths and their main blocks are **kept**, because many projects extend them:
`brick/form/index.html.twig`, `modal/_base_modal.html.twig`, `field/*.html.twig`
(`value`, `icon`, `label`, `attributes`), `filter/type/*`, `filter/state/*`,
`brick/list_items/actions/_action.html.twig`, `crud/index.html.twig`.
Their *inner markup* changes (Bootstrap classes removed): check your overrides visually.

The form theme `form/custom_types.html.twig` is renamed `form/theme.html.twig` *(lot 3.2)*.

Renamed or removed blocks: *to be filled lot by lot.*

CSS hooks you may rely on: `crudit-field` and `crudit-action` are kept. `crudit-flash` is gone
(toasts). Workflow states: `crudit-wf-state-<state>` is **removed**: declare a color per state in the workflow configuration (named palette, or your own `--crudit-ui-badge-<name>-bg` / `-fg` variables) *(details: lot 3.4)*. Edit in place `crudit-eip-*`:
renamed *(lot 3.4.b)*.

## 8. JavaScript

*Draft, completed by lots 1.1 and 3.2.*

- The modules of `assets/sb-admin/js` are removed. The behavior is provided by Stimulus controllers.
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
