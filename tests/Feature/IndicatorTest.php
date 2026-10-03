<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Benchmark;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private SportBranch $sportBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'role' => UserRole::Admin->value,
            'password' => bcrypt('password'),
        ]);

        $this->sportBranch = SportBranch::create(['name' => 'Atletik']);
    }


    public function test_index_displays_indicators(): void
    {
        Indicator::create([
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        $response = $this->actingAs($this->admin)->get('/sport-branches/' . $this->sportBranch->id . '?tab=indicators');

        $response->assertStatus(200);
        $response->assertSee('Lari 100m');
    }

    public function test_store_creates_indicator(): void
    {
        $response = $this->actingAs($this->admin)->post('/indicators', [
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lompat Jauh',
            'unit' => 'cm',
            'scoring_direction' => 'HIGHER_IS_BETTER',
        ]);

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=indicators');
        $this->assertDatabaseHas('indicators', ['name' => 'Lompat Jauh']);
    }

    public function test_store_requires_scoring_direction(): void
    {
        $response = $this->actingAs($this->admin)->post('/indicators', [
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Test',
            'unit' => 'cm',
        ]);

        $response->assertSessionHasErrors('scoring_direction');
    }

    public function test_update_indicator(): void
    {
        $indicator = Indicator::create([
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        $response = $this->actingAs($this->admin)->put("/indicators/{$indicator->id}", [
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lari 200m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=indicators');
        $this->assertDatabaseHas('indicators', ['name' => 'Lari 200m']);
    }

    public function test_destroy_when_unused(): void
    {
        $indicator = Indicator::create([
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        $response = $this->actingAs($this->admin)->delete("/indicators/{$indicator->id}");

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=indicators');
        $this->assertSoftDeleted('indicators', ['id' => $indicator->id]);
    }

    public function test_destroy_succeeds_when_sport_has_benchmarks(): void
    {
        $indicator = Indicator::create([
            'sport_branch_id' => $this->sportBranch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        Benchmark::create([
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12',
            'age_min' => 10,
            'age_max' => 12,
            'values' => null,
        ]);

        $response = $this->actingAs($this->admin)->delete("/indicators/{$indicator->id}");

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=indicators');
        $this->assertSoftDeleted('indicators', ['id' => $indicator->id]);
    }
}
