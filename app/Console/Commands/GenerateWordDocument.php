<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\Element\TextRun;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateWordDocument extends Command
{
    protected $signature = 'ef:document {collection}';
    protected $description = 'Create a Word-document based on a collection';
    public $collection = null;
    public $processor = null;
    public $sections = [];
    public $questionNumber = 1;
    public $answerURL = "https://app.examenfit.nl/a";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->processor = new \PhpOffice\PhpWord\PhpWord();

        // Add fonts and styles
        $this->processor->setDefaultFontSize(12);
        $this->processor->setDefaultParagraphStyle([
            'lineHeight' => 1.15,
            // 'indent' => 1.5,
        ]);
        $this->processor->addTitleStyle(1, ['bold' => true, 'size' => 14]);

        $this->processor->addTableStyle('questionTableStyle', [
            'borderColor' => 'ffffff',
            // 'borderColor' => '000000', // Debug
            'borderSize'  => 0,
            'cellMargin'  => 0,
        ]);

        // MathML to OMML (Office Math Markup Language) processors
        $this->mathStyleSheet = new \DOMDocument;
        $this->mathStyleSheet->load(storage_path("app/mml2omml.xsl"));

        $this->XSLTProcessor = new \XSLTProcessor;
        $this->XSLTProcessor->importStyleSheet($this->mathStyleSheet);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->getCollection($this->argument('collection'));
        $this->setDocumentInfo();
        $this->setHeader();
        $this->setFooter();
        $this->processQuestions();

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->processor, 'Word2007');
        $time = time();
        $objWriter->save("helloWorld-{$time}.docx");
    }

    public function getCollection($id)
    {
        $this->collection = Collection::with([
            'author',
            'questions.attachments',
            'questions.topic.attachments',
        ])->findOrFail($id);
    }

    public function setDocumentInfo()
    {
        $properties = $this->processor->getDocInfo();
        $properties->setCreator($this->collection->author->fullName . ' in ExamenFit');
        $properties->setCompany('ExamenFit');
        $properties->setTitle($this->collection->name . ' (ExamenFit)');
        $properties->setDescription(
            "Vragenset '{$this->collection->name}' voor een examen aangemaakt in ExamenFit"
        );
    }

    /**
     * Create a new document section
     */
    public function addSection()
    {
        if (count($this->sections)) {
            $this->currentSection()->addPageBreak();
        }

        $section = $this->processor->addSection([
            'marginTop' => 1200,
            'marginRight' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1200,
        ]);

        $this->sections[] = $section;
    }

    public function setHeader()
    {
        // $header = $this->section->addHeader();
        // $header->addText('ExamenFit examen');
    }

    public function setFooter()
    {

    }

    public function processQuestions()
    {
        $questions = $this->collection->questions;
        $currentTopic = null;

        foreach ($questions as $question) {
            if ($question->topic->id != $currentTopic) {
                $currentTopic = $question->topic->id;
                $this->addTopic($question->topic);
            }

            $this->addQuestion($question);
        }
    }

    public function addTopic($topic)
    {
        $this->addSection();

        // Title
        $this->currentSection()->addTitle($topic->name);

        // Attachments
        $this->addAttachments($topic->attachments, null, 'small');

        // Introduction
        $textRun = $this->currentSection()->addTextRun(['indent' => 1.45]);
        $this->formatText($topic->introduction, $textRun);

        // Attachments
        $this->addAttachments($topic->attachments, null, 'large', 1.3);

        // Add break
        $this->currentSection()->addTextBreak(1);
    }

    public function addAttachments($attachments, $parent = null, $type = null, $indent = 0)
    {
        $textBoxStyleOptions = [];
        $titleHeight = 20;

        if (!$parent) {
            $parent = $this->currentSection();
        }

        // When passed an indentation to the textrunner, the width of
        // the textbox needs to be compensated in order to show the
        // full width of the image.
        $textBoxIndentCompensation = $indent * 40;

        // If the image is 'small', we will inline the image on the right side.
        if ($type === 'small') {
            $textBoxStyleOptions = [
                'wrappingStyle' => 'square',
                'positioning' => 'absolute',
                'posHorizontal'    => \PhpOffice\PhpWord\Style\Image::POSITION_HORIZONTAL_RIGHT,
                'posHorizontalRel' => 'margin',
                'posVerticalRel' => 'line',
            ];
        }

        // Filter images based on the width (small or large)
        switch ($type) {
            case 'small':
                $attachments = $attachments->filter(fn ($item) => $item->image_width < 200);
                break;
            case 'large':
                $attachments = $attachments->filter(fn ($item) => $item->image_width >= 200);
                break;
        }

        foreach ($attachments as $attachment) {
            // Create textbox
            $textBox = $parent->addTextBox(
                array_merge([
                    'width' => $attachment->image_width + $textBoxIndentCompensation,
                    'height' => $attachment->image_height + $titleHeight,
                    'borderColor' => '#FFFFFF',
                ], $textBoxStyleOptions)
            );

            $textRun = $textBox->addTextRun(['indent' => $indent]);

            // Add title to the textbox
            $textRun->addText($attachment->name."\n", ['bold' => true]);

            // Add image to the textbox
            $textRun->addImage($attachment->url, [
                'width' => $attachment->image_width,
                'height' => $attachment->image_height,
            ]);
        }
    }

    public function addQuestion($question)
    {
        // Create table
        $table = $this->currentSection()->addTable('questionTableStyle');

        // First row (QR Code + introduction)
        $table->addRow();

        // QR Code
        $cell = $table->addCell(1050, ['gridSpan' => 2, 'valign' => 'bottom']);

        $textRun = $cell->addTextRun();
        $textRun->addImage($this->getQRCode($question), [
            'width' => 35,
            'height' => 35,
            'indent' => 0,
        ]);

        // Introduction
        $cell = $table->addCell();
        $this->addAttachments($question->attachments, $cell);

        $textRun = $cell->addTextRun();
        $this->formatText($question->introduction, $textRun);

        // Second row (Points, number + question)
        $table->addRow();

        // Points and number
        $cell = $table->addCell(525);
        $cell->addText(
            $question->points.'p',
            ['size' => 8],
            ['spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(2)]
        );

        $table->addCell(525)->addText($this->questionNumber, ['bold' => true]);

        // Question itself
        $cell = $table->addCell();
        $textRun = $cell->addTextRun(['indent' => 0]);
        $this->formatText($question->text, $textRun);

        // Add break
        $this->currentSection()->addTextBreak(1);

        // Increase question number
        $this->questionNumber++;
    }

    public function getQRCode($question)
    {
        $url = "{$this->answerURL}/{$this->collection->hash_id}/{$question->hash_id}";

        return QrCode
            ::format('png')
            ->size(100)
            ->generate($url);
    }

    public function formatText($text, &$textRun = null)
    {
        // Convert individual lines into seperate elements
        $lines = explode(PHP_EOL, $text);

        foreach ($lines as $index => $line) {

            // Match formula (`$$LaTeX$$`) and **bold** tekst.
            preg_match_all('/(`\$\$(.+?)\$\$`|\*\*(.+?)\*\*)/', $line, $results);

            // Break pieces on tags, but perserve values
            $arr = preg_split('/(`\$\$.+?\$\$`|\*\*.+?\*\*)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

            $chunks = [];
            foreach ($arr as $key => $result) {
                if (in_array($result, $results[1])) {

                    // Formula
                    if (preg_match('/`\$\$(.+)?\$\$`/', $result, $match)) {
                        $chunks[$key] = [
                            'type' => 'formula',
                            'result' => $this->latexFormula($match[1]),
                        ];
                    }

                    // Bold text
                    elseif (preg_match('/\*\*(.+)?\*\*/', $result, $match)) {
                        $chunks[$key] = [
                            'type' => 'boldStyle',
                            'result' => $match[1],
                        ];
                    }
                }

                // Text has no complex values, just a line of text.
                else {
                    $chunks[] = $result;
                }
            }

            // Clear empty values and reset keys.
            $chunks = array_values(array_filter($chunks));

            foreach ($chunks as $chunk) {
                if (is_array($chunk)) {
                    switch ($chunk['type']) {
                        case 'formula':
                            $textRun->addText($chunk['result']);

                            // Add space if the chunk only has a formula
                            // Otherwise the formula is shown centered.
                            if (count($chunks) === 1) {
                                $textRun->addText(' ');
                            }
                            break;
                        case 'boldStyle':
                            $textRun->addText($chunk['result'], ['bold' => true]);
                        break;
                    }
                } else {
                    $textRun->addText($chunk);
                }
            }

            // Add line break, except for the last line
            if ($index < count($lines) - 1) {
                $textRun->addTextBreak(1);
            }
        }
    }

    public function latexFormula($formula)
    {
        $xml = new \DOMDocument;
        $xml->loadXML($this->latexToMathML($formula));

        $omml = $this->XSLTProcessor->transformToXML($xml);

        $t_omml = new \DOMDocument;
        $t_omml->loadXML($omml);

        return $t_omml->saveXML($t_omml->documentElement);
    }

    public function latexToMathML($formula)
    {
        // Write formula to file
        $tempFile = tmpfile();
        fwrite($tempFile, $formula);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Run KaTeX NodeJS script
        $result = trim(shell_exec(base_path("/node_modules/katex/cli.js --input {$tempFilePath}")));

        // Grep MathML XML
        preg_match('/\<math.+\<\/math\>/', $result, $match);

        // Delete file
        fclose($tempFile);

        // Return MathML XML
        return $match[0];
    }

    /**
     * Return current document section
     */
    public function currentSection()
    {
        return $this->sections[
            (count($this->sections) - 1)
        ];
    }

}
