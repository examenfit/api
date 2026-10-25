<?php

namespace Tests\Feature;

use App\Support\DocxSettings;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

// Our Word exports want formulas left aligned with a 1 cm indent; the official PHPWord writes them centred.
// The assertions on the raw library output make a PHPWord upgrade that changes these settings visible.
class DocxSettingsTest extends TestCase
{
    private function settingsOf(string $file): string
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file));
        $xml = $zip->getFromName('word/settings.xml');
        $zip->close();

        return $xml;
    }

    public function test_formulas_are_aligned_left_with_a_one_cm_indent()
    {
        $phpWord = new PhpWord();
        $phpWord->addSection()->addText('x');
        $file = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($file);

        $raw = $this->settingsOf($file);
        $this->assertStringContainsString('<m:defJc m:val="centerGroup"/>', $raw);
        $this->assertStringContainsString('<m:lMargin m:val="0"/>', $raw);

        DocxSettings::alignFormulasLeft($file);

        $fixed = $this->settingsOf($file);
        $this->assertStringContainsString('<m:defJc m:val="left"/>', $fixed);
        $this->assertStringContainsString('<m:lMargin m:val="567"/>', $fixed);
        $this->assertStringNotContainsString('centerGroup', $fixed);

        // the file is still a valid docx that PHPWord can read
        $this->assertNotNull(IOFactory::load($file));

        unlink($file);
    }

    public function test_table_cells_do_not_get_nowrap_by_default_in_our_code()
    {
        // PHPWord 1.x writes <w:noWrap/> in every cell unless the cell style says noWrap => false
        $phpWord = new PhpWord();
        $row = $phpWord->addSection()->addTable()->addRow();
        $row->addCell(null, ['noWrap' => false])->addText('a');
        $file = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($file);

        $zip = new \ZipArchive();
        $zip->open($file);
        $this->assertStringNotContainsString('<w:noWrap/>', $zip->getFromName('word/document.xml'));
        $zip->close();
        unlink($file);
    }
}
