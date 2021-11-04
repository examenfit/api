<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Resources\TopicResource;

class TopicController extends Controller
{
    public function show(Topic $topic)
    {
        $topic->load([
            'exam.stream.course',
            'exam.stream.level',
            'questions.answers.sections',
            'questions.attachments',
            'questions.chapters',
            'questions.chapters.parent',
            'questions.dependencies',
            'questions.domains.parent',
            'questions.highlights',
            'questions.questionType',
            'questions.tags',
        ]);

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

        date_default_timezone_set('CET');
        $timestamp = date('Y-m-d H:i');

        $topic['appendixes'] = $appendixes;
        $topic['timestamp'] = $timestamp;

        return view('appendixes', $topic);
    }

    public function pdf(Topic $topic)
    {
        $api = url("/api/download-appendixes-html");
        $api = str_replace("http://localhost:8000", "https://staging-api.examenfit.nl", $api);

        $server = config('app.examenfit_scripts_url');

        $hash = $topic->hash_id;
        $pdf = "uitwerkbijlage-${hash}.pdf";
        $tmp = "/tmp/${pdf}";

        // currently ssh authentication goes with public key authentication
        // in the future this may need to become ssh -i id_rsa or something alike
        $generate = "ssh examenfit@$server make/appendixes-pdf $hash $api";
        $retrieve = "scp examenfit@$server:pdf/$pdf $tmp";

        shell_exec($generate);
        shell_exec($retrieve);

        return response()->download($tmp);
    }
}
