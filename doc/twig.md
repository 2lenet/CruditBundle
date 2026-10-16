# Twig extensions

Crudit provide some twig helpers.

## Dates and numbers

Crudit displays the dates and the numbers in the format of the locale of the request, in Latin digits. Do the
same in your templates with the filters of CruditUiBundle, rather than `|date('d/m/Y')` or `|number_format`:

```twig
{{ commande.dateLivraison|ui_date('short') }}   {# 13/10/2026 in fr, 10/13/26 in en #}
{{ commande.updatedAt|ui_datetime('short', 'short') }}
{{ commande.poids|ui_number }}                  {# 1 234,5 in fr, 1,234.5 in en #}
{{ commande.montant|ui_currency('EUR') }}
```

See `docs/languages.md` in CruditUiBundle.

## How to create link to a filtered page

You can create links to a filtered page with `crudit_route_filtered_link` Twig function.

```twig
{{ crudit_route_filtered_link('entity', {
    'field1': {'value', 'value1'},
    'field2': {'value': 'value2', 'op': 'operator2'}
}) }}
```

The operator is not required, default is "eq".
To filter with an entity, just set the id of the resource into the Twig function.

Example :

```twig
{{ crudit_route_filtered_link('user', {
    'status': {'value': 'generated', 'op': 'neq'},
    'active': {'value': '2023-01-01', 'to': '2023-12-31'},
    'establishment': {'value': 1}
}) }}
```
