<?php

namespace Tests\Feature;

use App\Support\FormulaRepair;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FormulaRepairTest extends TestCase
{
    public static function repairedFormulas(): array
    {
        return [
            'the formulas of the waterstraal question' => [
                'r = r_{0} \cdot \sqrt[4]\frac{v_0^2}{v_{0}^2+2gx}',
                'r = r_{0} \cdot \sqrt[4]{\frac{v_0^2}{v_{0}^2+2gx}}',
            ],
            'braces inside the denominator' => [
                'r(10)=\sqrt[3]\frac{7,0685}{\frac43 \pi}=1,190... \ \text{cm}',
                'r(10)=\sqrt[3]{\frac{7,0685}{\frac43 \pi}}=1,190... \ \text{cm}',
            ],
            'spaces between the commands and the groups' => [
                '\sqrt[4] \frac {a} {b}',
                '\sqrt[4]{\frac{a}{b}}',
            ],
            'an escaped brace is not the end of the group' => [
                '\sqrt[3]\frac{a\}}{b}x',
                '\sqrt[3]{\frac{a\}}{b}}x',
            ],
            'a prime after a superscript caret' => [
                "K^' = 133,49 \cdot 0,07 \cdot d^{0,07-1}",
                'K^{\prime} = 133,49 \cdot 0,07 \cdot d^{0,07-1}',
            ],
            'a prime with a space after the caret' => ["x^ ' + 1", 'x^{\prime} + 1'],
            'the texitt typo' => ['\texitt{Indien een antwoord}', '\textit{Indien een antwoord}'],
            'two in one formula' => [
                '\sqrt[3]\frac{a}{b} + \sqrt[4]\frac{c}{d}',
                '\sqrt[3]{\frac{a}{b}} + \sqrt[4]{\frac{c}{d}}',
            ],
        ];
    }

    #[DataProvider('repairedFormulas')]
    public function test_it_puts_braces_around_the_radicand(string $formula, string $expected): void
    {
        $this->assertSame($expected, (new FormulaRepair())->repairFormula($formula));
    }

    public static function untouchedFormulas(): array
    {
        return [
            'already in braces' => ['\sqrt[4]{\frac{a}{b}}'],
            'a plain root' => ['\sqrt{x}+\sqrt[3]{y}'],
            'a shorthand fraction katex also rejects' => ['\sqrt[3]\frac43'],
            'a group that never closes' => ['\sqrt[4]\frac{0,5^2}{0,5^2+2\cdot 9,81'],
            'a prime that already has braces' => ["K^{\prime} + f'(x)"],
            'a derivative prime' => ["f'(x) = 2x"],
            'a longer command' => ['\sqrt[3]\fraction{a}{b}'],
        ];
    }

    #[DataProvider('untouchedFormulas')]
    public function test_it_leaves_other_formulas_alone(string $formula): void
    {
        $this->assertSame($formula, (new FormulaRepair())->repairFormula($formula));
    }

    public function test_it_only_touches_the_formulas_in_a_text(): void
    {
        $text = 'Zie \sqrt[3]\frac{a}{b} en `$$\sqrt[3]\frac{a}{b}$$` en `$$x^2$$`.';

        $this->assertSame(
            'Zie \sqrt[3]\frac{a}{b} en `$$\sqrt[3]{\frac{a}{b}}$$` en `$$x^2$$`.',
            (new FormulaRepair())->repairText($text)
        );
    }

    public function test_repairing_twice_changes_nothing_more(): void
    {
        $once = (new FormulaRepair())->repairFormula('\sqrt[4]\frac{a}{b}');

        $this->assertSame($once, (new FormulaRepair())->repairFormula($once));
    }
}
