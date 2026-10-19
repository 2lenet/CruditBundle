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
`footer`, `side_footer`, `main`.

| Before | After |
|---|---|
| `layout/sb_admin/layout.html.twig` | `layout/base.html.twig` |
| `layout/sb_admin/header.html.twig`, `menu`, `footer`, `brand` | `layout/_header.html.twig`, `_menu`, `_footer`, `_brand` |
| `layout/sb_admin/elements/_user`, `_multisearch`, `_exit_impersonation` | `layout/_user_menu`, `_search`, `_exit_impersonation` |
| `layout/sb_admin/elements/_v_separator` | removed |

Removed `Dto/Layout` elements: `TemplateElement`, `SearchElement`, `UserElement`, `HorizontalSeparatorElement`, `VerticalSeparatorElement` (search is `Ctrl K`, the user menu is part of the header). Kept: `LinkElement`, `TitleElement`, `CategoryElement`, `HeaderElement`, `ExternalLinkElement`.

| `layout/sb_admin/_flash.html.twig` | `layout/_toasts.html.twig` (flash messages are toasts) |

If you overrode one of these files in `templates/bundles/LleCruditBundle/layout/sb_admin/`, rewrite it
on the new one: their markup changed completely.

The footer has **no default link** any more: add yours (accessibility statement, legal notice…) by
configuration or in the `footer` block.

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
| `lle_crudit_ui.icons.library`, `lle_crudit_ui.icons.map` | New (CruditUiBundle): icon library and replacement of any icon, see [Icons](#icons) |

## 7. Template overrides

*Draft, completed lot by lot.*

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
