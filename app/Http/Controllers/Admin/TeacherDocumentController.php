<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class TeacherDocumentController extends Controller
{
    public function index(Exam $exam)
    {
        try {
            Artisa::call('ef:questioncorrection', ['exam' => $exam->id]);
            Log::info(Artisan::output());

            $path = storage_path("app/public/question-correction/{$exam->hash_id}.docx");

            if (file_exists($path)) {
                return response()->download($path);
            } else {
                return response("document unavailable ($path)", 500);
            }
        } catch (\Exception $error) {
            return response($error->getMessage(), 500);
        }
    }
}
