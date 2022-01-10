<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Support\CollectionCorrectionsDocument;
use Illuminate\Console\Command;

class GenerateCollectionCorrectionsDocument extends Command
{
    protected $signature = 'ef:ccdoc {collection} {format=docx}';
    protected $description = 'Document met uitwerkingen van een opgaveset aanmaken voor docenten';

    public $collection = null;
    public $document = null;
    public $sections = [];

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
        $collection = $this->findCollection();
        $file = CollectionCorrectionsDocument::storage_file($collection, 'docx');
        $document = new CollectionCorrectionsDocument();
        $document->createDocument($collection);
        $document->saveDocument($file, "docx");
        $this->info("Aangemaakt: $file\n");
    }

    function findCollection()
    {
        $collection_id = $this->argument('collection');
        $collection = Collection::findOrFail($collection_id);
        $collection->load([
            'author',
            'questions' => fn($q) => $q->orderBy('topic_id', 'ASC')->orderBy('number', 'ASC'),
            'questions.topic',
        ]);
        return $collection;
    }

}
