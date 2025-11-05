## Override default template

When you override a brick, you sometimes have to use your own template.

To do this, you can use the `->setTemplate` method and specify the path to your template
> :warning: **The path you need to specify is only the folder where your `index.html.twig` file is located.**

```php
$indexBricks[] = ListConfig::new()
    ->addFields($this->getFields(CrudConfigInterface::INDEX))
    ->setTemplate('crudit/commande/list');
```

## How to display the brick under certain conditions

You can add conditions to the brick, using the resource or not, in order to decide whether to display the brick based on the data.

To do this, simply use the method `setDisplayIf`:

```php
SublistConfig::new('subResourceField', $this->subResourceCrudConfig)
    ->setFields($this->subResourceCrudConfig->getFields(CrudConfigInterface::INDEX))
    ->setActions($this->subResourceCrudConfig->getItemActions())
    ->setDisplayIf(fn(Resource $resource) => $resource->isActive())
```
