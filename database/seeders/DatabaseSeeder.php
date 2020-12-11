<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Topic;
use App\Models\Answer;
use App\Models\Question;
use App\Models\AnswerSection;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public $questionTypes = [
        ['course_id' => 1, 'name' => 'Los op (algebraïsch)'],
        ['course_id' => 1, 'name' => 'Los op (exact)'],
        ['course_id' => 1, 'name' => 'Toon aan'],
        ['course_id' => 1, 'name' => 'Leid af'],
        ['course_id' => 1, 'name' => 'Bepaal'],
        ['course_id' => 1, 'name' => 'Beredeneer / leguit'],
        ['course_id' => 1, 'name' => 'Bereken'],
        ['course_id' => 1, 'name' => 'Bewijs'],
        ['course_id' => 1, 'name' => 'Herleid'],
        ['course_id' => 1, 'name' => 'Noem'],
        ['course_id' => 1, 'name' => 'Onderzoek'],
        ['course_id' => 1, 'name' => 'Los op'],
        ['course_id' => 1, 'name' => 'Schets'],
        ['course_id' => 1, 'name' => 'Teken'],
    ];

    public $domains = [
        [
            'course_id' => '1',
            'name' => 'Vaardigheden',
        ],
        [
            'parent_id' => '1',
            'name' => 'Algemene',
        ],
        [
            'parent_id' => '1',
            'name' => 'Specifieke',
        ],
        [
            'parent_id' => '1',
            'name' => 'Wiskundige',
        ],
        [
            'course_id' => '1',
            'name' => 'Algebra',
        ],
        [
            'parent_id' => '5',
            'name' => 'Percentages',
        ],
        [
            'parent_id' => '5',
            'name' => 'Breuken',
        ],
        [
            'parent_id' => '5',
            'name' => 'Maatsystemen',
        ],
        [
            'course_id' => '1',
            'name' => 'Tellen',
        ],
        [
            'parent_id' => '9',
            'name' => 'Combinaties',
        ],
        [
            'parent_id' => '9',
            'name' => 'Diagrammen',
        ],
        [
            'parent_id' => '9',
            'name' => 'Schematiseren',
        ],
        [
            'course_id' => '1',
            'name' => 'Functies',
        ],
        [
            'parent_id' => '13',
            'name' => 'Lineaire',
        ],
        [
            'parent_id' => '13',
            'name' => 'Kwadratische',
        ],
        [
            'parent_id' => '13',
            'name' => 'Machts',
        ],
        [
            'parent_id' => '13',
            'name' => 'Goniometrische',
        ],
        [
            'parent_id' => '13',
            'name' => 'Logaritmische',
        ],
        [
            'course_id' => '1',
            'name' => 'Verbanden',
        ],
        [
            'parent_id' => '19',
            'name' => 'Formules',
        ],
        [
            'parent_id' => '19',
            'name' => 'Grafieken',
        ],
        [
            'parent_id' => '19',
            'name' => 'Vergelijkingen',
        ],
        [
            'parent_id' => '19',
            'name' => 'Ongelijkheden',
        ],
        [
            'parent_id' => '19',
            'name' => 'Transformaties',
        ],
        [
            'course_id' => '1',
            'name' => 'Verandering',
        ],
        [
            'parent_id' => '25',
            'name' => 'Rijen',
        ],
        [
            'parent_id' => '25',
            'name' => 'Helling',
        ],
        [
            'parent_id' => '25',
            'name' => 'Afgeleide',
        ],
    ];



    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->faker = \Faker\Factory::create();

        \App\Models\User::factory(1)->create();

        $course = \App\Models\Course::create([
            'name' => 'Wiskunde'
        ]);

        $domains = \App\Models\Domain::factory()->createMany($this->domains);
        $questionTypes = \App\Models\QuestionType::factory()->createMany($this->questionTypes);


        Exam::factory()->count(10)->create()->each(function($exam) use ($domains, $questionTypes) {
            Topic::factory()->count(5)->create([
                'exam_id' => $exam->id
                ])->each(function ($topic) use ($domains, $questionTypes) {
                Question::factory()->count(20)->create([
                    'domain_id' => $this->faker->randomElement($domains->pluck('id')->toArray()),
                    'type_id' => $this->faker->randomElement($questionTypes->pluck('id')->toArray()),
                    'topic_id' => $topic->id,
                ])->each(function ($question) {
                    $answer = Answer::factory()->create([
                        'question_id' => $question->id,
                    ]);

                    AnswerSection::factory()->create([
                        'answer_id' => $answer->id,
                    ]);
                });
            });
        });
    }
}
