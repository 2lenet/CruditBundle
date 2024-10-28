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
- 'progressValue' => The name of the attribute or method to retrieve the current progress value from the resource. This should be a string that corresponds to the property you want to display,
- 'progressLabelCssClass' => defines the Bootstrap class for the label to customize it,
- 'min' => integer who defines the minimum value,
- 'max' => integer who defines the maximum value,
- 'isBottomLabelActivate' => activates or deactivates the bottom label with true/false,
- 'bottomLabel' => By default, this gives the percentage of the progress bar. If you set this to true, it will display progressValue/value under the bar,
- 'isStripped' => true/false, defines if the progress bar is stripped,

### Example:

```php
$nbDeplacement = Field::new('nbDeplacement', ProgressBarField::class)
   ->setOptions(["theme"=>"bg-info", "progressValue"=>"getTotaldeplacement", "isBottomLabelActivate"=>true, "isStripped"=>true]);
```
