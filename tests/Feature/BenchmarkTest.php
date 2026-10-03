<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Benchmark;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarkTest extends TestCase
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

    public function test_store_creates_benchmark(): void
    {
        $response = $this->actingAs($this->admin)->from('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks')->post('/benchmarks', [
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12 Tahun',
            'age_min' => 10,
            'age_max' => 12,
        ]);

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks');
        $this->assertDatabaseHas('benchmarks', [
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12 Tahun',
        ]);
    }

    public function test_store_blocks_overlapping_age_range(): void
    {
        Benchmark::create([
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12',
            'age_min' => 10,
            'age_max' => 12,
        ]);

        $response = $this->actingAs($this->admin)->post('/benchmarks', [
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 11-15',
            'age_min' => 11,
            'age_max' => 15,
        ]);

        $response->assertSessionHasErrors('age_min');
    }

    public function test_store_allows_non_overlapping_range(): void
    {
        Benchmark::create([
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12',
            'age_min' => 10,
            'age_max' => 12,
        ]);

        $response = $this->actingAs($this->admin)->from('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks')->post('/benchmarks', [
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 13-15',
            'age_min' => 13,
            'age_max' => 15,
        ]);

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks');
        $this->assertDatabaseHas('benchmarks', ['label' => 'Putra 13-15']);
    }

    public function test_update_benchmark(): void
    {
        $benchmark = Benchmark::create([
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12',
            'age_min' => 10,
            'age_max' => 12,
        ]);

        $response = $this->actingAs($this->admin)->from('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks')->put("/benchmarks/{$benchmark->id}", [
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-13',
            'age_min' => 10,
            'age_max' => 13,
        ]);

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks');
        $this->assertDatabaseHas('benchmarks', ['label' => 'Putra 10-13']);
    }

    public function test_destroy_benchmark(): void
    {
        $benchmark = Benchmark::create([
            'sport_branch_id' => $this->sportBranch->id,
            'gender' => 'M',
            'label' => 'Putra 10-12',
            'age_min' => 10,
            'age_max' => 12,
        ]);

        $response = $this->actingAs($this->admin)->from('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks')->delete("/benchmarks/{$benchmark->id}");

        $response->assertRedirect('/sport-branches/' . $this->sportBranch->id . '?tab=benchmarks');
        $this->assertDatabaseMissing('benchmarks', ['label' => 'Putra 10-12']);
    }
}
