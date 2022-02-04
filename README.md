# CruditBundle

![](doc/crudit.png)
[![Build Status](https://github.com/2lenet/CruditBundle/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/2lenet/CruditBundle/actions)
[![Build Status](https://github.com/2lenet/CruditBundle/actions/workflows/validate.yml/badge.svg?branch=main)](https://github.com/2lenet/CruditBundle/actions)

Crudit bundle for 2le.

## Installation

Webpack Encore is required and you need to have a security on your application.

```bash
require 2lenet/crudit-bundle
npm install bootstrap@5 sass sass-loader @fortawesome/fontawesome-free easymde --save
```

Just add the following lines in your template/base.html.twig

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

## Recipes

- [Setup Webpack Encore](doc/webpack_encore.md)
- [Create a CRUD](doc/crud.md)
- [Menu](doc/menu.md)
- [Filters](doc/filter.md)
- [Sublist](doc/sublist.md)
- [Use a FilterSet in your own Controller](doc/filterset_controller.md)
- [Form types](doc/form_types.md)
- [Add a map to a list or to a show](doc/map_config.md)
- [How to export data](doc/export.md)
- [Workflows](doc/workflow.md)
- [Markdown](doc/markdown.md)

## Principle

A crud is composed by
- an independent controller
- a crud config class
- a datasource
- a filterset if needed

The layout and menu are independant from the crud. You can integrate easily you own controller in a Crudit Layout

## Feature

### List view
![](doc/list.png)
The list view has the following features :
- Pagination
- Sorting
- Item Actions ( Show, Edit and Delete as standard )
- List Actions ( Add and Export csv and excel as standard )
- List grouping ( to save place in repeating values )
- Batch Action ( see [Batch actions](doc/batch_action.md) )
- Layout customisation is possible ( doc TODO / Block principle )

The list view need a Datasource but is not bounded to Doctrine or any ORM.

### Show view
![](doc/show.png)
The show view has the following feature :
- Show all fields
- Title can use the entity to title the object by its name
- Tabs
- Sublists to show related data ( see [Sublist](doc/sublist.md) )
- Layout customisation is possible ( doc TODO / Block principle )

### Form view
![](doc/edit.png)
The edit view is a classical Symfony Form. You write your own FormType

Crudit provides some help to be nicely integrated :
- Many FormType ( Datetime, Entity, etc ...)
- Entity Dropdown based on TomSelect with autocomplete
- DoctrineFormGuesser to automatically use Crudit FormType.
- Markdown editor based on EasyMDE
- Layout customisation is possible ( doc TODO / Block principle )

## JS Format input helper

Some help to check the input validity ( upper, email, ip, etc... )
- [inputFormat](doc/input_format.md)

## Dependencies

Crudit wants to minimise dependencies on not really popular bundles or components
in order to be able to maintain this bundle for many years and to follow the Symfony Stack development without dependency problems.

The layout is inspired by [SB Admin 2](https://startbootstrap.com/theme/sb-admin-2) but partially rewritten. Many of the features of SB Admin wasn't useful for this project.

The CSS framework used is [Bootstrap 5](https://getbootstrap.com/docs/5.1/getting-started/introduction/).

Excel export uses phpoffice/phpspreadsheet.

Doctrine is needed for the pre-version but the project is designed to work without it. Feel free to contact us if you want to use other dataprovider.


# Development

TO COMPLETE
- [Design principle](doc/design.md)

## JS / CSS Developpement

To update the JS or the CSS please run in the bundle

```bash
npm install
npm run build
```