<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;

class SetImageDimensions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:setImageDimensions {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set all correct image dimensions';

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
        if ($this->option('all')) {
          $images = Attachment::all();
        } else {
          $images = Attachment::whereNull('image_width')
            ->whereNull('image_height')
            ->get();
        }

        foreach ($images as $image) {
            $imageSize = @getimagesize($image->url);
            if ($imageSize) {
                $image->image_width = $imageSize[0] / 3;
                $image->image_height = $imageSize[1] / 3;
                $image->save();
            }
        }
    }
}
