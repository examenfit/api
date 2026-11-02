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
        $zip = self::openDocx($file);

        if ($zip === null) {
            return;
        }

        $count = self::replaceMathSettings($zip);
        $zip->close();

        self::warnWhenNotReplaced($file, $count);
    }

    private static function openDocx(string $file): ?\ZipArchive
    {
        $zip = new \ZipArchive();

        if ($zip->open($file) === true) {
            return $zip;
        }

        Log::warning("DocxSettings: cannot open $file");

        return null;
    }

    // how many of the settings were found and replaced
    private static function replaceMathSettings(\ZipArchive $zip): int
    {
        $settings = $zip->getFromName('word/settings.xml');

        if ($settings === false) {
            return 0;
        }

        $count = 0;
        $settings = str_replace(array_keys(self::MATH_SETTINGS), array_values(self::MATH_SETTINGS), $settings, $count);
        $zip->addFromString('word/settings.xml', $settings);

        return $count;
    }

    // a cosmetic fix must not break an export, but a changed library output should be noticed
    private static function warnWhenNotReplaced(string $file, int $count): void
    {
        $expected = count(self::MATH_SETTINGS);

        if ($count !== $expected) {
            Log::warning("DocxSettings: expected $expected math settings in $file, replaced $count");
        }
    }
}
