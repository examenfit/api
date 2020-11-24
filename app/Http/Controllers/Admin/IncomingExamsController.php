<?php

namespace App\Http\Controllers\Admin;

use App\Models\IncomingExam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\IncomingExamResource;

class IncomingExamsController extends Controller
{
    public function index()
    {
        return IncomingExamResource::collection(
            IncomingExam::all()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'level' => 'required|string|in:havo,vwo',
            'year' => 'required|integer|min:2010',
            'term' => 'required|integer|in:1,2',
            'assignment_file' => 'required|file|mimes:pdf',
            'appendix_file' => 'nullable|file|mimes:pdf',
            'correction_requirement_file' => 'required|file|mimes:pdf',
            'standardization_url' => 'required|url',
        ]);

        $incomingExam = IncomingExam::create([
            'level' => $data['level'],
            'year' => $data['year'],
            'term' => $data['term'],
            'assignment_file_path' => $data['assignment_file']->store('cito_files', 'public'),
            'appendix_file_path' => isset($data['appendix_file'])
                ? $data['appendix_file']->store('cito_files', 'public')
                : null,
            'correction_requirement_file_path' => $data['correction_requirement_file']->store('cito_files', 'public'),
            'standardization_url' => $data['standardization_url'],
        ]);

        // Process to queue

        return response(200);
    }

    public function show(IncomingExam $incomingExam)
    {
        $incomingExam->load('exam.topics.questions');

        return new IncomingExamResource(
            $incomingExam
        );
    }
}
