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

1. [Install UiBundle and Stimulus](#1-requirements-and-installation) *(lot 1.1)*
2. [Change the layout you extend](#2-layout) *(lot 2.1)*
3. [Replace the SCSS theme by CSS variables](#3-theme-scss-variables-to-css-variables) *(lot 1.2)*
4. [Decide what to do about Bootstrap](#4-bootstrap) *(lot 1.2)*
5. [Fix `setCssClass()` on actions](#5-css-classes-on-actions) *(lot 3.1)*
6. [Review your `lle_crudit` configuration](#6-configuration) *(lots 2.1, 3.2)*
7. [Review your template overrides](#7-template-overrides) *(each lot)*
8. [Review your JavaScript](#8-javascript) *(lots 1.1, 3.2)*
9. [Check the other 2LE bundles you use](#9-other-bundles) *(lot 5.8)*
10. [Translations](#10-translations) *(lot 1.4)*

## 1. Requirements and installation

*Draft, completed by lot 1.1.*

- New dependency: `2lenet/ui-bundle` (installed automatically, design system shared with DashboardBundle).
- `symfony/stimulus-bundle` and `symfony/ux-twig-component` are required. If your project had no
  Stimulus before, Flex does not create the configuration: `assets/controllers.json`,
  `assets/bootstrap.js` (`startStimulusApp`) and, under Encore, `enableStimulusBridge()` in
  `webpack.config.js`. The Flex recipe of UiBundle does it for you.
- Works with **Webpack Encore** and **AssetMapper**. A project with a Vue front stays on Encore.
- Remove `vendor/2lenet/crudit-bundle/assets/sb-admin/...` imports (SCSS and JS).

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

*Draft, completed by lot 1.2.*

Remove the `@import '.../crudit-bundle/assets/sb-admin/css/app.scss'` and the Sass variables declared
before it. The theme is now CSS custom properties, with no compilation:

```css
:root {
    --color-primary: #333333;
}
```

| Sass variable | CSS variable |
|---|---|
| `$primary` | *to be filled (lot 1.2)* |
| `$secondary`, `$success`, `$danger`, `$warning`, `$info` | *to be filled* |
| `$topbar-bg-color`, `$topbar-color` | *to be filled* |
| `$sidebar-bg-color`, `$sidebar-color`, `$sidebar-bg-event-color` | *to be filled* |
| `$border-radius`, `$font-size-base`, `$spacer` | *to be filled* |

Variables with no equivalent (`$input-*`, `$card-*`, `$btn-*`, `$table-*`, `$alert-*`, `$h2-font-size`…)
are gone: those components are driven by the design tokens. The `media()` mixin is gone too
(breakpoints: 768, 1024, 1280, 1600).

Logo (with a dark-theme variant) and primary color are also configurable: see `lle_crudit.theme`
*(to be filled)*.

## 4. Bootstrap

*Draft, completed by lot 1.2.*

Crudit no longer loads Bootstrap (CSS, JS, form theme) and no longer depends on `jquery`, `popper`,
CKEditor or EasyMDE. Your own templates keep working **only if you load Bootstrap yourself**, in the
`stylesheets` and `javascripts` blocks of your layout, before Crudit's CSS.

Bundle classes are prefixed (`lle-…`) and live in the `lle-ui` CSS layer, so they do not clash with
Bootstrap and your CSS wins over them without `!important`.

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
| `lle_ui.icons.load_fontawesome`, `lle_ui.icons.map` | New (UiBundle): icon library and replacement of any icon |

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
(toasts). Workflow states: `crudit-wf-state-<state>` is **removed**: declare a color per state in the workflow configuration (named palette, or your own `--lle-badge-<name>-bg` / `-fg` variables) *(details: lot 3.4)*. Edit in place `crudit-eip-*`:
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

## 10. Translations

*Completed by lot 1.4.* Locales: fr, en, de, it. Right-to-left languages are supported (provide your
own translations, Latin digits). Renamed or removed keys of the `LleCruditBundle` domain:
*to be filled*.
