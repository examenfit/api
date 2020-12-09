<?php

namespace Database\Factories;

use App\Models\AnswerSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerSectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnswerSection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'text' => $this->faker->paragraph(),
            'points' => $this->faker->numberBetween(1, 5),
        ];
    }
}
