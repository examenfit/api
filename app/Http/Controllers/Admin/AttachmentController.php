<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Attachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;

class AttachmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('exam')) {
            $exam = Exam::with('topics')->findOrFailByHashId($request->get('exam'));

            $attachments = Attachment::query()
                ->whereHas('topics', function ($query) use ($exam) {
                    return $query->where('exam_id', $exam->id);
                })
                ->orWhereHas('questions', function ($query) use ($exam) {
                    return $query->whereIn('topic_id', $exam->topics->pluck('id'));
                })
                ->get();

            return AttachmentResource::collection($attachments);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'file' => 'required|file',
        ]);

        $attachment = Attachment::create([
            'name' => $data['name'],
            'path' => $data['file']->store('attachments'),
        ]);

        return new AttachmentResource($attachment);
    }
}
