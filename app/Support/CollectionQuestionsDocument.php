<?php

namespace App\Support;

use App\Models\Collection;
use App\Models\Question;
use App\Models\Topic;

use DateTime;
use DateTimeZone;

use Illuminate\Support\Facades\Log;

use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\SimpleType\Jc;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class CollectionQuestionsDocument
{
    public $collection = null;
    public $document = null;
    public $sections = [];
    public $dashboardUrl;

    public function __construct()
    {
        $this->dashboardUrl = config('app.dashboard_url');
    }

    function createDocument($collection)
    {
        Log::info($collection->hash_id);
        $this->setCollection($collection);
        $this->initDocument();
        $this->setHeader();
        $this->setFooter();
        $this->processQuestions();
    }

    function setCollection($collection)
    {
        $this->collection = $collection;
        $this->collection->load([
            'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.topic',
            'questions.topic.exam',
            'questions.topic.exam.course',
        ]);
    }

    function initDocument()
    {
        $this->document = new \PhpOffice\PhpWord\PhpWord();
        $this->initMathML();
        $this->initStyling();
        $this->setDocumentInfo();
    }

    function initStyling()
    {
        $this->document->setDefaultFontSize(12);
        $this->document->setDefaultParagraphStyle([
            'lineHeight' => 1.15,
        ]);
        $this->document->addTitleStyle(1, ['bold' => true, 'size' => 14]);
        $this->document->addTitleStyle(2, ['bold' => true, 'size' => 12]);
        $this->document->addTableStyle('questionTableStyle', [
            'borderColor' => 'D3DAE6',
            'borderSize'  => 1,
            'cellMargin'  => 75,
        ]);
    }

    function initMathML()
    {
        $math_xsl = storage_path("app/mml2omml.xsl");
        // MathML to OMML (Office Math Markup Language) processors
        $this->mathStyleSheet = new \DOMDocument;
        $this->mathStyleSheet->load($math_xsl);
        $this->XSLTProcessor = new \XSLTProcessor;
        $this->XSLTProcessor->importStyleSheet($this->mathStyleSheet);
    }

    function setDocumentInfo()
    {
        $properties = $this->document->getDocInfo();
        $properties->setCreator('ExmenFit');
        $properties->setCompany('ExmenFit');
        $properties->setTitle($this->collection->name);
        $properties->setDescription("Lijst aangemaakt in ExamenFit");
    }

    function setHeader()
    {
        //$header = $this->section->addHeader();
        //$header->addText('Collection');
    }

    function setFooter()
    {
        // ...
    }

    function addSection()
    {
        if (count($this->sections)) {
            //$this->currentSection()->addPageBreak();
        } else {
            $section = $this->document->addSection([
                'marginTop' => 1200,
                'marginRight' => 1200,
                'marginBottom' => 1200,
                'marginLeft' => 1200,
            ]);
            $this->sections[] = $section;
        }
    }

    function processQuestions()
    {
        $topic_id = 0;
        $this->addSection();
        $this->addCollectionTitle($this->collection);
        foreach ($this->collection['questions'] as $question) {
            $topic = $question->topic;
            if ($topic_id !== $topic->id) {
                $topic_id = $topic->id;
                $this->addTopic($topic);
            }
            $this->addQuestion($question);
        }
        $this->addCollectionEnd();
    }

    function addTopic($topic)
    {
        Log::info($topic->name);

        $this->addSection();

        $this->addTopicTitle($topic);

        // Title

        // Attachments
        $this->addAttachments($topic->attachments, null, 'small');

        // Introduction
        $textRun = $this->currentSection()->addTextRun();
        $this->formatText($topic->introduction, $textRun);

        // Attachments
        $this->addAttachments($topic->attachments, null, 'large', 1.3);
    }

    function addCollectionTitle($collection)
    {
        $questions = 0;
        $points = 0;
        $time_in_minutes = 0;

        foreach ($this->collection['questions'] as $question) {
            $questions += 1;
            $points += $question->points;
            $time_in_minutes += $question->time_in_minutes;
        }

        $ts = new DateTime();
        $ts->setTimeZOne(new DateTimeZone('CET'));
        $timestamp = $ts->format('Y-m-d H:i:s');

        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'borderBottomSize' => 32
        ]);
        $row = $table->addRow();

        $left = $row->addCell();
        $left->addText('ExamenFit', ['size' => 32]);
        $left->addText('examenvragen op maat', ['size' => 14, 'bold' => true]);

        $right = $row->addCell();
        $right->addText($this->collection->name, ['size' => 16, 'bold' => true], ['align' => 'right']);
        $txt = $right->addTextRun([
            'align' => 'right',
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(0),
            'spacing' => 120,
            'lineHeight' => 2,
        ]);
        $txt->addText("$questions vragen");
        $txt->addText('  |  ');
        $txt->addText("$points punten");
        $txt->addText('  |  ');
        $txt->addText("$time_in_minutes min.");

        $right->addText("Download: {$timestamp}", ['size' => 10, 'italic' => true], ['align' => 'right']);
        $section->addTextRun()->addTextBreak(2);
    }

    function addCollectionEnd()
    {
        $section = $this->currentSection();
        $section->addTextRun()->addTextBreak(2);
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'borderTopSize' => 32
        ]);
        $row = $table->addRow();

        $left = $row->addCell();
        $left->addText('ExamenFit', ['size' => 32]);

        $right = $row->addCell();
        $right->getStyle()->setVAlign('center');
        $txt = $right->addTextRun(['align' => 'right']);
        $txt->addText('Met ExamenFit kun je eenvoudig vragen samenstellen en gericht vragen oefenen.', ['size' => 8]);
        $txt->addtextBreak();
        $txt->addText('Kijk op ', ['size' => 8]);
        $txt->addText('www.examenfit.nl', ['bold' => true, 'size' => 8]);
        $txt->addText(' voor meer informatie.', ['size' => 8]);
    }

    function addTopicTitle($topic)
    {
        $title = $topic->name;
        $course = $topic->exam->course->name;
        $level = $topic->exam->level;
        $year = $topic->exam->year;
        $term = $topic->exam->term;

        //$this->currentSection()->addTitle($title);
        $exam = $term === 1 ? "{$year}-I" : "{$year}-II";
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'borderBottomSize' => 6
        ]);
        $row = $table->addRow();

        $left = $row->addCell();
        $left->addText($title, ['size' => 14, 'bold' => true]);

        $right = $row->addCell();
        $txt = $right->addTextRun(['align' => 'right']);
        $txt->addText($course, ['bold' => true]);
        $txt->addText(' | ');
        $txt->addText($level, ['bold' => true]);
        $txt->addText(' | ');
        $txt->addText($exam, ['bold' => true]);

        $textRun = $this->currentSection()->addTextRun();
        $textRun->addTextBreak();
    }

    function addAttachments($attachments, $parent = null, $type = null)
    {
        $textBoxStyleOptions = [];
        $titleHeight = 20;

        if (!$parent) {
            $parent = $this->currentSection();
        }

        // If the image is 'small', we will inline the image on the right side.
        if ($type === 'small') {
            $textBoxStyleOptions = [
                'wrappingStyle' => 'square',
                'positioning' => 'absolute',
                'posHorizontal'    => \PhpOffice\PhpWord\Style\Image::POSITION_HORIZONTAL_RIGHT,
                'posHorizontalRel' => 'margin',
                'posVerticalRel' => 'line',
            ];
            $attachments = $attachments->filter(fn ($item) => $item->image_width < 200);
        }
        if ($type === 'large') {
            $attachments = $attachments->filter(fn ($item) => $item->image_width >= 200);
        }

        foreach ($attachments as $attachment) {
            // Create textbox
            $textBox = $parent->addTextBox(
                array_merge([
                    'width' => $attachment->image_width + 20, // Textbox padding compensation
                    'height' => $attachment->image_height + $titleHeight,
                    'borderColor' => '#FFFFFF',
                ], $textBoxStyleOptions)
            );

            $textRun = $textBox->addTextRun();

            // Add title to the textbox
            $textRun->addText($attachment->name . "\n", ['bold' => true]);

            // Add image to the textbox
            //Log::info($attachment->url);
            $textRun->addImage($attachment->url, [
                'width' => $attachment->image_width,
                'height' => $attachment->image_height,
            ]);
        }
    }

    function addQuestion($question)
    {
        $section = $this->currentSection();

        $this->addAttachments($question->attachments);

        // Introduction
        $textRun = $section->addTextRun(['alignment' => 'left']);
        $this->formatText($question->introduction, $textRun);
        $textRun->addTextBreak(1);

        // Question text
        $this->addQuestionTitle($question);
        $textRun = $section->addTextRun(['alignment' => 'left']);
        $textRun->addTextBreak(1);
        $this->formatText($question->text, $textRun);
        $textRun->addTextBreak(1);

        // Question type
        //$textRun->addTextBreak(1);
        //$textRun->addText('Vraagtype: ', ['bold' => true, 'color' => '0070C0']);
        //$textRun->addText($question->questionType->name, ['color' => '0070C0']);

        // Domains
        //$textRun->addTextBreak(1);
        //$textRun->addText('Domeinen: ', ['bold' => true, 'color' => '0070C0']);

        //$domains = [];
        //foreach ($question->domains as $domain) {
        //$domains[] = $domain->name;
        //}
        //$textRun->addText(implode(', ', $domains), ['color' => '0070C0']);

        // Tags
        //$textRun->addTextBreak(1);
        //$textRun->addText('Trefwoorden: ', ['bold' => true, 'color' => '0070C0']);
        //$textRun->addText(implode(', ', $question->tags->pluck('name')->toArray()), ['color' => '0070C0']);

        // Add QrCode
        $this->addQrCode($question);

        // addTextBreak
        $textRun = $section->addTextRun(['alignment' => 'left']);
        $textRun->addTextBreak(1);
    }

    function addQuestionTitle($question)
    {
        //$this->currentSection()->addTitle($title);
        $COMPLEXITY = [
            'low' => 'Eenvoudig',
            'average' => 'Gemiddeld',
            'high' => 'Moeilijk',
        ];
        $complexity = $COMPLEXITY[$question->complexity];
        $section = $this->currentSection();
        $section->addTitle("Vraag {$question->number}", 2);
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'marginBottom' => 100
        ]);
        $row = $table->addRow();

        $left = $row->addCell();
        $txt = $left->addTextRun(['align' => 'left']);
        $txt->addText("{$question->points} punten", ['size' => 10, 'bold' => true]);

        $right = $row->addCell();
        $txt = $right->addTextRun(['align' => 'right']);
        $txt->addText("{$question->time_in_minutes} min.", ['size' => 10, 'bold' => true]);
        $txt->addText('   ');
        $txt->addText("$complexity", ['size' => 10, 'bold' => true]);
    }

    function createQrUrl($question)
    {
        $collection_id = $this->collection->hash_id;
        if ($question) {
            $question_id = $question->hash_id;
            $topic_id = $question->topic->hash_id;
            $url = "{$this->dashboardUrl}/c/{$collection_id}/{$topic_id}/{$question_id}";
        } else {
            $url = "{$this->dashboardUrl}/a/{$collection_id}/";
        }
        Log::info($url);

        return $url;
    }

    function addQrCode($question)
    {
        $url = $this->createQrUrl($question);

        $hash = md5($url);
        $tmpdir = sys_get_temp_dir();

        $tmpfile = "{$tmpdir}/{$hash}.png";
        QrCode::format('png')->generate($url, $tmpfile);

        $section = $this->currentSection();
        $table = $section->addTable([
            'Spacing' => 0,
            'cellSpacing' => 0,
            'marginBottom' => 100
        ]);
        $row = $table->addRow();

        $left = $row->addCell(1500);
        $left->addImage($tmpfile, [
            'width' => 54,
            'height' => 54
        ]);
        $right = $row->addCell(4000, ['valign' => 'center']);
        $right->addText('Gebruik de QR-code om na te kijken of om tips te krijgen', ['bold' => true]);
    }

    function formatText($text, &$textRun = null, $textStyle = null)
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
                    $type = $chunk['type'];
                    if ($type === 'formula') {
                        $textRun->addText($chunk['result'], $textStyle, ['alignment' => 'left']);
                        // Add space if the chunk only has a formula
                        // Otherwise the formula is shown centered.
                        // if (count($chunks) === 1) {
                        //     $textRun->addText(' ');
                        // }
                    }
                    if ($type === 'boldStyle') {
                        $textRun->addText(
                            $chunk['result'],
                            array_merge(['bold' => true], $textStyle ?? [])
                        );
                    }
                } else {
                    $textRun->addText($chunk, $textStyle);
                }
            }

            // Add line break, except for the last line
            if ($index < count($lines) - 1) {
                $textRun->addTextBreak(1);
            }
        }
    }

    function latexFormula($formula)
    {
        $xml = new \DOMDocument;
        $xml->loadXML($this->latexToMathML($formula));

        $omml = $this->XSLTProcessor->transformToXML($xml);

        $t_omml = new \DOMDocument;
        $t_omml->loadXML($omml);

        return $t_omml->saveXML($t_omml->documentElement);
    }

    function latexToMathML($formula)
    {
        //Log::info("latexToMathML: $formula");

        // Write formula to file
        $tempFile = tmpfile();
        fwrite($tempFile, $formula);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        //Log::info("KaTeX...");
        //Log::info("tempFilePath: $tempFilePath");

        // Run KaTeX NodeJS script
        $result = trim(shell_exec(base_path("/node_modules/katex/cli.js --input {$tempFilePath}")));

        //Log::info("result: $result");

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
    function currentSection()
    {
        return $this->sections[(count($this->sections) - 1)];
    }

    function saveDocument($file, $type = 'docx')
    {
        Log::info("{$file} ({$type})");
        switch ($type) {
            case 'pdf':
                return $this->savePDF($file);
            case 'docx':
                return $this->saveDocx($file);
            default:
                throw new Exception('format not supported');
        }
    }

    public static function storage_file($collection, $type)
    {
        $filename = $collection->hash_id . $type;
        return storage_path("app/public/question-correction/{$filename}");
    }

    public static function tmp_file($collection, $type)
    {
        $filename = $collection->hash_id . $type;
        return "/tmp/{$filename}";
    }

    function saveDocx($file)
    {
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($this->document, 'Word2007');
        $writer->save($file);
        //Log::info($file);
    }

    function savePDF($file)
    {
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($this->document, 'PDF');
        $writer->save($file);
        //Log::info($file);
    }
}
