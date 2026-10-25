<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

// Formulas ($$..$$ in questions, answers and tips) are converted with the katex CLI. katex rejects some input; one bad formula
// must not make a whole export fail, so these helpers return null instead of throwing and log what was rejected.
class Latex
{
    const TIMEOUT = 30;

    // MathML (<math>..</math>) for a TeX formula, or null when katex cannot convert it
    public static function toMathML(string $formula): ?string
    {
        $file = tempnam(sys_get_temp_dir(), 'tex');
        file_put_contents($file, $formula);

        try {
            $process = new Process([base_path('node_modules/katex/cli.js'), '--input', $file]);
            $process->setTimeout(self::TIMEOUT);
            $process->run();
        } catch (\Throwable $e) {
            self::warn("katex could not be run: " . $e->getMessage(), $formula);
            return null;
        } finally {
            @unlink($file);
        }

        $output = trim($process->getOutput());

        if ($process->isSuccessful() && preg_match('/\<math.+\<\/math\>/', $output, $match)) {
            return $match[0];
        }

        $reason = self::reason($process->getErrorOutput());
        self::warn("katex rejected the formula (exit {$process->getExitCode()}): $reason", $formula);

        return null;
    }

    // Word OMML for a TeX formula, or null when it cannot be converted
    public static function toOmml(string $formula, \XSLTProcessor $mathMlToOmml): ?string
    {
        $mathml = self::toMathML($formula);

        if ($mathml === null) {
            return null;
        }

        try {
            $xml = new \DOMDocument;
            $omml = @$xml->loadXML($mathml) ? $mathMlToOmml->transformToXML($xml) : false;

            $result = new \DOMDocument;

            if ($omml && @$result->loadXML($omml)) {
                return $result->saveXML($result->documentElement);
            }
        } catch (\Throwable $e) {
            self::warn("MathML to OMML failed: " . $e->getMessage(), $formula);
            return null;
        }

        self::warn("MathML to OMML gave no result", $formula);

        return null;
    }

    // The TeX source as text for a Word run. PHPWord output escaping is off in this app, so escape it here.
    public static function fallbackText(string $formula): string
    {
        return htmlspecialchars($formula, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    // the line of node's error output that says what katex did not like ("KaTeX parse error: ...")
    private static function reason(string $stderr): string
    {
        foreach (preg_split('/\R/', $stderr) as $line) {
            if (str_contains($line, 'KaTeX')) {
                return trim($line);
            }
        }

        return trim(strtok(trim($stderr), "\n") ?: 'no MathML in the katex output');
    }

    private static function warn(string $message, string $formula): void
    {
        Log::warning("Latex: $message; formula: " . mb_substr($formula, 0, 200));
    }
}
