<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Support\RemoteScripts;
use Illuminate\Http\Request;
use App\Http\Resources\TopicResource;

class TopicController extends Controller
{
    public function show(Topic $topic)
    {
        $statuses = [ 'published' ];

        $user = auth()->user();
        $role = $user ? $user->role : '';

        $fields = [
            'exam.stream.course',
            'exam.stream.level',
            'questions.attachments',
            'questions.answers.sections.tips',
            'questions.chapters.parent',
            'questions.dependencies',
            'questions.domains.parent',
            'questions.highlights',
            'questions.questionType',
            'questions.tags',
            'questions.tips',
        ];

        $topic->load($fields);

        return new TopicResource($topic);
    }

    public function html(Topic $topic)
    {
        $appendix_added = [];
        $appendixes = [];

        foreach ($topic->questions as $question) {
            foreach ($question->appendixes as $appendix) {
                $id = $appendix->id;
                if (array_key_exists($id, $appendix_added)) {
                  /* skip */
                } else {
                  $appendixes[] = $appendix;
                  $appendix_added[$id] = true;
                }
            }
        }

        // Dutch local time; 'CET' is no longer a valid timezone id on current PHP/tzdata
        $timestamp = now('Europe/Amsterdam')->format('Y-m-d H:i');

        $topic['appendixes'] = $appendixes;
        $topic['timestamp'] = $timestamp;

        return view('appendixes', $topic);
    }

    public function pdf(Topic $topic)
    {
        $api = url("/api/download-topic-appendixes-html");
        $api = str_replace("http://localhost:8000", "https://staging-api.examenfit.nl", $api);

        $hash = $topic->hash_id;
        $pdf = "uitwerkbijlage-${hash}.pdf";

        $tmp = RemoteScripts::fromConfig()->makePdf('appendixes-pdf', $hash, $api, $pdf);

        return response()->download($tmp);
    }
}
