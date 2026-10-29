<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// hashids 14 (Laravel 13 upgrade) only decodes strings. Question's topic_id/type_id/domain_id mutators decode
// their value as a hashid, but relations (topic->questions()->create(), associate()) hand them the numeric id:
// that used to be coerced silently and became a 500 ("Hashids::decode(): Argument #1 ($hash) must be of type
// string, int given") when an admin added a question.
class QuestionHashIdTest extends TestCase
{
    use RefreshDatabase;

    private function topic(): Topic
    {
        Schema::disableForeignKeyConstraints();
        // not Exam::factory(): it still sets the long gone course_id and level columns
        $exam = Exam::create(['status' => 'published', 'year' => 2020, 'term' => 1]);
        $topic = Topic::factory()->create(['exam_id' => $exam->id]);
        Schema::enableForeignKeyConstraints();

        return $topic;
    }

    public function test_a_question_can_be_created_through_the_topic_relation()
    {
        $topic = $this->topic();

        Schema::disableForeignKeyConstraints();
        $question = $topic->questions()->create(['number' => 1, 'points' => 1, 'text' => 'Vraag']);
        Schema::enableForeignKeyConstraints();

        $this->assertSame($topic->id, $question->fresh()->topic_id);
    }

    public function test_a_question_can_be_moved_to_another_topic_with_a_hashid_or_an_id()
    {
        $topic = $this->topic();
        $other = $this->topic();

        Schema::disableForeignKeyConstraints();
        $question = $topic->questions()->create(['number' => 1, 'points' => 1, 'text' => 'Vraag']);

        $question->update(['topic_id' => $other->hash_id]);
        $this->assertSame($other->id, $question->fresh()->topic_id);

        $question->update(['topic_id' => $topic->id]);
        $this->assertSame($topic->id, $question->fresh()->topic_id);
        Schema::enableForeignKeyConstraints();
    }

    public function test_the_admin_endpoint_creates_a_question_with_an_answer_section()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $topic = $this->topic();

        Schema::disableForeignKeyConstraints();
        $response = $this->postJson("/api/admin/topics/{$topic->hash_id}/questions?withExamWrapper", [
            'number' => '1',
            'points' => '1',
            'introduction' => 'Introductie',
            'text' => 'Weet je zeker dat je wil doorgaan?',
            'proportion_value' => '',
            'answerSections' => [['correction' => 'Ja.', 'points' => '1']],
            'answer_remark' => '',
            'newSubject' => 'false',
            'id' => '',
            'attachments' => [],
        ]);
        Schema::enableForeignKeyConstraints();

        $response->assertOk();

        $question = Question::where('topic_id', $topic->id)->first();
        $this->assertNotNull($question);
        $this->assertSame(1, $question->answers()->count());
        $this->assertSame(1, $question->answers()->first()->sections()->count());
    }
}
