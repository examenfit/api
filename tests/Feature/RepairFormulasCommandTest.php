<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Runs formulas:repair against a table of its own in the test database (examenfit_test, never the dev database).
class RepairFormulasCommandTest extends TestCase
{
    private const TABLE = 'formula_repair_fixture';

    private const BROKEN = 'Zie `$$r = \sqrt[4]\frac{a_0}{b+2}$$` hier.';

    private const REPAIRED = 'Zie `$$r = \sqrt[4]{\frac{a_0}{b+2}}$$` hier.';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists(self::TABLE);
        Schema::create(self::TABLE, function ($table) {
            $table->id();
            $table->text('body');
        });
        DB::table(self::TABLE)->insert([['body' => self::BROKEN], ['body' => 'Geen formule']]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLE);

        parent::tearDown();
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $this->artisan('formulas:repair')->assertExitCode(0);

        $this->assertSame(self::BROKEN, DB::table(self::TABLE)->where('id', 1)->value('body'));
    }

    public function test_apply_repairs_the_formula_and_leaves_other_rows_alone(): void
    {
        $this->artisan('formulas:repair', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(self::REPAIRED, DB::table(self::TABLE)->where('id', 1)->value('body'));
        $this->assertSame('Geen formule', DB::table(self::TABLE)->where('id', 2)->value('body'));
    }

    public function test_a_second_run_finds_nothing_to_change(): void
    {
        $this->artisan('formulas:repair', ['--apply' => true])->assertExitCode(0);

        $this->artisan('formulas:repair')->expectsOutputToContain('0 texts change')->assertExitCode(0);
    }
}
