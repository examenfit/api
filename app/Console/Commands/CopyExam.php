<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;

class CopyExam extends Command
{
  protected $signature = 'ef:copy:exam {exam}';
  protected $description = 'Creates a copy of an existing exam';

  public function handle()
  {
    $id = $this->argument('exam');
    $exam = Exam::find($id);

    $replExam = $exam->replicate();
    $replExam->status = 'frozen';
    $replExam->notes = "copy of #{$exam->hash_id}/{$exam->id}";
    $replExam->save();
    $this->info("Exam#{$replExam->hash_id} {$exam->stream->slug} {$exam->year}-{$exam->term}");

    foreach ($exam->topics as $topic) {
      $replTopic = $topic->replicate();
      $replTopic->exam_id = $replExam->id;
      $replTopic->save();
      $this->info("Topic#{$replTopic->hash_id} {$topic->name}");

      foreach ($topic->questions as $question) {
        $replQuestion = $question->replicate();
        $replQuestion->topic_id = $replTopic->id;
        $replQuestion->save();
        $this->info("Question#{$replQuestion->hash_id} {$question->number}");

        $this->info("Question.Tags");
        $replQuestion->tags()->attach($question->tags);
        $this->info("Question.Appendixes");
        $replQuestion->appendixes()->attach($question->appendixes);
        $this->info("Question.Domains");
        $replQuestion->domains()->attach($question->domains);
        $this->info("Question.Chapters");
        $replQuestion->chapters()->attach($question->chapters);

        foreach ($question->tips as $tip) {
          $replTip = $tip->replicate();
          $replTip->tippable_id = $replQuestion->id;
          $replTip->save();
          $this->info("Tip#{$replTip->hash_id}");
        }

        foreach ($question->highlights as $highlight) {
          $replHighlight = $highlight->replicate();
          $replHighlight->question_id = $replQuestion->id;
          $replHighlight->save();
          $this->info("Highlight#{$replHighlight->hash_id}");
        }

        foreach ($question->answers as $answer) {
          $replAnswer = $answer->replicate();
          $replAnswer->question_id = $replQuestion->id;
          $replAnswer->save();
          $this->info("Answer#{$replAnswer->hash_id}");

          foreach ($answer->sections as $section) {
            $replSection = $section->replicate();
            $replSection->answer_id = $replAnswer->id;
            $replSection->save();
            $this->info("Section#{$replSection->hash_id}");

            foreach ($section->tips as $sectionTip) {
              $replSectionTip = $sectionTip->replicate();
              $replSectionTip->tippable_id = $replSection->id;
              $replSectionTip->save();
              $this->info("Tip#{$replSectionTip->id}");

              $replTopic->has_answers = TRUE;
              $replTopic->save();

              $replExam->show_answers = TRUE;
              $replExam->save();
            }
          }
        }
      }
    }
  }
}
