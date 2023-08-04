When you override a brick, you sometimes have to use your own template.

To do this, you can use the `->setTemplate` method and specify the path to your template
> :warning: **The path you need to specify is only the folder where your `index.html.twig` file is located.**

```php
$indexBricks[] = ListConfig::new()
    ->addFields($this->getFields(CrudConfigInterface::INDEX))
    ->setTemplate('crudit/commande/list');
```