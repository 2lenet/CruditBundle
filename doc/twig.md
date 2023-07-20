# Twig extensions

Crudit provide some twig helpers.

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
