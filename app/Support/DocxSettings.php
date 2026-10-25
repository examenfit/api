<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class DocxSettings
{
    // PHPWord writes math settings that centre formulas. Our documents want them left aligned with a 1 cm indent
    // (this is what our former PHPWord fork changed, see work/done/rfc-api-phpword-fork.txt).
    const MATH_SETTINGS = [
        '<m:lMargin m:val="0"/>' => '<m:lMargin m:val="567"/>',
        '<m:defJc m:val="centerGroup"/>' => '<m:defJc m:val="left"/>',
    ];

    public static function alignFormulasLeft(string $file): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            Log::warning("DocxSettings: cannot open $file");
            return;
        }

        $settings = $zip->getFromName('word/settings.xml');
        $count = 0;

        if ($settings !== false) {
            $settings = str_replace(array_keys(self::MATH_SETTINGS), array_values(self::MATH_SETTINGS), $settings, $count);
            $zip->addFromString('word/settings.xml', $settings);
        }

        $zip->close();

        // a cosmetic fix must not break an export, but a changed library output should be noticed
        if ($count !== count(self::MATH_SETTINGS)) {
            Log::warning("DocxSettings: expected " . count(self::MATH_SETTINGS) . " math settings in $file, replaced $count");
        }
    }
}
