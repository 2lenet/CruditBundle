# Submenu

how to do a menu hierarchy

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

all Menu element has a setParent Method which take the id of the parent.

The menu registry will consolidate the menu.

The id is automaticaly build with le label without the "menu.". You can force the id with setId()
