<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Support\CollectionQuestionsDocument;
use Illuminate\Console\Command;

class GenerateCollectionQuestionsDocument extends Command
{
    protected $signature = 'ef:cqdoc {collection} {format=docx}';
    protected $description = "
Create a document containing all the questions from a collection, intended for authors.

- collection: collection id.
- format: pdf / docx (default)

Example:

> php artisan ef:questionsdocument 1 practice
";

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
