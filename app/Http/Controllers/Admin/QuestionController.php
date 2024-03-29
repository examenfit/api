<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Question;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\QuestionResource;

class QuestionController extends Controller
{
    public function show(Question $question)
    {
        $question->load([
            'topic',
            'attachments',
            'appendixes',
            'tags',
            'domains',
            'answers.sections',
            'chapters',
            'highlights',
            'dependencies',
        ]);

        return new QuestionResource($question);
    }

    public function store(Request $request, Topic $topic)
    {
        $data = $request->validate([
            'number' => 'required|integer',
            'points' => 'required|integer',
            'proportion_value' => 'nullable|integer|min:0',
            'introduction' => 'nullable|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.correction' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'answer_remark' => 'nullable|string',
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
        ]);

        $question = $topic->questions()->create($data);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['answerSections'])) {
            $answer = $question->answers()->create([
                'type' => 'correction',
                'position' => 1,
                'name' => 'Oplossingsstrategie',
                'remark' => $data['answer_remark'] ?? null,
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tags'])) {
            $question->addTags($data['tags']);
        }

        if (isset($data['domains'])) {
            $question->addDomains($data['domains']);
        }

        return app('App\Http\Controllers\Admin\ExamController')
            ->show($topic->exam);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'topic_id' => ['nullable', new HashIdExists('topics')],
            'number' => 'required|integer',
            'points' => 'required|integer',
            'time_in_minutes' => 'nullable|integer',
            'complexity' => 'nullable|in:low,average,high',
            'proportion_value' => 'nullable|numeric',
            'introduction' => 'nullable|string',
            'text' => 'required|string',
            'answerSections' => 'array',
            'answerSections.*.correction' => 'required|string',
            'answerSections.*.points' => 'required|integer',
            'answer_remark' => 'nullable|string',
            'type_id' => ['nullable', new HashIdExists('question_types')],
            'tags.*.id' => ['required', new HashIdExists('tags')],
            'tagNames.*' => ['required'],
            'domains.*.id' => ['required', new HashIdExists('domains')],
            'chapters.*.id' => ['required', new HashIdExists('chapters')],
            'attachments' => 'array',
            'attachments.*.id' => ['required', new HashIdExists('attachments')],
            'appendixes' => 'array',
            'appendixes.*.id' => ['required', new HashIdExists('attachments')],
            'highlights' => 'array',
            'highlights.*.text' => ['required', 'string', 'max:255'],
            'dependencies' => 'array',
            'dependencies.*.id' => ['required', new HashIdExists('questions')],
            'dependencies.*.introduction' => 'nullable|boolean',
            'dependencies.*.attachments' => 'nullable|boolean',
            'dependencies.*.appendixes' => 'nullable|boolean',
        ]);

        if (isset($data['attachments'])) {
            $question->addAttachments($data['attachments']);
        }

        if (isset($data['appendixes'])) {
            $question->addAppendixes($data['appendixes']);
        }

        if (isset($data['answerSections'])) {
            $question->answers()->delete();

            $answer = $question->answers()->create([
                'type' => 'correction',
                'remark' => $data['answer_remark'] ?? null,
            ]);

            $answer->sections()->createMany($data['answerSections']);
        }

        if (isset($data['tagNames'])) {
          $tags = [];
          $stream = $question->topic->exam->stream;
          foreach($data['tagNames'] as $tagName) {
            $tags[] =
              $stream->tags()->firstWhere('name', $tagName) ?:
              $stream->tags()->create([
                'name' => $tagName
              ]);
          }
          $question->syncTagIds($tags);
        }

        //if (isset($data['tags'])) {
            //$question->addTags($data['tags']);
        //}

        if (isset($data['domains'])) {
            $question->addDomains($data['domains']);
        }

        if (isset($data['chapters'])) {
            $question->addChapters($data['chapters']);
        }


        if (isset($data['highlights'])) {
            $question->highlights()->delete();

            $question->highlights()->createMany(
                collect($data['highlights'])
            );
        }

        if (isset($data['dependencies'])) {
            $question->dependencies()->sync(
                collect($data['dependencies'])
                    ->filter(
                        fn ($item) => $item['introduction']
                            || $item['attachments']
                            || $item['appendixes']
                    )
                    ->mapWithKeys(
                        fn ($item) => [Hashids::decode($item['id'])[0] => [
                            'introduction' => $item['introduction'],
                            'attachments' => $item['attachments'],
                            'appendixes' => $item['appendixes'],
                        ]]
                    )
            );
        }

        $question->update($data);

        if ($request->has('withExamWrapper')) {
            return app('App\Http\Controllers\Admin\ExamController')
                ->show($question->fresh()->topic->exam);
        }

        return $this->show($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return response(null, 200);
    }
}
