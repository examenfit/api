<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Spatie\PdfToText\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessCitoPDF extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:processPDF {exam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pdftotextPath = config('app.env') === 'production'
            ? '/usr/bin/pdftotext'
            : '/usr/local/bin/pdftotext';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exam = Exam::findOrFail($this->argument('exam'));
        $file = $exam->files()->where('name', 'Opgaven')->first();

        if (!$file) {
            $this->error("Could not find exam 'Opgaven'.");
        }

        // Store as a temp file, because the files are stored
        // in a S3-bucket on production.
        $tempFile = tmpfile();
        fwrite($tempFile, file_get_contents(Storage::url($file->path)));
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        $sectionTitles = $this->getSectionsSubjectTitles($tempFilePath);

        $text = (new Pdf($this->pdftotextPath))
            ->setPdf($tempFilePath)
            ->setOptions(['layout'])
            ->text();

        $sections = [];

        foreach ($sectionTitles as $key => $sectionTitle) {
            $searchTitle = preg_quote($sectionTitle);
            if ($key === array_key_last($sectionTitles)) {
                preg_match('/'.$searchTitle.'(.+)lees verdereinde/s', $text, $sectionContent);
            } else {
                $nextSectionTitle = preg_quote($sectionTitles[$key + 1]);
                preg_match('/'.$searchTitle.'(.+)'.$nextSectionTitle.'/s', $text, $sectionContent);
            }

            $sections[] = [
                'title' => $sectionTitle,
                'contents' => $sectionContent[1],
            ];
        }

        foreach ($sections as $index => $section) {
            $sections[$index]['questions'] = $this->getQuestionsFromContent($section['contents']);
            $sections[$index]['contents'] = null;
        }

        $exam->update([
            'assignment_contents' => $sections,
        ]);

        // Delete file
        fclose($tempFile);
    }

    public function getQuestionsFromContent($content)
    {
        $questions = [];
        preg_match_all("/([1-9]p).+?([0-9]{1,2})\s+(.+?)\n{2,}/s", $content, $questionResults, PREG_SET_ORDER);

        foreach ($questionResults as $question) {
            $text = strip_tags($question[3]);
            $text = str_replace("\n", " ", $text);
            $text = preg_replace("/\s+/", " ", $text);
            $text = trim($text);

            $questions[] = [
                'points' => str_replace('p', '', $question[1]),
                'number' => $question[2],
                'text' => $text,
            ];
        }

        return $questions;
    }

    public function getSectionsSubjectTitles($pdfPath)
    {
        $sections = [];

        $layout = (new Pdf($this->pdftotextPath))
            ->setPdf($pdfPath)
            ->setOptions(['bbox-layout'])
            ->text();

        // Last character of the document
        preg_match('/^.+/s', $layout, $mainContent);

        // Look for section titles
        preg_match_all('/<line xMin="82\.200000".+?>(.+?)<\/line>/s', $mainContent[0], $sectionsSearch);

        if (count($sectionsSearch[1])) {
            foreach ($sectionsSearch[1] as $result) {
                $result = strip_tags($result);
                $result = str_replace("\n", " ", $result);
                $result = preg_replace("/\s+/", " ", $result);
                $result = preg_replace("/[1-9]\)/", "", $result);
                $result = trim($result);

                $sections[] = $result;
            }
        }

        return $sections;
    }
}
