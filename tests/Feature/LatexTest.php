<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Support\CollectionCorrectionsDocument;
use App\Support\CollectionQuestionsDocument;
use App\Support\Latex;
use PhpOffice\PhpWord\IOFactory;
use Tests\TestCase;

// A formula that katex rejects must not make a whole Word export fail (it did: "Undefined array key 0").
// Needs node_modules/katex (npm ci), like the exports themselves.
class LatexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(base_path('node_modules/katex/cli.js'))) {
            $this->markTestSkipped('node_modules/katex is not installed (npm ci)');
        }
    }

    private function mathMlToOmml(): \XSLTProcessor
    {
        $xsl = new \DOMDocument;
        $xsl->load(storage_path('app/mml2omml.xsl'));
        $processor = new \XSLTProcessor;
        $processor->importStyleSheet($xsl);

        return $processor;
    }

    private function unsavedCollection(): Collection
    {
        $collection = new Collection();
        $collection->name = 'test';

        return $collection;
    }

    private function documentXmlOf($phpWord): string
    {
        $file = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($file);
        $zip = new \ZipArchive();
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($file);

        return $xml;
    }

    public function test_a_valid_formula_becomes_mathml_and_omml()
    {
        $this->assertStringContainsString('<math', Latex::toMathML('x^2 + \frac{1}{2}'));
        $this->assertStringContainsString('m:oMath', Latex::toOmml('x^2 + \frac{1}{2}', $this->mathMlToOmml()));
    }

    public function test_a_formula_katex_rejects_gives_null_and_does_not_throw()
    {
        $this->assertNull(Latex::toMathML('\frac{1'));
        $this->assertNull(Latex::toOmml('\frac{1', $this->mathMlToOmml()));
        $this->assertNull(Latex::toMathML('\nonexistentcommand{x}'));
    }

    public function test_fallback_text_is_xml_escaped()
    {
        // PHPWord output escaping is off in this app: raw < or & would break the docx
        $this->assertSame('a &lt; b &amp; &quot;c&quot;', Latex::fallbackText('a < b & "c"'));
    }

    public function test_questions_export_keeps_working_with_a_broken_formula()
    {
        $document = new CollectionQuestionsDocument();
        $document->collection = $this->unsavedCollection();
        $document->initDocument();
        $textRun = $document->document->addSection()->addTextRun();

        $document->formatText('voor `$$\frac{1$$` midden `$$a < b$$` na `$$x^2$$`', $textRun);

        $xml = $this->documentXmlOf($document->document);
        $this->assertTrue((new \DOMDocument)->loadXML($xml), 'document.xml is well-formed');
        $this->assertStringContainsString('\frac{1', $xml, 'the rejected formula is shown as TeX text');
        $this->assertSame(2, preg_match_all('/<m:oMath[ >]/', $xml), 'the two valid formulas are still equations');
    }

    public function test_corrections_export_keeps_working_with_a_broken_formula()
    {
        $document = new CollectionCorrectionsDocument();
        $document->collection = $this->unsavedCollection();
        $document->initDocument();
        $textRun = $document->document->addSection()->addTextRun();

        $document->formatText('antwoord `$$r = \sqrt[4]\frac{a}{b}$$` en `$$x^2$$`', $textRun);

        $xml = $this->documentXmlOf($document->document);
        $this->assertTrue((new \DOMDocument)->loadXML($xml));
        $this->assertStringContainsString('\sqrt[4]\frac{a}{b}', $xml);
        $this->assertSame(1, preg_match_all('/<m:oMath[ >]/', $xml));
    }
}
