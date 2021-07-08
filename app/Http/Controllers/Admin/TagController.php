<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Models\Stream;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Controllers\Controller;
use App\Rules\HashIdExists;

class TagController extends Controller
{
    public function index(Stream $stream)
    {
        $stream->load(['tags' => function ($query) {
            $query
                ->withCount('question')
                ->orderBy('name', 'ASC');
        }]);

        return TagResource::collection($stream->tags);
    }

    public function store(Request $request, Stream $stream)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $stream->tags()->create($data);

        return $this->index($stream);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag->update($data);

        return $this->index($tag->stream);
    }

    public function destroy(Tag $tag)
    {
        $stream = $tag->stream;
        $tag->delete();

        return $this->index($stream);
    }
}
