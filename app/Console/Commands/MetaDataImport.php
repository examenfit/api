<?php

namespace App\Console\Commands;

use App\Imports\MetaDataImport as ImportsMetaDataImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class MetaDataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:import:metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears and imports meta data based on an Excel sheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Excel::import(new ImportsMetaDataImport($this), 'vwoa.xlsx');
    }
}
