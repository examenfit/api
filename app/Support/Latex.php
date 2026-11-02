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
        $process = self::runKatex($formula);

        if ($process === null) {
            return null;
        }

        $mathMl = self::readMathMl($process);

        if ($mathMl === null) {
            self::logRejection($process, $formula);
        }

        return $mathMl;
    }

    // Word OMML for a TeX formula, or null when it cannot be converted
    public static function toOmml(string $formula, \XSLTProcessor $mathMlToOmml): ?string
    {
        $mathml = self::toMathML($formula);

        if ($mathml === null) {
            return null;
        }

        try {
            $omml = self::convertToOmml($mathml, $mathMlToOmml);
        } catch (\Throwable $e) {
            self::warn("MathML to OMML failed: " . $e->getMessage(), $formula);
            return null;
        }

        if ($omml === null) {
            self::warn("MathML to OMML gave no result", $formula);
        }

        return $omml;
    }

    // the finished process, or null when node could not even be started (the cause is logged)
    private static function runKatex(string $formula): ?Process
    {
        $file = tempnam(sys_get_temp_dir(), 'tex');
        file_put_contents($file, $formula);

        try {
            $process = new Process([base_path('node_modules/katex/cli.js'), '--input', $file]);
            $process->setTimeout(self::TIMEOUT);
            $process->run();

            return $process;
        } catch (\Throwable $e) {
            self::warn("katex could not be run: " . $e->getMessage(), $formula);
            return null;
        } finally {
            @unlink($file);
        }
    }

    private static function readMathMl(Process $process): ?string
    {
        $isConverted = $process->isSuccessful()
            && preg_match('/\<math.+\<\/math\>/', trim($process->getOutput()), $match);

        return $isConverted ? $match[0] : null;
    }

    private static function logRejection(Process $process, string $formula): void
    {
        $reason = self::reason($process->getErrorOutput());
        self::warn("katex rejected the formula (exit {$process->getExitCode()}): $reason", $formula);
    }

    // null when the MathML or the stylesheet output is not well-formed XML
    private static function convertToOmml(string $mathml, \XSLTProcessor $mathMlToOmml): ?string
    {
        $xml = new \DOMDocument;
        $omml = @$xml->loadXML($mathml) ? $mathMlToOmml->transformToXML($xml) : false;
        $result = new \DOMDocument;

        return ($omml && @$result->loadXML($omml)) ? $result->saveXML($result->documentElement) : null;
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
