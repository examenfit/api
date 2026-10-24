<?php

namespace Tests\Feature;

use App\Models\Methodology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Safety net for the owen-it/laravel-auditing upgrades: config/audit.php has to match the package's
// config shape (v14 throws "Invalid UserResolver implementation" on every audited write otherwise).
class AuditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audited_model_changes_are_recorded_with_the_user()
    {
        config(['audit.console' => true]); // tests run in the console; auditing skips console writes by default

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        Schema::disableForeignKeyConstraints();
        $methodology = Methodology::create(['stream_id' => 1, 'name' => 'before']);
        $methodology->update(['name' => 'after']);
        $methodology->delete();
        Schema::enableForeignKeyConstraints();

        $audits = DB::table('audits')
            ->where('auditable_type', Methodology::class)
            ->orderBy('id')
            ->get();

        $this->assertSame(['created', 'updated', 'deleted'], $audits->pluck('event')->all());
        $this->assertSame($user->id, (int) $audits[0]->user_id);
        $this->assertSame(['name' => 'after'], json_decode($audits[1]->new_values, true));
    }
}
