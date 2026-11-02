<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Runs formulas:check against a table of its own in the test database (examenfit_test, never the dev database).
class CheckFormulasCommandTest extends TestCase
{
    private const TABLE = 'formula_check_fixture';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists(self::TABLE);
        Schema::create(self::TABLE, function ($table) {
            $table->id();
            $table->text('body');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLE);

        parent::tearDown();
    }

    private function insertBodies(array $bodies): void
    {
        DB::table(self::TABLE)->insert(array_map(fn ($body) => ['body' => $body], $bodies));
    }

    public function test_formulas_katex_can_convert_exit_with_zero(): void
    {
        $this->insertBodies(['Zie `$$x^2 + \frac{1}{2}$$` hier.', 'Geen formule']);

        $this->artisan('formulas:check')
            ->expectsOutputToContain('1 unique formulas')
            ->expectsOutputToContain('all formulas can be converted')
            ->assertExitCode(0);
    }

    public function test_a_formula_katex_rejects_is_listed_with_its_row_and_exits_with_one(): void
    {
        $this->insertBodies(['goed `$$x^2$$`', 'stuk `$$\frac{1$$` hier']);

        $this->artisan('formulas:check')
            ->expectsOutputToContain(self::TABLE . '.body #2')
            ->expectsOutputToContain('1 formulas cannot be converted')
            ->assertExitCode(1);
    }

    public function test_a_formula_in_many_rows_lists_four_and_dots(): void
    {
        $this->insertBodies(array_fill(0, 5, '`$$\frac{1$$`'));

        $this->artisan('formulas:check')
            ->expectsOutputToContain(self::TABLE . '.body #4, ...')
            ->assertExitCode(1);
    }

    public function test_texts_without_the_formula_delimiters_are_ignored(): void
    {
        $this->insertBodies(['$$\frac{1$$ zonder backticks', 'prijs $5 en $6']);

        $this->artisan('formulas:check')->expectsOutputToContain('0 unique formulas')->assertExitCode(0);
    }
}
