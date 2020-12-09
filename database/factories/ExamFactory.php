<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'course_id' => 1,
            'status' => 'published',
            'level' => $this->faker->randomElement(['havo', 'vwo']),
            'year' => $this->faker->numberBetween(2015, 2020),
            'term' => $this->faker->randomElement([1, 2]),
        ];
    }
}
