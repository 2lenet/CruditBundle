<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Form\Type;

use Lle\CruditBundle\Validator\CronExpression;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A cron expression, stored as a string ("30 8 * * 1,4"). Rendered by the widget of CruditUiBundle
 * (form/_cron.html.twig): a frequency, days, a day of the month and a time, written in a sentence and as the
 * expression; an expression the widget cannot show is typed as a text. Always checked by the constraint CronExpression
 * (an empty value passes). Not required by default, as GedmoTranslatableType: with required, the widget has no choice
 * None and the server refuses an empty value (NotBlank).
 */
class CronExpressionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => ['spellcheck' => 'false', 'autocomplete' => 'off'],
        ]);
        // The option of the validator extension of the forms (always there with the FrameworkBundle, null refused by its
        // allowed types); added after its normalizer, which already makes a list of a single constraint.
        if (!$resolver->isDefined('constraints')) {
            return;
        }
        $resolver->addNormalizer('constraints', static function (Options $options, mixed $constraints): array {
            $constraints = \is_array($constraints) ? $constraints : [$constraints];
            $cron = $notBlank = false;
            foreach ($constraints as $constraint) {
                $cron = $cron || $constraint instanceof CronExpression;
                $notBlank = $notBlank || $constraint instanceof NotBlank;
            }
            if ($options['required'] && !$notBlank) {
                $constraints[] = new NotBlank();
            }
            if (!$cron) {
                $constraints[] = new CronExpression();
            }

            return $constraints;
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $value = $view->vars['value'];
        $view->vars['crudit_cron_state'] = self::widgetState(\is_string($value) ? $value : null);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'crudit_cron_expression';
    }

    /**
     * The state of the widget for an expression: the three forms it writes ("m h * * *", "m h * * 1,4", "m h D * *",
     * D from 1 to 28, zeros before accepted: "00 08 * * *"), none for an empty value, custom for any other expression
     * (typed as a text), "L" included: the last day of the month is not standard cron.
     *
     * @return array{
     *     frequency: 'none'|'daily'|'weekly'|'monthly'|'custom',
     *     days: list<string>,
     *     month_day: string,
     *     time: string
     * }
     */
    public static function widgetState(?string $expression): array
    {
        $state = ['frequency' => 'custom', 'days' => [], 'month_day' => '1', 'time' => '08:00'];
        $expression = trim($expression ?? '');
        if ('' === $expression) {
            return array_merge($state, ['frequency' => 'none']);
        }

        $parts = explode(' ', $expression);
        if (5 !== \count($parts) || '*' !== $parts[3]) {
            return $state;
        }
        [$minute, $hour, $day, , $weekDays] = $parts;
        if (1 !== preg_match('/^[0-5]?\d$/', $minute) || 1 !== preg_match('/^([01]?\d|2[0-3])$/', $hour)) {
            return $state;
        }
        $time = \sprintf('%02d:%02d', (int) $hour, (int) $minute);

        if ('*' === $day && '*' === $weekDays) {
            return array_merge($state, ['frequency' => 'daily', 'time' => $time]);
        }
        if ('*' === $day && 1 === preg_match('/^[0-6](,[0-6])*$/', $weekDays)) {
            $days = array_values(array_unique(explode(',', $weekDays)));

            return array_merge($state, ['frequency' => 'weekly', 'days' => $days, 'time' => $time]);
        }
        if ('*' === $weekDays && 1 === preg_match('/^(0?[1-9]|1\d|2[0-8])$/', $day)) {
            // "05" is the option 5
            return array_merge($state, ['frequency' => 'monthly', 'month_day' => (string) (int) $day, 'time' => $time]);
        }

        return $state;
    }
}
