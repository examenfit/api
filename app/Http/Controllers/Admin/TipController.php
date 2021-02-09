<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tip;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Controllers\Controller;

class TipController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'tippable_type' => 'required|in:Question,AnswerSection',
            'tippable_id' => 'required',

            'tips' => 'array|min:1',
            'tips.*.id' => 'string|nullable',
            'tips.*.text' => 'string|required',
        ]);

        $tippable = app('App\\Models\\'.$data['tippable_type'])
            ->with('tips')
            ->find(
                Hashids::decode($data['tippable_id'])[0]
            );

        if (!isset($data['tips'])) {
            $data['tips'] = [];
        }

        foreach ($data['tips'] as $item) {
            // Update Tip if the ID was provided in the submission
            if (isset($item['id']) && strlen($item['id'])) {
                Tip::where('id', Hashids::decode($item['id']))
                    ->update(['text' => $item['text']]);
            }

            // Create Tip in other cases
            else {
                $tippable->tips()->create([
                    'text' => $item['text'],
                ]);
            }
        }

        // Compare ID's, and delete tips
        $current = $tippable->tips->pluck('id');
        $submitted = collect($data['tips'])
            ->pluck('id')
            ->map(
                fn($value) => Arr::first(Hashids::decode($value))
            );

        $current->diff($submitted)->each(function ($id) {
            Tip::where('id', $id)->delete();
        });
    }
}
