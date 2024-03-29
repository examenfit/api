<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Support\CollectionQuestionsDocument;
use Illuminate\Console\Command;

class GenerateCollectionQuestionsDocument extends Command
{
    protected $signature = 'ef:cqdoc {collection} {format=docx}';
    protected $description = 'Document met vragen van een opgaveset aanmaken voor docenten';

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
        $file = CollectionQuestionsDocument::storage_file($collection, 'docx');
        $document = new CollectionQuestionsDocument();
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
        if (!$collection) {
            $this->error('Opgavenset niet gevonden');
            die("\n");
        }
        return $collection;
    }

}
