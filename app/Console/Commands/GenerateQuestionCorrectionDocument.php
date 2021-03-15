<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Exam;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\SimpleType\Jc;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQuestionCorrectionDocument extends Command
{
    protected $signature = 'ef:questioncorrection {exam}';
    protected $description = 'Create a Question Correction document for authors';
    public $exam = null;
    public $processor = null;
    public $sections = [];
    public $questionNumber = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->processor = new \PhpOffice\PhpWord\PhpWord();

        // dd($this->processor->getSettings());

        // Add fonts and styles
        $this->processor->setDefaultFontSize(12);
        $this->processor->setDefaultParagraphStyle([
            'lineHeight' => 1.15,
            // 'indent' => 1.5,
        ]);
        $this->processor->addTitleStyle(1, ['bold' => true, 'size' => 14]);

        $this->processor->addTableStyle('questionTableStyle', [
            'borderColor' => 'D3DAE6',
            'borderSize'  => 1,
            'cellMargin'  => 75
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
        $this->getExam($this->argument('exam'));
        $this->setDocumentInfo();
        $this->setHeader();
        $this->setFooter();
        $this->processTopics();

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->processor, 'Word2007');
        $objWriter->save(storage_path("app/public/question-correction/{$this->exam->hash_id}.docx"));
    }

    public function getExam($id)
    {
        $this->exam = Exam::with([
            'course',
            'topics.questions.attachments',
            'topics.questions.answers',
            'topics.questions.domains.parent',
            'topics.questions.tags',
            'topics.questions.questionType',
        ])->findOrFail($id);
    }

    public function setDocumentInfo()
    {
        $properties = $this->processor->getDocInfo();
        $properties->setCreator('ExamenFit');
        $properties->setCompany('ExamenFit');
        $properties->setTitle(
            $this->exam->year . ' ' .
                $this->exam->term . 'e tijdvak' . ' ' .
                $this->exam->level . ' (ExamenFit)'
        );
        $properties->setDescription(
            "Examen aangemaakt in ExamenFit"
        );
    }

    /**
     * Create a new document section
     */
    public function addSection()
    {
        if (count($this->sections)) {
            $this->currentSection()->addPageBreak();
        } else {
            $section = $this->processor->addSection([
                'marginTop' => 1200,
                'marginRight' => 1200,
                'marginBottom' => 1200,
                'marginLeft' => 1200,
            ]);

            $this->sections[] = $section;
        }
    }

    public function setHeader()
    {
        // $header = $this->section->addHeader();
        // $header->addText('ExamenFit examen');
    }

    public function setFooter()
    {
    }

    public function processTopics()
    {
        $topics = $this->exam->topics;

        $this->addSection();

        $this->addCover();

        foreach ($topics as $topic) {
            $this->addTopic($topic);

            foreach ($topic->questions as $question) {
                $this->addQuestion($question);

                $this->currentSection()->addPageBreak();

                $this->addAnswer($question->answers);

                // Temporary hide
                // $this->addMetaData($question);

                $this->currentSection()->addPageBreak();

                // Increase question number
                $this->questionNumber++;
            }
        }
    }

    public function addCover()
    {
        // Title
        $level = strtoupper($this->exam->level);
        $term = $this->exam->term === 1 ? 'Ⅰ' : 'Ⅱ';
        $title = "{$this->exam->course->name} {$level} – {$this->exam->year}-{$term}";
        $this->currentSection()->addText($title, ['bold' => true, 'size' => 24]);

        // Subtitle
        $this->currentSection()->addText(
            "Standaard uitwerkingenbestand auteurs ExamenFit",
            ['bold' => true, 'size' => 16]
        );

        // Next page
        $this->currentSection()->addPageBreak();
    }

    public function addTopic($topic)
    {
        // Title
        $this->currentSection()->addTitle($topic->name);

        // Attachments
        $this->addAttachments($topic->attachments, null, 'small');

        // Introduction
        $textRun = $this->currentSection()->addTextRun();
        $this->formatText($topic->introduction, $textRun);

        // Attachments
        $this->addAttachments($topic->attachments, null, 'large', 1.3);

        // Add break
        $this->currentSection()->addTextBreak(1);
    }

    public function addAttachments($attachments, $parent = null, $type = null)
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
                    'width' => $attachment->image_width + 20, // Textbox padding compensation
                    'height' => $attachment->image_height + $titleHeight,
                    'borderColor' => '#FFFFFF',
                ], $textBoxStyleOptions)
            );

            $textRun = $textBox->addTextRun();

            // Add title to the textbox
            $textRun->addText($attachment->name . "\n", ['bold' => true]);

            // Add image to the textbox
            $textRun->addImage($attachment->url, [
                'width' => $attachment->image_width,
                'height' => $attachment->image_height,
            ]);
        }
    }

    public function addQuestion($question)
    {
        // Title
        $this->currentSection()->addTitle('Vraag ' . $this->questionNumber);

        $this->addAttachments($question->attachments);

        // Create TextRun
        $textRun = $this->currentSection()->addTextRun(['alignment' => 'left']);

        // Introduction
        $this->formatText($question->introduction, $textRun);

        $textRun->addTextBreak(2);

        // Question number
        $textRun->addText('Vraag ' . $this->questionNumber . ': ', ['bold' => true]);

        // Question text
        $this->formatText($question->text, $textRun);

        // Add break
        $this->currentSection()->addTextBreak(1);
    }

    public function addAnswer($answers)
    {
        $this->currentSection()->addTitle("CV – Vraag {$this->questionNumber}:");

        $textRun = $this->currentSection()->addTextRun();
        $textRun->addTextBreak(1);
        $textRun->addText('Opmerking: ');
        $textRun->addTextBreak(1);
        $textRun->addText('...');
        $textRun->addTextBreak(1);

        foreach ($answers as $index => $answer) {

            if ($index > 0) {
                $textRun = $this->currentSection()->addTextRun();
                $textRun->addTextBreak(1);
                $textRun->addText('Of', ['italic' => true]);
                $textRun->addTextBreak(1);
            }

            foreach ($answer->sections as $index => $section) {
                $textRun = $this->currentSection()->addTextRun();

                $textRun->addText('Item ' . ($index + 1) . ' (' . $section->points . 'p): ', ['bold' => true]);
                $this->formatText($section->correction, $textRun);
            }

            $textRun->addTextBreak(2);
            $textRun->addText('Opmerking: ');
            $textRun->addTextBreak(1);
            $textRun->addText('...');
            $textRun->addTextBreak(1);

            if ($answer->remark) {
                $this->currentSection()->addTextBreak(1);
                $textRun = $this->currentSection()->addTextRun();
                $textRun->addText('Opmerking:');
                $textRun->addTextBreak(1);
                $this->formatText($answer->remark, $textRun, ['italic' => true]);
                // $textRun->addText($answer->remark, ['italic' => true]);
            }
        }

        $answer = $answers[0];

        $this->currentSection()->addTextBreak(1);
        $this->currentSection()
            ->addTitle("Tussenantwoorden – Vraag {$this->questionNumber}:");
        $this->currentSection()->addTextBreak(1);

        foreach ($answer->sections as $index => $section) {
            $textRun = $this->currentSection()->addTextRun();
            $stepNumber = $index + 1;

            $textRun->addText(
                "TA {$stepNumber} ({$section->points}p):",
                ['bold' => true, 'color' => '0070C0']
            );
            $textRun->addTextBreak(1);
            $this->formatText($section->correction, $textRun);
            $textRun->addTextBreak(2);
        }

        $this->currentSection()->addPageBreak();

        $this->currentSection()
            ->addTitle("Tips – Vraag {$this->questionNumber}:");
        $this->currentSection()->addTextBreak(1);

        $textRun = $this->currentSection()->addTextRun();
        $textRun->addText(
            "Algemene tip:",
            ['bold' => true, 'color' => '0070C0']
        );

        foreach ($answer->sections as $index => $section) {
            $textRun = $this->currentSection()->addTextRun();
            $stepNumber = $index + 1;

            $textRun->addText(
                "Tip TA {$stepNumber}:",
                ['bold' => true, 'color' => '0070C0']
            );
            $textRun->addTextBreak(1);
        }

        $this->currentSection()->addTextBreak(2);
        $this->currentSection()->addTitle("Modeluitwerking – Vraag {$this->questionNumber}:");
        $this->currentSection()->addTextBreak(1);

        foreach ($answer->sections as $index => $section) {
            $textRun = $this->currentSection()->addTextRun();
            $stepNumber = $index + 1;

            $textRun->addText(
                "MU {$stepNumber}:",
                ['bold' => true, 'color' => '0070C0']
            );
            $textRun->addTextBreak(1);
            if ($index === 0) {
                $textRun->addText("Gegeven:");
                $textRun->addTextBreak(1);
                $textRun->addText("Gevraagd:");
                $textRun->addTextBreak(1);
                $textRun->addText("Aanpak:");
                $textRun->addTextBreak(1);
            }
        }
    }

    public function addMetaData($question)
    {
        $this->currentSection()->addTextBreak(2);
        $this->currentSection()->addTitle("Metadata – Vraag {$question->number} (Controleren):");
        $this->currentSection()->addTextBreak(1);

        $textRun = $this->currentSection()->addTextRun();

        // Question type
        if ($question->questionType) {
            $textRun->addText('Vraagtype: ', ['bold' => true]);
            $textRun->addText($question->questionType->name);
        }

        // Domains
        $textRun->addTextBreak(1);
        $textRun->addText('Domeinen: ', ['bold' => true]);

        $domains = [];
        foreach ($question->domains as $domain) {
            $domains[] = $domain->name;
        }

        $textRun->addText(implode(', ', $domains));

        // Tags
        $textRun->addTextBreak(1);
        $textRun->addText('Trefwoorden: ', ['bold' => true]);
        $textRun->addText(implode(', ', $question->tags->pluck('name')->toArray()));

        $textRun->addTextBreak(3);

        $this->currentSection()->addTitle("Metadata – Vraag {$question->number} (Creëren):");
        $this->currentSection()->addTextBreak(1);

        $textRun = $this->currentSection()->addTextRun();
        $textRun->addText("Highlight - Vraag {$question->number}: ", ['bold' => true, 'color' => '0070C0']);
    }

    public function formatText($text, &$textRun = null, $textStyle = null)
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
                            $textRun->addText($chunk['result'], $textStyle, ['alignment' => 'left']);

                            // Add space if the chunk only has a formula
                            // Otherwise the formula is shown centered.
                            // if (count($chunks) === 1) {
                            //     $textRun->addText(' ');
                            // }
                            break;
                        case 'boldStyle':
                            $textRun->addText(
                                $chunk['result'],
                                array_merge(['bold' => true], $textStyle ?? [])
                            );
                            break;
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
        return $this->sections[(count($this->sections) - 1)];
    }
}
