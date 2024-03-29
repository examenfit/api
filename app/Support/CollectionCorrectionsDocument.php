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


class CollectionCorrectionsDocument
{
    public $collection = null;
    public $document = null;
    public $sections = [];
    public $dashboardUrl;

    public function __construct()
    {
        $this->skipPageBreak = false;
        $this->dashboardUrl = config('app.dashboard_url');
    }

    function showAppendixes($show = true) {
        $this->showAppendixes = $show;
    }

    function showQuestions($show = true) {
        $this->showQuestion = $show;
    }

    function createDocument($collection)
    {
        Log::info($collection->hash_id);
        $this->setCollection($collection);
        $this->initDocument();
        $this->setHeader();
        $this->setFooter();
        $this->processQuestions();
        //$this->processAppendixes();
    }

    function setCollection($collection)
    {
        $this->collection = $collection;
        $this->collection->load([
            'author',
            'questions' => fn ($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.topic',
            'questions.topic.exam',
            'questions.topic.exam.stream.course',
            'questions.topic.exam.stream.level',
            'questions.dependencies'
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
        $properties->setTitle($this->collection->name . ' - correctie');
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

    function addSection($isHeader = false)
    {
        if ($this->skipPageBreak) {
            /* do nothing */
        } else {
            if (count($this->sections)) {
                $this->currentSection()->addPageBreak();
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
        $this->skipPageBreak = $isHeader;
    }

    function processQuestions()
    {
        $topics = [];
        $topic_id = 0;
        $introduction = [];
        $attachments = [];
        $text = [];
        foreach ($this->collection['questions'] as $question) {
            $id = $question['id'];
            $text[$id] = true;
            $introduction[$id] = true;
            $attachments[$id] = true;

            foreach($question['dependencies'] as $dependency) {
                $pivot = $dependency['pivot'];
                $id = $pivot['question_id'];
                if ($pivot['introduction']) $introduction[$id] = true;
                if ($pivot['attachments']) $attachments[$id] = true;
            }

            $topic = $question->topic;
            if ($topic_id !== $topic->id) {
                $topic_id = $topic->id;
                $topics[] = $topic;
            }
        }
        $this->topics = $topics;

        $this->addSection(true);
        $this->addCollectionTitle($this->collection);
        foreach($topics as $topic) {
            $this->addTopic($topic);
            $first = TRUE;
            foreach($topic['questions'] as $question) {
                if (!$first) {
                    $this->addBreak();
                }
                $id = $question['id'];
                $this->addQuestion(
                    $question,
                    array_key_exists($id, $introduction),
                    array_key_exists($id, $attachments),
                    array_key_exists($id, $text)
                );
                $first = FALSE;
            }
        }
        $this->addCollectionEnd();
    }

    function addBreak()
    {
        $section = $this->currentSection();
        $txt = $section->addTextRun();
        $txt->addTextBreak(1);
    }

    function processAppendixes()
    {
        $appendixes = [];
        foreach ($this->collection['questions'] as $question) {
            $id = $question->id;
            if (count($question->appendixes) > 0) {
                $appendixes[$id] = true;
            }
            foreach($question['dependencies'] as $dependency) {
                $pivot = $dependency['pivot'];
                $id = $pivot['question_id'];
                if ($pivot['appendixes']) $appendixes[$id] = true;
            }
        }

        if (count($appendixes)) {
            $this->addSection(true);
            $this->addAppendixesTitle();
            $this->added = [];
            $first = null;
            //$this->addAppendixesTitle($this->collection);
            foreach($this->topics as $topic) {
                foreach($topic['questions'] as $question) {
                    $id = $question->id;
                    if (array_key_exists($id, $appendixes)) {
                        $this->addQuestionAppendixes($question);
                    }
                }
            }
        }
    }


    function addAppendixesTitle() {
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'borderBottomSize' => 12
        ]);
        $row = $table->addRow();

        $left = $row->addCell();
        $left->addText('Uitwerkbijlage', ['size' => 14, 'bold' => true]);
    }

    function addQuestionAppendixes($question) {
        foreach ($question['appendixes'] as $appendix) {
            $this->addAppendix($appendix);
        }
    }

    function addAppendix($appendix) {
        $titleHeight = 20;
        $id = $appendix->id;
        if (array_key_exists($id, $this->added)) {
            /* skip */
        } else {
            $attachment = $appendix;
            $question = $appendix->question;

            $parent = $this->currentSection();

            // Create textbox
            $textBox = $parent->addTextBox([
                'width' => $attachment->image_width + 20, // Textbox padding compensation
                'height' => $attachment->image_height + $titleHeight,
                'borderColor' => '#FFFFFF',
            ]);

            $textRun = $textBox->addTextRun();

            // Add title to the textbox
            $textRun->addText($attachment->name . "\n", ['bold' => true]);

            // Add image to the textbox
            Log::info($attachment->url);
            $scale = 1;
            $textRun->addImage($attachment->url, [
                'width' => $attachment->image_width * $scale,
                'height' => $attachment->image_height * $scale
            ]);
        }
    }

    function addTopic($topic)
    {
        Log::info($topic->name);

        $this->addSection();

        $this->addTopicTitle($topic);

        // Title

        // Attachments
        //$this->addAttachments($topic->attachments, null, 'small');

        // Attachments
        //$this->addAttachments($topic->attachments, null, 'large', 1.3);
    }

    function addCollectionTitle($collection)
    {
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'borderBottomSize' => 32
        ]);
        $row = $table->addRow();
        $cell = $row->addCell();

        $topics = count($this->collection->topics);
        $questions = 0;
        $points = 0;
        $time_in_minutes = 0;

        foreach ($this->collection['questions'] as $question) {
            $questions += 1;
            $points += $question->points;
            $time_in_minutes += $question->time_in_minutes;
        }

        $title = $cell->addTextRun();
        $title->addText($this->collection->name . ' - correctievoorschrift', ['size' => 16, 'bold' => true]);
        $txt = $cell->addTextRun();
        $txt->addText("$topics opgaven");
        $txt->addText('  |  ');
        $txt->addText("$questions vragen");
        $txt->addText('  |  ');
        $txt->addText("$points punten");

        $section->addTextRun()->addTextBreak(2);
    }

    function addCollectionEnd()
    {
    }

    function addTopicTitle($topic)
    {
        $title = $topic->name;
        $course = $topic->exam->stream->course->name;
        $level = $topic->exam->stream->level->name;
        $year = $topic->exam->year;
        $term = substr("III", 0, $topic->exam->term);

        //$this->currentSection()->addTitle($title);
        $exam = "{$year}-{$term}";
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
            Log::info($attachment->url);
            $textRun->addImage($attachment->url, [
                'width' => $attachment->image_width,
                'height' => $attachment->image_height,
            ]);
        }
    }

    function addQuestion($question, $use_introduction, $use_attachments, $use_text)
    {
        if (!$use_introduction && !$use_attachments && !$use_text) {
          return;
        }

        $section = $this->currentSection();

        if ($use_attachments) {
        //    $this->addAttachments($question->attachments);
        }

        // Question text
        $this->addQuestionTitle($question);

        if (count($question->answers)) {
          foreach($question->answers as $answer) {
            $nr = $question->number;
            if ($answer->remark) {
              $this->addRemark($answer->remark);
            }
            $txt = $this->currentSection()->addTextRun();
            $txt->addTextBreak(1);
            $txt->addText('Tussenantwoord(en)', ['bold' => true]);
            foreach($answer->sections as $step) {
              $points = $step->points;
              $correction = $step->correction;
              $this->addCorrection($points, $correction);
            }
            break;
          } 
        } else {
          $this->addNoCorrection();
        }
    }

    function addRemark($remark) {
        $txt = $this->currentSection()->addTextRun();
        $txt->addTextBreak(1);
        $txt->addText('Opmerkingen', ['bold' => true]);
        $txt->addTextBreak(1);
        $this->formatText($remark, $txt);
    }

    function addNoCorrection() {
        $section = $this->currentSection();
        $txt = $section->addTextRun();
        $txt->addText('Geen correctievoorschrift beschikbaar');
    }

    function addCorrection($points, $correction) {
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'cellMarginBottom' => 50,
            'cellMarginTop' => 50
        ]);
        $row = $table->addRow();

