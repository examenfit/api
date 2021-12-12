<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Stream;
use App\Rules\HashIdExists;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use Illuminate\Support\Facades\Artisan;
use Vinkla\Hashids\Facades\Hashids;

class ExamController extends Controller
{
    public function index(Stream $stream)
    {
        $exams = $stream->exams;
        return ExamResource::collection($exams);
    }

    public function show(Exam $exam)
    {
        $exam->load([
            'topics.questions.answers.sections',
            'topics.questions.tags',
            'topics.questions.domains',
            'files'
        ]);
        return new ExamResource($exam);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stream_id' => ['required', new HashIdExists('streams')],
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2,3',
            'standardization_value' => 'nullable|numeric',
            'files' => 'required|min:1',
            'files.*.name' => 'required|string',
            'files.*.file' => 'required|file|mimes:pdf',
        ]);

        $exam = Exam::create([
            'stream_id' => Hashids::decode($data['stream_id'])[0],
            'status' => 'prepared',
            'year' => $data['year'],
            'term' => $data['term'],
        ]);

        foreach ($data['files'] as $file) {
            $exam->files()->create([
                'name' => $file['name'],
                'path' => $file['file']->store('exams'),
            ]);
        }

        // Process to queue
        Artisan::queue('ef:processPDF', [
            'exam' => $exam->id,
        ]);

        return response(200);
    }

    public function update(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'stream_id' => ['required', new HashIdExists('streams')],
            'status' => 'nullable|in:concept,published,frozen',
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2,3',
            'show_answers' => 'nullable|boolean',
            'standardization_value' => 'nullable|numeric',
            'is_pilot' => 'nullable|boolean',
            'introduction' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $data['stream_id'] = Hashids::decode($data['stream_id'])[0];
        $exam->update($data);
        $exam->load('topics.questions.answers.sections', 'files');

        return new ExamResource($exam);
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();

        return response(null, 200);
    }
}
