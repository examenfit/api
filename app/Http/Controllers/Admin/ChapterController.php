<?php

namespace App\Http\Controllers\Admin;

use App\Models\Chapter;
use App\Models\Stream;
use App\Models\Methodology;
use Illuminate\Http\Request;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\MethodologyResource;
use App\Http\Resources\StreamResource;
use App\Http\Controllers\Controller;
use App\Rules\HashIdExists;
use Vinkla\Hashids\Facades\Hashids;

class ChapterController extends Controller
{
    public function index(Stream $stream)
    {
        $methodologies = Methodology::whereHas(
            'chapters', fn($q) => $q
            ->where('stream_id', $stream->id)
        )
        ->with('chapters',
            fn($q) => $q
                ->where('stream_id', $stream->id)
                ->withCount('topics')
                ->with([
                    'children' => fn($q) => $q
                        ->withCount('topics')
                        ->orderBy('id')
                ])
                ->orderBy('name')
        )
        ->orderBy('name')
        ->get();

        return MethodologyResource::collection($methodologies);
    }

    public function addBook(Stream $stream, Request $request) {
      $chapter = Chapter::create([
        'stream_id' => $stream->id,
        'methodology_id' => Hashids::decode($request->methodology_id)[0],
        'name' => $request->name
      ]);
      $chapter->load('children');
      return new ChapterResource($chapter);
    }

    public function updateBook(Chapter $book, Request $request) {
      $book->name = $request->name;
      $book->save();
      return new ChapterResource($book);
    }

    public function deleteBook(Chapter $book) {
      $book->delete();
      return [ 'status' => 'ok' ];
    }

    public function addChapter(Chapter $book, Request $request) {
      $chapter = Chapter::create([
        'stream_id' => $book->stream_id,
        'methodology_id' => $book->methodology_id,
        'chapter_id' => $book->id,
        'name' => $request->name,
        'title' => $request->title
      ]);
      return new ChapterResource($chapter);
    }

    public function updateChapter(Chapter $chapter, Request $request) {
      $chapter->name = $request->name;
      $chapter->title = $request->title;
      $chapter->save();
      return new ChapterResource($chapter);
    }

    public function deleteChapter(Chapter $chapter) {
      $chapter->delete();
      return [ 'status' => 'ok' ];
    }
}
