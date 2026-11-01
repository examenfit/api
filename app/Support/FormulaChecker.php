<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

// Asks katex (scripts/check-formulas.js, the renderer the exports use) which formulas it cannot convert.
class FormulaChecker
{
    // [['index' => position in $formulas, 'message' => what katex says], ...]
    public function failures(array $formulas): array
    {
        $process = new Process(['node', base_path('scripts/check-formulas.js')]);
        $process->setInput(json_encode(array_map('strval', array_values($formulas))));
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('node failed: ' . trim($process->getErrorOutput()));
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}
