<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;

class DuplicateExam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:duplicateExam {exam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Duplicates an exam';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exam = Exam::with([
            'topics.attachments',
            'topics.questions',
            'topics.questions.answers',
            'topics.questions.answers.sections',
            'topics.questions.answers.sections.tips',
            'topics.questions.attachments',
            'topics.questions.domains',
            'topics.questions.tags',
            'topics.questions.tips',
            'topics.questions.chapters',
            'topics.questions.highlights',
        ])->findOrFail($this->argument('exam'));

        // New exam
        $newExam = tap($exam->replicate())->save();

        // Topics
        foreach ($exam->topics as $topic) {
            $newTopic = $topic->replicate();
            $newTopic->exam_id = $newExam->id;
            $newTopic->save();

            // Topics -> Attachments
            foreach ($topic->attachments as $attachment) {
                $newTopic->attachments()->attach($attachment);
            }

            // Topics -> Questions
            foreach ($topic->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->topic_id = $newTopic->id;
                $newQuestion->save();

                // Questions -> Attachments
                foreach ($question->attachments as $attachment) {
                    $newQuestion->attachments()->attach($attachment);
                }

                // Questions -> Domains
                foreach ($question->domains as $domain) {
                    $newQuestion->domains()->attach($domain);
                }

                // Questions -> Tags
                foreach ($question->tags as $tag) {
                    $newQuestion->tags()->attach($tag);
                }

                // Questions -> Chapters
                foreach ($question->chapters as $chapter) {
                    $newQuestion->chapters()->attach($chapter);
                }

                // Questions -> Highlights
                foreach ($question->highlights as $highlight) {
                    $newHighlight = $highlight->replicate();
                    $newHighlight->linkable_id = $newQuestion->id;
                    $newHighlight->save();
                }

                // Questions -> Tips
                foreach ($question->tips as $tip) {
                    $newTip = $tip->replicate();
                    $newTip->tippable_id = $newQuestion->id;
                    $newTip->save();
                }

                // Questions -> Answers
                foreach ($question->answers as $answer) {
                    $newAnswer = $answer->replicate();
                    $newAnswer->question_id = $newQuestion->id;
                    $newAnswer->save();

                    // Answers -> Sections
                    foreach ($answer->sections as $section) {
                        $newSection = $section->replicate();
                        $newSection->answer_id = $newAnswer->id;
                        $newSection->save();

                        // AnswerSections -> Tips
                        foreach ($section->tips as $tip) {
                            $newTip = $tip->replicate();
                            $newTip->tippable_id = $newSection->id;
                            $newTip->save();
                        }
                    }
                }
            }
        }
    }
}
