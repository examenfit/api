<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use Illuminate\Support\Facades\Artisan;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('course')->get();

        return ExamResource::collection($exams);
    }

    public function show(Exam $exam)
    {
        $exam->load([
            'topics.questions.answers.sections',
            'topics.questions.tags',
            'files'
        ]);

        return new ExamResource($exam);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'level' => 'required|string|in:havo,vwo',
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2',
            'standardization_value' => 'nullable|numeric',
            'files' => 'required|min:1',
            'files.*.name' => 'required|string',
            'files.*.file' => 'required|file|mimes:pdf',
        ]);

        $exam = Exam::create([
            'course_id' => $data['course_id'],
            'status' => 'prepared',
            'level' => $data['level'],
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
            'course_id' => 'required',
            'status' => 'nullable|in:concept,published',
            'level' => 'required|in:havo,vwo',
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2',
            'standardization_value' => 'nullable|numeric',
        ]);

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
