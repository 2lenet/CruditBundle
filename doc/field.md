# Field
In your `src/Crudit/Config/EntityCrudConfig.php`, the `getField` method allow you to choose which Entity's fields will be displayed depending on the context.
### Usage
For example, the `getField` method for Product entity that have `name` and `price` properties will be :
`````php
public function getFields($key): array
{
        $name = Field::new('name');
        $price = Field::new('price');
        
        if ($key == CrudConfigInterface::INDEX) {
            return [
                $name,
                $price
            ];
        }
}
`````

### Render customization
By default Crudit use the `toString` method to render a Field. 

You can customize this render with a twig template :
`````php
$name = Field::new('name')->setTemplate('path/to/your/template.html.twig');
`````

Or you can also use a FieldType that we provides or that you can create by inherit `Lle\CruditBundle\Field\AbstractField`:
`````php
$name = Field::new('name')->setType(FieldType::class);
`````

### Our FieldTypes
- BooleanField
- DateField
- DateTimeField
- DoctrineEntityField
- EmailField
- FormatField
- MarkdownField that require to run `composer require twig/markdown-extra twig/extra-bundle league/commonmark` before
- NumberField
- TelephoneField
- TextAreaField
- TextField
- WorkflowField