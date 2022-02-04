# Submenu

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

All Menu element has a setParent() method which take the id of the parent.

The menu registry will consolidate the menu.

The id is automaticaly build with the label without the "menu.". You can force the id with setId().


**2. External links**

An external link can be created by adding setExternal() method.
```php
    LinkElement::new(
        'menu.especes',
        Path::new('app_crudit_espece_index'),
        Icon::new('/img/icons/growing-seed.svg', Icon::TYPE_IMG)
    )->setExternal(true),
```
