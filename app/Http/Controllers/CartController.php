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
        // dump(json_decode($request->items));
        $items = $request->validate([
            'items' => 'array|min:1',
            'items.*' => [new HashIdExists('topics')]
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

        $topics = Topic::with('exam', 'questions')->whereIn('id', $items)->get();

        return CartResource::collection($topics);
    }
}
