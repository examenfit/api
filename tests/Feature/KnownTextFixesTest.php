<?php

namespace Tests\Feature;

use App\Support\KnownTextFixes;
use Tests\TestCase;

// The hand-made corrections for the stored texts that formulas:repair cannot fix mechanically.
class KnownTextFixesTest extends TestCase
{
    public function test_a_stray_delimiter_inside_a_formula_is_removed(): void
    {
        $text = 'hokje is `$$20 \text{ nm}$$` breed en `$$0,00025\text{ W m}^{-2}\text{nm}$$^{-1}$$` hoog.';

        $this->assertSame(
            'hokje is `$$20 \text{ nm}$$` breed en `$$0,00025\text{ W m}^{-2}\text{nm}^{-1}$$` hoog.',
            (new KnownTextFixes())->apply('answer_sections', 'correction', 7192, $text)
        );
    }

    public function test_a_missing_closing_backtick_is_added(): void
    {
        $this->assertSame(
            'van `$$\rm O-15$$` is 122 seconde',
            (new KnownTextFixes())->apply('answer_sections', 'text', 7724, 'van `$$\rm O-15$$ is 122 seconde')
        );
        $this->assertSame(
            'spanning `$$U$$` in `$$y$$`-richting',
            (new KnownTextFixes())->apply('tips', 'text', 3789, 'spanning `$$U$$ in `$$y$$`-richting')
        );
    }

    public function test_math_symbols_in_italic_text_are_put_between_dollars(): void
    {
        $this->assertSame(
            '`$$\textit{aangegeven met $\sim$ of $\cdot$ of – 2 punten}$$`',
            (new KnownTextFixes())->apply('answer_sections', 'correction', 14592, '`$$\textit{aangegeven met \sim of \cdot of – 2 punten}$$`')
        );
    }

    public function test_another_row_with_the_same_text_is_left_alone(): void
    {
        $text = 'van `$$\rm O-15$$ is 122 seconde';

        $this->assertSame($text, (new KnownTextFixes())->apply('answer_sections', 'text', 1, $text));
    }

    public function test_a_row_that_was_changed_by_an_author_is_left_alone(): void
    {
        $text = 'al verbeterd: `$$\rm O-15$$` is 122 seconde';

        $this->assertSame($text, (new KnownTextFixes())->apply('answer_sections', 'text', 7724, $text));
    }

    public function test_applying_twice_changes_nothing_more(): void
    {
        $fixes = new KnownTextFixes();
        $once = $fixes->apply('tips', 'text', 3789, 'spanning `$$U$$ in `$$y$$`-richting');

        $this->assertSame($once, $fixes->apply('tips', 'text', 3789, $once));
    }
}
