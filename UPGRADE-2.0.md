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

- New dependency: `2lenet/crudit-ui-bundle` (installed automatically, design system shared with DashboardBundle).
- `symfony/stimulus-bundle` and `symfony/ux-twig-component` are required. If your project had no
  Stimulus before, Flex does not create the configuration: `assets/controllers.json`,
  `assets/bootstrap.js` (`startStimulusApp`) and, under Encore, `enableStimulusBridge()` in
  `webpack.config.js`. The Flex recipe of CruditUiBundle does it for you.
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
`body_javascript`, `modal_confirmation`, `scroll_top`, `logo`, `footer_text`. *(lot 2.1.0)*

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

New markup, if you rewrite the block `body`: `<aside class="crudit-shell__sidebar">` (logo, then
`<nav id="main-nav">` with the menu), then `<div class="crudit-shell__body">` with `<header>`, `<main id="main"
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
gone *(replacement: lot 2.1.b)*.

| Before | After |
|---|---|
| `layout/sb_admin/layout.html.twig` | `layout/base.html.twig` |
| `layout/sb_admin/header.html.twig`, `menu`, `footer`, `brand` | `layout/_header.html.twig`, `_menu`, `_footer`, `_brand` |
| `layout/sb_admin/elements/_user`, `_multisearch`, `_exit_impersonation` | `layout/_user_menu`, `_search`, `_exit_impersonation` |
| `layout/sb_admin/elements/_v_separator` | removed |

Removed `Dto/Layout` elements: `TemplateElement`, `SearchElement`, `UserElement`, `VerticalSeparatorElement` (search is `Ctrl K`, the user menu is part of the header, the top bar has no separator). Kept: `LinkElement`, `TitleElement`, `CategoryElement`, `HeaderElement`, `ExternalLinkElement`, `HorizontalSeparatorElement` (a rule between two groups of the menu).

**Flash messages are toasts** (`layout/_toasts.html.twig`, component `Toasts` of CruditUiBundle; removed:
`layout/sb_admin/_flash.html.twig`, the element `#crudit-flash` and the class `crudit-flash`). Types: `success`,
`info` / `notice`, `warning`, `danger` / `error`; an error stays until it is closed. The keys `crudit.*` are
translated in the domain `LleCruditBundle`, the others in `messages`. **They are escaped**: a message with HTML
shows its tags (before, a type containing `nt` was output raw). Write the message as text.

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

Bundle classes are prefixed (`crudit-ui-…`) and live in the `crudit-ui` CSS layer, so they do not clash with
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
| `lle_crudit_ui.icons.load_fontawesome`, `lle_crudit_ui.icons.map` | New (CruditUiBundle): icon library and replacement of any icon |

## 7. Template overrides

*Draft, completed lot by lot.*

**Modals** (`modal/_base_modal.html.twig`): same path, same variable `id` and blocks `content`, `header`, `body`,
`footer`, but a native `<dialog>` (controller `dialog` of CruditUiBundle) instead of a Bootstrap modal. *(lot 2.1.0)*

- Open it with a button: `<button type="button" commandfor="<id>" command="show-modal">` instead of
  `data-bs-toggle="modal" data-bs-target="#<id>"`; in JavaScript, `document.getElementById(id).showModal()` instead
  of `bootstrap.Modal`.
- Close it with `commandfor="<id>" command="close"` instead of `data-bs-dismiss="modal"`. The close button is in the
  header: remove your `btn-close`.
- The block `header` names the dialog (`aria-labelledby`): put the title in it (a `<h2>`).
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

## 10. Translations

*Completed by lot 1.4.* Locales: fr, en, de, it. Right-to-left languages are supported (provide your
own translations, Latin digits). Renamed or removed keys of the `LleCruditBundle` domain:
*to be filled*.