        $left = $row->addCell(600);
        $left->addText("{$points}pt", ['bold' => true]);

        $right = $row->addCell(4000);
        $txt = $right->addTextRun();
        $this->formatText($correction, $txt);
    }

    function addQuestionText($question) {
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'cellMargin' => 100,
            'marginBottom' => 100,
        ]);
        $row = $table->addRow();
        $cell = $row->addCell(5000, [
            'borderSize' => 12
        ]);
        $txt = $cell->addTextRun();
        $this->formatText($question->text, $txt);
    }

    function addQuestionTitle($question)
    {
        //$this->currentSection()->addTitle($title);
        $COMPLEXITY = [
            'low' => 'laag',
            'average' => 'gemiddeld',
            'high' => 'hoog',
        ];
        $section = $this->currentSection();
        $table = $section->addTable([
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'Spacing' => 0,
            'cellSpacing' => 0,
            'marginBottom' => 100
        ]);
        $row = $table->addRow();

        $left = $row->addCell(2500);
        $txt = $left->addTextRun(['align' => 'left']);
        $txt->addText("Vraag {$question->number} ", ['size' => 11, 'bold' => true]);
        $txt->addText(" {$question->points} punten", ['size' => 11]);
/*
        $right = $row->addCell(2500);
        $txt = $right->addTextRun(['align' => 'right']);
        $txt->addText("{$question->time_in_minutes} min.", ['size' => 11]);
        if ($question->complexiteit) {
          $complexity = 'complexiteit: '.$COMPLEXITY[$question->complexity];
          $txt->addText('   ');
          $txt->addText("$complexity", ['size' => 11]);
        }
*/
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
        $imageWidthMax = 380;
        $boldStyle = array_merge(['bold' => true], $textStyle ?? []);

        // Convert individual lines into seperate elements
        $lines = explode(PHP_EOL, $text);

        foreach ($lines as $index => $line) {

            $arr = preg_split('/(`\$\$.+?\$\$`|\*\*.+?\*\*|!\[.+?\]\(.+?\))/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

            $chunks = [];
            foreach ($arr as $key => $result) {

                // Formula
                if (preg_match('/`\$\$(.+)?\$\$`/', $result, $match)) {
                    $formula = $match[1];
                    $formatted = $this->latexFormula($formula);
                    $textRun->addText($formatted, $textStyle, ['alignment' => 'left']);
                }

                // Bold text
                elseif (preg_match('/\*\*(.+)?\*\*/', $result, $match)) {
                    $bold = $match[1];
                    $textRun->addText($bold, $boldStyle);
                }
                  
                elseif (preg_match('/!\[(.+?)\]\((.+?)\)/', $result, $match)) {

                    $caption = $match[1];
                    $image = $match[2];
                    $url = 'https://dxblfrp59esb2.cloudfront.net/'.$image;
                    $dim = getimagesize($url);
                    $imageWidth = $dim[0];
                    $imageHeight = $dim[1];
                    $scale = 1;

                    if ($imageWidth > $imageWidthMax) {
                      $scale = $imageWidthMax / $imageWidth;
                    }

                    $textRun->addText($caption);
                    $textRun->addTextBreak(1);
                    $textRun->addImage($url, [
                      'width' => $imageWidth * $scale,
                      'height' => $imageHeight * $scale
                    ]);
                }

                // Text has no complex values, just a line of text.
                elseif ($result) {
                    $textRun->addText($result);
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

        try {
          //Log::info("result: $result");

          // Grep MathML XML
          preg_match('/\<math.+\<\/math\>/', $result, $match);

          // Delete file
          fclose($tempFile);

          // Return MathML XML
          return $match[0];
        } catch (Exception $err) {
          Log::info($err->getMessage());
          Log::info($result);
        }
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
        $prefix = 'corrections';
        $hash = $collection->hash_id;
        $filename = "{$prefix}-{$hash}.{$type}";
        return storage_path("app/public/question-correction/{$filename}");
    }

    public static function tmp_file($collection, $type)
    {
        $filename = $collection->hash_id . $type;
        return "/tmp/{$filename}";
    }

    function saveDocx($file)
    {
        Log::info($file);
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($this->document, 'Word2007');
        $writer->save($file);
    }

    function savePDF($file)
    {
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($this->document, 'PDF');
        $writer->save($file);
        //Log::info($file);
    }
}
