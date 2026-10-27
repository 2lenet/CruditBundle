# Config

We have several configurations available to manage different functionalities.

**1. CRUD**

We have three configurations for managing the alignment of numbers with three possible values: left, center and right:
- `default_currency_alignment`
- `default_integer_alignment`
- `default_number_alignment`

We have four configurations for columns/card display :
- `css_class_columns_form` — CSS class applied to each field column in form view
- `css_class_columns_show` — CSS class applied to each field column in show view
- `css_class_columns_card` — CSS class applied to each field column in card view
- `number_cards_per_row` — number of cards displayed per row

```yaml
lle_crudit:
    css_class_columns_form: 'col-sm-12 col-md-6'
    css_class_columns_show: 'col-sm-12 col-md-6'
    css_class_columns_card: 'col-sm-12 col-md-4'
    number_cards_per_row: 3
```

If you want to have roles on all your bricks (sublist, form, template, etc.), you can add the following configuration:
- `generate_default_role`

**2. Actions**

Two configurations decide whether an action whose role is refused is hidden or shown disabled, with the reason
"You do not have the rights for this action":
- `hide_if_disabled`: `true` (hidden) by default;
- `delete_hide_if_disabled`: the same for the deletions, `null` by default (as `hide_if_disabled`).

`setHideIfDisabled()` on an action wins over them. An action disabled by a rule of your data (`canEdit()`,
`canDelete()`, `setDisabledReason()`) is always shown, with its reason; an action whose `setDisplayIf()` returns
false is always hidden (see [crud.md](crud.md)).

**3. Others**

These configurations manage the features in your application header.

`add_connect_profile_link` adds a link to your profile page (`<connect_url>profile`) in the user menu of the header.

You must have a `connect_url` variable in your `twig.yaml` file like this:

```yaml
twig:
    globals:
        connect_url: '%env(CONNECT_URL)%'
```

During an impersonation (`switch_user`), a banner above the header always says whose account is used and gives the
way back, "Back to my account" (no option: `add_exit_impersonation_button` is removed in 2.0, see UPGRADE-2.0.md,
"The header").

`exit_impersonation_path` is the route to which "Back to my account" leads after exiting the impersonation (default:
`homepage`).

`ignore_referer_routes` allow you to configure routes that will not be saved in the user's navigation session.

The value is an array of regexes matching the routes

```yaml
ignore_referer_routes:
    - /\/admin\/[0-9]+/
    - /\/login
```