<?php

namespace App\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class HashIdExists implements Rule
{
    public $table;
    public $column;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($table, $column = 'id')
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $decodedValue = Arr::first(\Vinkla\Hashids\Facades\Hashids::decode($value));

        if (!is_int($decodedValue)) {
            return false;
        }

        return DB::table($this->table)
            ->where($this->column, $decodedValue)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The provided ID does not exists.';
    }
}
