Crudit ProgressBar Field

### Usage

To enable the progress bar for a field, you need to set the type:

```php
$value = Field::new("value", ProgressField::class);
```
And it's done !

### Attributs

You can customize your ProgressField with the following options:

- 'theme' => defines the color of the progress bar with a Bootstrap class,
- 'progressLabel' => defines the label on the progress bar,
- 'progressLabelCssClass' => defines the Bootstrap class for the label to customize it,
- 'min' => defines the minimum value,
- 'max' => defines the maximum value,
- 'bottomLabel' => activates or deactivates the bottom label with true/false.

### Example:

```php
$value = Field::new('value', ProgressField::class)
    ->setOptions(["theme"=>"bg-warning", "progressLabel"=>"Example","progressLabelCssClass"=>"lh-sm fa-sm text-uppercase", "min"=>5, "max"=>10, "bottomLabel"=>true]);
```
