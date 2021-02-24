<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Question;
use App\Rules\HashIdExists;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Resources\CartResource;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->validate([
            'items' => 'array|min:1',
            // 'items.*' => [new HashIdExists('questions')]
        ]);

        $items = collect($items['items'])
            ->filter()
            ->map(function($item) {
                $decodedValue = Arr::first(\Vinkla\Hashids\Facades\Hashids::decode($item));

                if (!is_int($decodedValue)) {
                    return false;
                }

                return $decodedValue;
            });

        $topics = Question::whereIn('id', $items)
            ->get()->pluck('topic_id')->unique();

        $data = Topic::with(['exam', 'questions'])->whereIn('id', $topics)->get();

        // $data = [];

        // foreach ($topics as $topic_id => $questions) {
        //     $topic = $questions[0]['topic'];
        //     $topic['questions'] = $questions;

        //     for ($i = 0; $i < count($questions); $i++) {
        //         unset($questions[$i]['topic']);
        //     }

        //     $data[] = $topic;
        // }

        return CartResource::collection($data);
    }
}
