<?php

namespace App\Support;

// Hand-made corrections for the few stored texts whose formulas katex rejects and that no rule can repair
// (stray or missing delimiters, math symbols in italic text). Found with formulas:check on 2026-11-01; see
// work/todo/rfc-fix-broken-formulas-in-data. Keyed by row, so a text is only touched where we looked at it.
class KnownTextFixes
{
    // 'table.column#id' => [[search, replace], ...]
    private const FIXES = [
        // `$$0,00025\text{ W m}^{-2}\text{nm}$$^{-1}$$`: the unit's exponent got a second $$
        'answer_sections.correction#7192' => [['\text{nm}$$^{-1}$$', '\text{nm}^{-1}$$']],
        // closing backtick missing after the formula
        'answer_sections.text#7724' => [['\rm O-15$$ is', '\rm O-15$$` is']],
        'tips.text#3789' => [['$$U$$ in', '$$U$$` in']],
        // katex has no \sim or \cdot in text mode: between $...$ they are math again
        'answer_sections.correction#14592' => [['met \sim of \cdot of', 'met $\sim$ of $\cdot$ of']],
    ];

    public function apply(string $table, string $column, int|string $id, string $text): string
    {
        foreach (self::FIXES["$table.$column#$id"] ?? [] as [$search, $replace]) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }
}
