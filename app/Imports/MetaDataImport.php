<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class MetaDataImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    public $console;
    public $topic;

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            return $row;
        }
    }
}
