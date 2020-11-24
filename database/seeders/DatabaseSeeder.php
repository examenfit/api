<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(1)->create();

        $course = \App\Models\Course::create([
            'name' => 'Wiskunde'
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Categorie',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Onderwerp',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Complexiteit',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Type vraag',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Tijdsduur',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);

        $facet = \App\Models\Facet::factory(1)->create([
            'course_id' => $course->id,
            'name' => 'Vaardigheid',
        ]);

        \App\Models\Facet::factory(5)->create([
            'parent_id' => $facet[0]->id,
        ]);
    }
}
