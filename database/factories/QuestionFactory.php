<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'number' => $this->faker->numberBetween(1, 10),
            'points' => $this->faker->numberBetween(1, 6),
            'proportion_value' => $this->faker->numberBetween(1, 3),
            'introduction' => $this->faker->paragraph(),
            'text' => $this->faker->sentence(),
        ];
    }
}
