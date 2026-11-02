<?php

namespace App\Support;

// Repairs stored `$$formulas$$` that katex 0.19 rejects but katex 0.9 (the old app) accepted, and plain typos.
class FormulaRepair
{
    private const FORMULA = '/`\$\$(.+?)\$\$`/';

    // \sqrt[n], then \frac that is not the start of a longer command such as \fraction
    private const SQRT_FRAC = '/\\\\sqrt(\[[^\]]*\])\s*\\\\frac(?![a-zA-Z])/';

    public function repairText(string $text): string
    {
        return preg_replace_callback(
            self::FORMULA,
            fn (array $match) => '`$$' . $this->repairFormula($match[1]) . '$$`',
            $text
        );
    }

    public function repairFormula(string $formula): string
    {
        return $this->repairTypos($this->bracedRadicands($formula));
    }

    // K^' -> K^{\prime}: katex wants a group after ^ (katex 0.9 let it pass); \texitt is a typo for \textit
    private function repairTypos(string $formula): string
    {
        return preg_replace(
            ["/\^\s*'/", '/\\\\texitt(?![a-zA-Z])/'],
            ['^{\\\\prime}', '\\\\textit'],
            $formula
        );
    }

    // \sqrt[4]\frac{a}{b} -> \sqrt[4]{\frac{a}{b}}: katex 0.19 wants the radicand in braces
    private function bracedRadicands(string $formula): string
    {
        $result = '';
        $position = 0;

        while (($found = $this->findUnbracedRadicand($formula, $position)) !== null) {
            $result .= substr($formula, $position, $found['start'] - $position) . $found['replacement'];
            $position = $found['end'];
        }

        return $result . substr($formula, $position);
    }

    private function findUnbracedRadicand(string $formula, int $from): ?array
    {
        if (!preg_match(self::SQRT_FRAC, $formula, $match, PREG_OFFSET_CAPTURE, $from)) {
            return null;
        }

        $start = $match[0][1];
        $afterFrac = $start + strlen($match[0][0]);
        $groups = $this->readTwoGroups($formula, $afterFrac);

        if ($groups === null) {
            // not repairable here (a shorthand like \frac43, or a group that never closes): leave it, look further
            return $this->findUnbracedRadicand($formula, $afterFrac);
        }

        $replacement = '\\sqrt' . $match[1][0] . '{\\frac{' . $groups['numerator'] . '}{' . $groups['denominator'] . '}}';

        return ['start' => $start, 'end' => $groups['end'], 'replacement' => $replacement];
    }

    private function readTwoGroups(string $text, int $position): ?array
    {
        $numerator = $this->readGroup($text, $position);
        $denominator = $numerator === null ? null : $this->readGroup($text, $numerator['end']);

        if ($denominator === null) {
            return null;
        }

        return ['numerator' => $numerator['content'], 'denominator' => $denominator['content'], 'end' => $denominator['end']];
    }

    // the {...} group that starts at the first non-space character from $position on; braces may nest
    private function readGroup(string $text, int $position): ?array
    {
        $open = $position + strspn($text, " \t", $position);

        if (($text[$open] ?? '') !== '{') {
            return null;
        }

        $depth = 0;

        for ($index = $open; $index < strlen($text); $index++) {
            if ($this->isEscape($text[$index])) {
                $index++;
                continue;
            }

            $depth += $this->braceDepthChange($text[$index]);

            if ($depth === 0) {
                return ['content' => substr($text, $open + 1, $index - $open - 1), 'end' => $index + 1];
            }
        }

        return null;
    }

    // \{ and \} are literal braces: the character after a backslash does not count
    private function isEscape(string $character): bool
    {
        return $character === '\\';
    }

    private function braceDepthChange(string $character): int
    {
        return match ($character) {
            '{' => 1,
            '}' => -1,
            default => 0,
        };
    }
}
