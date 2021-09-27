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
        ->get();

        return MethodologyResource::collection($methodologies);
    }
}
