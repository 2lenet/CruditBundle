Crudit ProgressBar Field

### Usage

To enable the progress bar for a field, you need to set the type:

```php
$value = Field::new("value", ProgressBarField::class);
```
And it's done !

The value is shown as a bar (the native `<progress>`, named by the label of the field) and written as a percentage
of the interval `min`–`max`, in the format of the locale (`45 %` in French). The bar stays between `min` and `max`, the
value written is the real one (`130 %`). The bar is in the primary tone, in the success one from `max`.

### Options

- 'min' => the minimum value (0),
- 'max' => the maximum value (100),
- 'overMax' => the tone above `max`: 'success' (default, as at `max`), or 'danger' (above `max` is an alert, a budget
  exceeded),
- 'progressLabel' => the text written instead of the percentage,
- 'isBottomLabelActivate' => adds a line under the bar (true/false),
- 'bottomLabel' => the text of that line (the percentage by default),
- 'barCssClass', 'progressLabelCssClass' => **deprecated in 2.0, without effect**: the bar and its value have the look
  of the design system (use 'overMax' for the tone above `max`).

### Example:

```php
$budget = Field::new('budgetUsed', ProgressBarField::class)
    ->setOptions(
            [
                "overMax" => "danger",
                "isBottomLabelActivate" => true,
                "bottomLabel" => "of the budget",
            ]
        );
```
