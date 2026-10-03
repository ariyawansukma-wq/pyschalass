<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\SportBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AthleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private SportBranch $sportBranch;
    private Folder $folder;

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
        $this->folder = Folder::create(['name' => 'Test Data Folder']);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->get('/athletes');
        $response->assertRedirect('/login');
    }

    public function test_index_displays_athletes(): void
    {
        Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi Santoso',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/athletes');

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
    }

    public function test_store_creates_athlete(): void
    {
        $response = $this->actingAs($this->admin)->post('/athletes', [
            'athlete_number' => 'ATL-001',
            'name' => 'Budi Santoso',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response->assertRedirect('/athletes');
        $this->assertDatabaseHas('athletes', ['athlete_number' => 'ATL-001']);
    }

    public function test_store_blocks_duplicate_number(): void
    {
        Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/athletes', [
            'athlete_number' => 'ATL-001',
            'name' => 'Sari',
            'gender' => 'F',
            'date_of_birth' => '2006-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response->assertSessionHasErrors('athlete_number');
    }

    public function test_store_requires_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post('/athletes', []);

        $response->assertSessionHasErrors(['name', 'gender', 'date_of_birth', 'sport_branch_id', 'folder_id']);
    }

    public function test_update_athlete(): void
    {
        $athlete = Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/athletes/{$athlete->id}", [
            'athlete_number' => 'ATL-001',
            'name' => 'Budi Updated',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response->assertRedirect('/athletes');
        $this->assertDatabaseHas('athletes', ['name' => 'Budi Updated']);
    }

    public function test_upload_photo(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(1024);

        $response = $this->actingAs($this->admin)->post('/athletes', [
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
            'photo' => $file,
        ]);

        $response->assertRedirect('/athletes');
        $athlete = Athlete::where('athlete_number', 'ATL-001')->first();
        $this->assertNotNull($athlete->photo_path);
        \Storage::disk('public')->assertExists($athlete->photo_path);
    }

    public function test_destroy_when_unused(): void
    {
        $athlete = Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/athletes/{$athlete->id}");

        $response->assertRedirect('/athletes');
        $this->assertDatabaseMissing('athletes', ['athlete_number' => 'ATL-001']);
    }

    public function test_store_calculates_bmi_automatically(): void
    {
        $response = $this->actingAs($this->admin)->post('/athletes', [
            'athlete_number' => 'ATL-002',
            'name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
            'height' => 180,
            'weight' => 75,
        ]);

        $response->assertRedirect('/athletes');
        $this->assertDatabaseHas('athletes', [
            'athlete_number' => 'ATL-002',
            'height' => 180.0,
            'weight' => 75.0,
            'bmi' => 23.15,
        ]);
    }

    public function test_destroy_all_athletes(): void
    {
        Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'Budi',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);
        Athlete::create([
            'athlete_number' => 'ATL-002',
            'name' => 'Sari',
            'gender' => 'F',
            'date_of_birth' => '2006-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/athletes/delete-all");

        $response->assertRedirect('/athletes');
        $this->assertDatabaseMissing('athletes', ['athlete_number' => 'ATL-001']);
        $this->assertDatabaseMissing('athletes', ['athlete_number' => 'ATL-002']);
    }

    public function test_store_with_sessions_records_trials_and_anthropometries(): void
    {
        $indicator = \App\Models\Indicator::create([
            'name' => 'Push Up',
            'sport_branch_id' => $this->sportBranch->id,
            'scoring_direction' => 'HIGHER_IS_BETTER',
            'unit' => 'times',
        ]);

        $response = $this->actingAs($this->admin)->post('/athletes', [
            'athlete_number' => 'ATL-003',
            'name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
            'sessions' => [
                '1' => [
                    'name' => 'Session 1',
                    'date' => '2026-08-01',
                    'color' => '#112233',
                    'height' => 175.5,
                    'weight' => 70.0,
                    'indicators' => [
                        $indicator->id => 25.5
                    ]
                ]
            ]
        ]);

        $response->assertRedirect('/athletes');

        $athlete = Athlete::where('athlete_number', 'ATL-003')->first();
        $this->assertNotNull($athlete);

        // Verify session was created
        $session = \App\Models\Session::where('name', 'Session 1')->first();
        $this->assertNotNull($session);
        $this->assertEquals('#112233', $session->color);

        // Verify anthropometry was recorded
        $this->assertDatabaseHas('anthropometries', [
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'height' => 175.5,
            'weight' => 70.0,
        ]);

        // Verify trial was recorded
        $this->assertDatabaseHas('trials', [
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'indicator_id' => $indicator->id,
            'value' => 25.50,
        ]);
    }

    public function test_update_with_sessions_updates_trials_and_anthropometries(): void
    {
        $athlete = Athlete::create([
            'athlete_number' => 'ATL-004',
            'name' => 'Jane Doe',
            'gender' => 'F',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
        ]);

        $indicator = \App\Models\Indicator::create([
            'name' => 'Sit Up',
            'sport_branch_id' => $this->sportBranch->id,
            'scoring_direction' => 'HIGHER_IS_BETTER',
            'unit' => 'times',
        ]);

        // First save a session and trial
        $session = \App\Models\Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session A',
            'date_time' => '2026-08-01 00:00:00',
            'color' => '#aaaaaa',
        ]);

        \App\Models\Anthropometry::create([
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'height' => 160.0,
            'weight' => 50.0,
            'bmi' => 19.5,
        ]);

        \App\Models\Trial::create([
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'indicator_id' => $indicator->id,
            'trial_number' => 1,
            'value' => 15.0,
        ]);

        // Put request to update it
        $response = $this->actingAs($this->admin)->put("/athletes/{$athlete->id}", [
            'athlete_number' => 'ATL-004',
            'name' => 'Jane Doe Updated',
            'gender' => 'F',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $this->folder->id,
            'sessions' => [
                '1' => [
                    'name' => 'Session A', // matching session name
                    'date' => '2026-08-02',
                    'color' => '#bbbbbb',
                    'height' => 161.0,
                    'weight' => 51.0,
                    'indicators' => [
                        $indicator->id => 20.0
                    ]
                ]
            ]
        ]);

        $response->assertRedirect('/athletes');

        // Verify anthropometry was updated
        $this->assertDatabaseHas('anthropometries', [
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'height' => 161.0,
            'weight' => 51.0,
        ]);

        // Verify trial was updated
        $this->assertDatabaseHas('trials', [
            'athlete_id' => $athlete->id,
            'session_id' => $session->id,
            'indicator_id' => $indicator->id,
            'value' => 20.00,
        ]);
    }

    public function test_update_athlete_adds_specified_folder_and_keeps_all_others(): void
    {
        $folder1 = Folder::create(['name' => 'Folder 1']);
        $folder2 = Folder::create(['name' => 'Folder 2']);
        $folder3 = Folder::create(['name' => 'Folder 3']);

        $athlete = Athlete::create([
            'athlete_number' => 'ATL-005',
            'name' => 'Folder Test Athlete',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $folder1->id,
        ]);

        $athlete->folders()->attach([$folder1->id, $folder3->id]);

        $response = $this->actingAs($this->admin)->put("/athletes/{$athlete->id}", [
            'athlete_number' => 'ATL-005',
            'name' => 'Folder Test Athlete',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $this->sportBranch->id,
            'folder_id' => $folder2->id,
            'original_folder_id' => $folder1->id,
        ]);

        $response->assertRedirect('/athletes');

        $this->assertTrue($athlete->fresh()->folders->contains($folder1));
        $this->assertTrue($athlete->fresh()->folders->contains($folder2));
        $this->assertTrue($athlete->fresh()->folders->contains($folder3));
    }
}
