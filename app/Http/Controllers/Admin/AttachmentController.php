<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;

class AttachmentController extends Controller
{
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
