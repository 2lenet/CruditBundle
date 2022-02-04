# Menu

How to do a menu hierarchy

**1. Internal links**
```php
    LinkElement::new(
        'menu.parametrage',
        null,
        Icon::new('/img/icons/gear.svg', Icon::TYPE_IMG)
    ),
    LinkElement::new(
        'menu.especes',
        Path::new('app_crudit_espece_index'),
        Icon::new('/img/icons/growing-seed.svg', Icon::TYPE_IMG)
    )->setParent('parametrage'),
    LinkElement::new(
        'menu.coefficients',
        Path::new('app_crudit_coefficient_index'),
        Icon::new('/img/icons/coefficient.svg', Icon::TYPE_IMG)
    )->setParent('parametrage'),
```

All menu element has a setParent() method which take the id of the parent.

The menu registry will consolidate the menu.

The id is automaticaly build with the label without the "menu.". You can force the id with setId().


**2. External links**

An external link can be created by adding ExternalLinkElement object to the menu.
```php
    ExternalLinkElement::new(
        'menu.google',
        'https://www.google.com',
        Icon::new('/img/icons/google.svg', Icon::TYPE_IMG)
    ),
```

By default, the target of this external link is "_blank", but it can be changed :
```php
    ExternalLinkElement::new(
        'menu.google',
        'https://www.google.com',
        Icon::new('/img/icons/google.svg', Icon::TYPE_IMG),
        '_self'
    ),
```