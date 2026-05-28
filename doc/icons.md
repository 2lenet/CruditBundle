# Icons

Crudit ships with Font Awesome by default but lets you override any icon
individually so you can switch to another icon pack (Bootstrap Icons, Material
Icons, a custom set...) without forking the templates.

## How it works

All built-in templates render icons through a single Twig function:

```twig
<i class="{{ crudit_icon('search') }}"></i>
```

`crudit_icon(name)` looks up `name` in the `IconRegistry` and returns the CSS
classes to apply on a `<i>` element. The registry is built by merging Crudit's
default map (Font Awesome) with whatever you declare under
`lle_crudit.icons` — only the keys you redefine are overridden, the rest keep
their FA default.

If a name isn't in the registry, the function returns it unchanged, so passing
a raw CSS class still works.

## Configuration

Override icons in your `config/packages/lle_crudit.yaml`:

```yaml
lle_crudit:
    icons:
        # Switch a single icon to Bootstrap Icons
        search: 'bi bi-search'

        # Switch the pager to Bootstrap Icons
        pager_first: 'bi bi-chevron-double-left'
        pager_prev:  'bi bi-chevron-left'
        pager_next:  'bi bi-chevron-right'
        pager_last:  'bi bi-chevron-double-right'

        # Use a Font Awesome variant
        user: 'far fa-user'
```

The value is just the CSS classes that will be put on the `<i>` tag — use
whatever syntax your icon pack expects.

## Available icon keys

These are the logical names used in the bundle's templates. Override any of
them to change the rendered icon.

### Common UI

| Key | Default |
| --- | --- |
| `search` | `fas fa-search` |
| `plus` | `fas fa-plus` |
| `minus` | `fas fa-minus` |
| `check` | `fas fa-check` |
| `close` | `fa fa-close` |
| `window_close` | `fa fa-window-close` |
| `download` | `fas fa-download` |
| `info` | `fas fa-info-circle` |
| `warning` | `fas fa-exclamation-triangle` |
| `kebab` | `fas fa-ellipsis-v` |
| `user` | `fas fa-user` |
| `bell` | `fas fa-bell` |
| `envelope` | `fas fa-envelope` |
| `external_link` | `fas fa-external-link-alt` |
| `bars` | `fa fa-bars` |
| `sign_out` | `fas fa-sign-out-alt` |
| `arrow_right` | `fas fa-arrow-right` |
| `arrow_circle_left` | `fa fa-arrow-circle-left` |
| `clock` | `far fa-clock` |
| `exchange` | `fa fa-exchange` |
| `folder_plus` | `fas fa-folder-plus` |
| `file` | `fas fa-file-alt` |
| `donate` | `fas fa-donate` |
| `filter` | `fa fa-filter` |
| `angle_up` | `fas fa-angle-up` |
| `chevron_up` | `fa fa-chevron-up` |
| `chevron_down` | `fa fa-chevron-down` |

### Default action icons

Used by `AbstractCrudConfig` when it builds the default CRUD actions.
Override these to restyle every Crudit-generated edit/delete/show/etc. button.

| Key | Default |
| --- | --- |
| `edit` | `fa fa-edit` |
| `delete` | `fa fa-trash-alt` |
| `show` | `fa fa-search` |
| `export` | `fa fa-file-export` |

### Pager

| Key | Default |
| --- | --- |
| `pager_first` | `fa fa-angle-double-left` |
| `pager_prev` | `fa fa-angle-left` |
| `pager_next` | `fa fa-angle-right` |
| `pager_last` | `fa fa-angle-double-right` |

### Sort

| Key | Default |
| --- | --- |
| `sort` | `fas fa-sort` |
| `sort_up` | `fas fa-sort-up` |
| `sort_down` | `fas fa-sort-down` |

### Filter operators

