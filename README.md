# CruditBundle


[![Build Status](https://github.com/2lenet/CruditBundle/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/2lenet/CruditBundle/actions)
[![Build Status](https://github.com/2lenet/CruditBundle/actions/workflows/validate.yml/badge.svg?branch=main)](https://github.com/2lenet/CruditBundle/actions)

Crudit bundle for 2le.

## Installation

```composer require 2lenet/crudit-bundle```

## Recipe

- [Submenu](doc/submenu.md)
- [Filters](doc/filter.md)
- [Form types](doc/form_types.md)


## Design architecture

Crudit is a standard RAD specification to create business applications. 
These applications are naively composed by crud screen assembled by a layout and linked together.

There is a lot of tools to generate such screen in multiple technologies. But each techno redefines some vocabulary, redevelops some concept and design.
Maybe some reflexion could be mutualized.

The first objective is to define some standard format to optimise development effort for each part of such a complex system.

These standards are techno agnostic.

First we define two main parts.

## Frontend

This part it the visible part of the application. It can be a single page app in Vue, Angular or React but it could also be a simple and classic template system in PHP or Twig.

The frontend recieves two sorts of data. 
- parameters data ( UI config, layout, menu entry, etc...)
- business data ( customer, invoice, prices, products, etc... )

these two sorts of data are provided in a standardised way ( the format is defined by castor but the serialisation model is free ( json, direct data, xml))


## Backend

This part is responsible for providing the business data, configuring the manager and how this data should be managed by the user.

The backend could be dispatched in many micro services each responsible for a small part of the application.

For example, we could build a single CRM / ERP app with part of the interface handled by a CRM micro service and the other by an ERP one.

The system have to respect the separation on concern needed by the business rules.

## Dependency

https://startbootstrap.com/theme/sb-admin-2 ( inspired from )

## js / css Developpement

To update the JS or the CSS please run 

`npm install`

and 

`npm run build` or `npm run watch`

in the bundle
