<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class TeacherDocumentController extends Controller
{
    public function index(Exam $exam)
    {
        Artisan::call('ef:questioncorrection', ['exam' => $exam->id]);

        $path = storage_path("app/public/question-correction/{$exam->hash_id}.docx");

        if (file_exists($path)) {
            return response()->download($path);
        }

        return "Couldn't create file";
    }
}
