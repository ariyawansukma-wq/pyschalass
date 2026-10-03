<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'role' => UserRole::Admin->value,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_deleting_sport_branch_with_athletes_succeeds(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);
        Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/sport-branches/{$branch->id}");

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseMissing('sport_branches', ['name' => 'Atletik']);
    }

    public function test_deleting_sport_branch_with_indicators_succeeds(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);
        Indicator::create([
            'sport_branch_id' => $branch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER',
        ]);

        $response = $this->actingAs($this->admin)->delete("/sport-branches/{$branch->id}");

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseMissing('sport_branches', ['name' => 'Atletik']);
    }

    public function test_deleting_unused_sport_branch_succeeds(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);

        $response = $this->actingAs($this->admin)->delete("/sport-branches/{$branch->id}");

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseMissing('sport_branches', ['name' => 'Atletik']);
    }
}
