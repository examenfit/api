<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class QuestionTypeImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    public $console;
    public $topic;

    /**
     * @param Collection $collection
     */
    // Excel 4 requires `: void` and ignores the return value (it was ignored in Excel 3 too);
    // the commands only use Excel::toCollection(), which returns the sheets' rows.
    public function collection(Collection $collection): void
    {
    }
}