| Key | Default |
| --- | --- |
| `op_equal` | `fas fa-equals` |
| `op_not_equal` | `fas fa-not-equal` |
| `op_less_than` | `fas fa-less-than` |
| `op_less_than_equal` | `fas fa-less-than-equal` |
| `op_greater_than` | `fas fa-greater-than` |
| `op_greater_than_equal` | `fas fa-greater-than-equal` |
| `op_interval` | `fas fa-arrows-alt-h` |
| `op_is_null` | `far fa-square` |
| `op_is_not_null` | `fas fa-square` |
| `op_contains` | `fa fa-text-width` |
| `op_starts_with` | `far fa-caret-square-right` |
| `op_ends_with` | `far fa-caret-square-left` |
| `op_in` | `fas fa-equals` |
| `op_not_in` | `fas fa-not-equal` |
| `op_before` | `fas fa-less-than` |
| `op_after` | `fas fa-greater-than` |

## Using `crudit_icon` in your own templates

The function is exposed globally, so you can reuse the same registry in your
own views to stay consistent with the rest of the admin:

```twig
<button class="btn btn-primary">
    <i class="{{ crudit_icon('plus') }}"></i> {{ 'action.add'|trans }}
</button>
```

You can also register your own logical names by adding them under
`lle_crudit.icons`. Any name that isn't in the defaults works the same way —
declare it once in the config and reference it everywhere with
`crudit_icon('my_name')`.

## Using the `Icon` DTO from PHP

When you build actions, layout elements, etc. from PHP, use the `Icon` DTO:

```php
use Lle\CruditBundle\Dto\Icon;

Icon::new('search');                          // <i class="fa fa-search">
Icon::new('user', Icon::TYPE_FAR);            // <i class="far fa-user">
Icon::new('logo.png', Icon::TYPE_IMG);        // <img src="logo.png">

// Any other pack: the prefix is required as the third argument.
Icon::new('search', 'fas', 'fa');             // <i class="fas fa-search">
Icon::new('github', 'fab', 'fa');             // <i class="fab fa-github">
Icon::new('search', 'bi', 'bi');              // <i class="bi bi-search">
Icon::new('home', 'my-pack', 'icon');         // <i class="my-pack icon-home">
```

Supported built-in types: `TYPE_FA` (default), `TYPE_FAR` and `TYPE_IMG`. When
you switch to another icon pack — any other value — you must pass the class
prefix used by that pack as the third constructor argument. Omitting it
throws an `InvalidArgumentException`.

### Late-bound icons with `Icon::logical()`

When you want the icon to be resolved through `lle_crudit.icons` (so a YAML
override takes effect without rebuilding the action), use a logical key
instead of hard-coding a pack:

```php
use Lle\CruditBundle\Dto\Icon;

EditAction::new('action.edit', $this->getPath('edit'), Icon::logical('edit'));
DeleteAction::new('action.delete', $this->getPath('delete'), Icon::logical('delete'));
```

This is what `AbstractCrudConfig` does for its default actions: it ships
`Icon::logical('edit' | 'delete' | 'show' | 'export' | 'plus' | 'arrow_circle_left')`
so an app that overrides these keys (e.g. to Bootstrap Icons) gets the new
glyph everywhere — no template fork required.

Internally, a logical icon stores only the key. Resolution happens at render
time through `crudit_icon(action.icon)`, which looks the key up in
`IconRegistry`. `getCssClass()` on a logical icon returns an empty string, so
templates that haven't been migrated will visibly miss the icon — that's a
deliberate signal to update the template.

## Operator icons and Bootstrap Icons

`op_equal`, `op_not_equal`, `op_less_than`, `op_greater_than`, `op_in`,
`op_not_in`, `op_before`, `op_after` (and friends) point at Font Awesome
glyphs that have **no direct equivalent in Bootstrap Icons**. If you switch
the rest of the bundle to BI, these keys will keep falling back to FA — that
is the safest behaviour for a comparison-operator label, and works as long as
your app still loads the Font Awesome stylesheet.

If you don't want to keep FA around just for these, override them in
`lle_crudit.icons` with whichever pack you use (or with text/Unicode):

```yaml
lle_crudit:
    icons:
        op_equal:        'bi bi-dash-lg'      # closest BI approximation
        op_not_equal:    'bi bi-slash-lg'
        op_less_than:    'bi bi-chevron-left'
        op_greater_than: 'bi bi-chevron-right'
        # ...
```
