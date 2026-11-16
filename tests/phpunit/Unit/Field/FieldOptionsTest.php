<?php

declare(strict_types=1);

namespace phpunit\Unit\Field;

use Lle\CruditBundle\Field\AbstractField;
use Lle\CruditBundle\Field\CKEditorField;
use Lle\CruditBundle\Field\JsonField;
use Lle\CruditBundle\Field\MarkdownField;
use Lle\CruditBundle\Field\ProgressBarField;
use Lle\CruditBundle\Field\TextAreaField;
use Lle\CruditBundle\Field\TextField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The width of a field in a section of a record (lot 3.3.a): the option span, the whole line for a long text; the
 * classes of Bootstrap of the 1.x (cssClass, cardCssClass, tableCssClass, barCssClass, progressLabelCssClass) are
 * removed: an option that does not exist.
 */
class FieldOptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{int|string|null}>
     */
    public static function spans(): iterable
    {
        yield 'none' => [null];
        yield '1' => [1];
        yield '2' => [2];
        yield '3' => [3];
        yield 'full' => ['full'];
    }

    #[DataProvider('spans')]
    public function testTheSpan(int|string|null $span): void
    {
        self::assertSame($span, $this->resolver(TextField::class)->resolve(['span' => $span])['span']);
    }

    public function testASpanOfFourIsRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolver(TextField::class)->resolve(['span' => 4]);
    }

    /**
     * @return iterable<string, array{class-string<AbstractField>, string}>
     */
    public static function removedOptions(): iterable
    {
        yield 'cssClass' => [TextField::class, 'cssClass'];
        yield 'cardCssClass' => [TextField::class, 'cardCssClass'];
        yield 'tableCssClass' => [TextField::class, 'tableCssClass'];
        yield 'barCssClass' => [ProgressBarField::class, 'barCssClass'];
        yield 'progressLabelCssClass' => [ProgressBarField::class, 'progressLabelCssClass'];
    }

    /**
     * @param class-string<AbstractField> $class
     */
    #[DataProvider('removedOptions')]
    public function testTheClassesOfBootstrapAreRemoved(string $class, string $option): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->expectExceptionMessage('The option "' . $option . '" does not exist.');

        $this->resolver($class)->resolve([$option => 'col-md-6']);
    }

    /**
     * @return iterable<string, array{class-string<AbstractField>, bool}>
     */
    public static function wides(): iterable
    {
        yield 'text' => [TextField::class, false];
        yield 'textarea' => [TextAreaField::class, true];
        yield 'markdown' => [MarkdownField::class, true];
        yield 'rich text' => [CKEditorField::class, true];
        yield 'json' => [JsonField::class, true];
    }

    /**
     * @param class-string<AbstractField> $class
     */
    #[DataProvider('wides')]
    public function testALongTextTakesTheWholeLine(string $class, bool $wide): void
    {
        self::assertSame($wide, $this->field($class)->isWide());
    }

    /**
     * @param class-string<AbstractField> $class
     */
    private function resolver(string $class): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $this->field($class)->configureOptions($resolver);

        return $resolver;
    }

    /**
     * @param class-string<AbstractField> $class
     */
    private function field(string $class): AbstractField
    {
        return new $class(new Environment(new ArrayLoader()), new ParameterBag());
    }
}
