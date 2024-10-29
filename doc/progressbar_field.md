Crudit ProgressBar Field

### Usage

To enable the progress bar for a field, you need to set the type:

```php
$value = Field::new("value", ProgressField::class);
```
And it's done !

### Attributs

You can customize your ProgressField with the following options:

- 'barCssClass' => Defines the color of the progress bar with a Bootstrap class,
- 'progressLabel' => Change the label displayed on the progress bar,
- 'progressLabelCssClass' => Defines the Bootstrap class for the label to customize it,
- 'min' => Integer who defines the minimum value,
- 'max' => integer who defines the maximum value,
- 'isBottomLabelActivate' => activates or deactivates the bottom label with true/false,
- 'bottomLabel' => Change the label under the bar,

### Example:

```php
$nbDeplacement = Field::new('nbDeplacement', ProgressBarField::class)
    ->setOptions(
            [
                "barCssClass" => "bg-info",
                "progressLabel" => "Example"
                "progressLabelCssClass" => "lh-sm fa-sm text-black"
                "isBottomLabelActivate" => true,
            ]
        );
```
