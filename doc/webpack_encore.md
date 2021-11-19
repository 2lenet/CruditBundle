# How to integrate Webpack Encore to Crudit

## Setup
To use Webpack Encore, just add the following lines in your template/base.html.twig

```twig
{% extends crudit.template('layout') %}

{% block stylesheets %}
    {{ encore_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {{ encore_entry_script_tags('app') }}
{% endblock %}
```

Then, in your assets/js/app.js, you have to add this line :

```js
import '../styles/app.scss';
```

And in your assets/styles/app.scss, add this :
```scss
@import '../../vendor/2lenet/crudit-bundle/assets/sb-admin/css/app.scss';
```

All new SCSS files must be imported before the import of Crudit SCSS.


## Features
With that, you can now :
* customize the colors or other Bootstrap variables (in your assets/styles/_variables.scss for example):
  ```scss
  $primary: #C18B8B;
  ```

* use responsive mixin present and used by CruditBundle:
  ```scss
  @include media('>tablet') {
    // Your responsive rules
  }
  ```
  This is the list of all responsive breakpoints used :

  | Name | Value |
    | --- | --- |
  | mobile | 768px |
  | tablet | 992px |
  | laptop | 1200px |
