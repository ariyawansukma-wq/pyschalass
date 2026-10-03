<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SportBranch;
use App\Models\Athlete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportBranchTest extends TestCase
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

    public function test_index_requires_auth(): void
    {
        $response = $this->get('/sport-branches');
        $response->assertRedirect('/login');
    }

    public function test_index_displays_sport_branches(): void
    {
        SportBranch::create(['name' => 'Atletik', 'description' => 'Olahraga atletik']);

        $response = $this->actingAs($this->admin)->get('/sport-branches');

        $response->assertStatus(200);
        $response->assertSee('Atletik');
    }

    public function test_store_creates_sport_branch(): void
    {
        $response = $this->actingAs($this->admin)->post('/sport-branches', [
            'name' => 'Renang',
            'description' => 'Olahraga renang',
        ]);

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseHas('sport_branches', ['name' => 'Renang']);
    }

    public function test_store_validates_unique_name(): void
    {
        SportBranch::create(['name' => 'Atletik', 'user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->post('/sport-branches', [
            'name' => 'Atletik',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_sport_branch(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);

        $response = $this->actingAs($this->admin)->put("/sport-branches/{$branch->id}", [
            'name' => 'Atletik Updated',
        ]);

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseHas('sport_branches', ['name' => 'Atletik Updated']);
    }

    public function test_destroy_when_unused(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);

        $response = $this->actingAs($this->admin)->delete("/sport-branches/{$branch->id}");

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseMissing('sport_branches', ['name' => 'Atletik']);
    }

    public function test_destroy_sets_null_on_athletes(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);
        $athlete = Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/sport-branches/{$branch->id}");

        $response->assertRedirect('/sport-branches');
        $this->assertDatabaseMissing('sport_branches', ['name' => 'Atletik']);
        $this->assertDatabaseHas('athletes', [
            'id' => $athlete->id,
            'sport_branch_id' => null,
        ]);
    }

    public function test_show_displays_sport_branch_details(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik']);

        $response = $this->actingAs($this->admin)->get("/sport-branches/{$branch->id}");

        $response->assertStatus(200);
        $response->assertSee('Atletik');
    }

    public function test_duplicate_sport_branch_with_indicators(): void
    {
        $branch = SportBranch::create(['name' => 'Atletik', 'description' => 'Olahraga atletik']);
        $indicator = $branch->indicators()->create([
            'name' => 'Speed 30m',
            'unit' => 'seconds',
            'category' => 'Speed',
            'scoring_direction' => 'LOWER_IS_BETTER',
            'calculation_method' => 'BEST',
        ]);

        $response = $this->actingAs($this->admin)->post("/sport-branches/{$branch->id}/duplicate");

        $response->assertRedirect('/sport-branches');
        
        $this->assertDatabaseHas('sport_branches', ['name' => 'Atletik - copy', 'description' => 'Olahraga atletik']);
        $newBranch = SportBranch::where('name', 'Atletik - copy')->first();
        $this->assertNotNull($newBranch);

        $this->assertDatabaseHas('indicators', [
            'sport_branch_id' => $newBranch->id,
            'name' => 'Speed 30m',
            'unit' => 'seconds',
        ]);
    }
}
