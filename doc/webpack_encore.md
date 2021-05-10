## To integrate crudit with webpack

update your base.html.twig template with this : 

```twig
{% extends crudit.template('layout') %}

{% block stylesheets %}
    {# { parent() }#}
    {{ encore_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {{ encore_entry_script_tags('app') }}
{% endblock %}
```

note the comment to not load the parent stylesheets.

And in you app.js entry add the following:

```scss
@import "variables.scss";
@import "../../vendor/2lenet/crudit-bundle/assets/sb-admin/css/app.scss";
```

you can know customise the colors or other bootstrap variables in the _variables.scss file

```scss
$primary: #c18b8b;
```
