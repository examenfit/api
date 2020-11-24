<?php

namespace Database\Factories;

use App\Models\Facet;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Facet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->colorName,
        ];
    }
}
