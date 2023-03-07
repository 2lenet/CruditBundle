# Twig extensions

Crudit provide some twig helpers.

## How to create link to a filtered page

You can create links to a filtered page with `crudit_route_filtered_link` Twig function.

```twig
{{ crudit_route_filtered_link('entity', [['field1', 'value1', 'operator1'], ['field2', {'key2.1': 'value2.1', 'key2.2': 'value2.2'}, 'operator2']]) }}
```

The operator is not required, default is "eq".

Example :
```twig
{{ crudit_route_filtered_link('user', [['status', 'generated'], ['active', {'value': '2023-01-01', 'to': '2023-12-31'}, 'interval']]) }}
```
