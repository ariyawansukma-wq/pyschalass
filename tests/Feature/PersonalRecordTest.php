<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\Session;
use App\Models\Trial;
use App\Models\PersonalRecord;
use App\Models\SportBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalRecordTest extends TestCase
{
    use RefreshDatabase;

    private Athlete $athlete;
    private Indicator $indicator;
    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $sportBranch = SportBranch::create(['name' => 'Atletik']);
        $this->folder = Folder::create(['name' => 'Event A']);
        
        $this->athlete = Athlete::create([
            'athlete_number' => 'ATL-001',
            'name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2005-01-01',
            'sport_branch_id' => $sportBranch->id,
        ]);

        $this->indicator = Indicator::create([
            'sport_branch_id' => $sportBranch->id,
            'name' => 'Lari 100m',
            'unit' => 'detik',
            'scoring_direction' => 'LOWER_IS_BETTER', // Lower score is better for sprint time
        ]);
    }

    public function test_creating_trial_syncs_personal_record(): void
    {
        $session = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 1',
            'date_time' => '2026-08-01 08:00:00',
        ]);

        $trial = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 12.5,
        ]);

        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $this->athlete->id,
            'indicator_id' => $this->indicator->id,
            'best_value' => 12.5,
        ]);
    }

    public function test_updating_trial_to_better_value_updates_personal_record(): void
    {
        $session = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 1',
            'date_time' => '2026-08-01 08:00:00',
        ]);

        $trial = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 12.5,
        ]);

        // Update to a better value (lower is better)
        $trial->update(['value' => 11.8]);

        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $this->athlete->id,
            'indicator_id' => $this->indicator->id,
            'best_value' => 11.8,
        ]);
    }

    public function test_updating_best_trial_to_worse_value_recalculates_personal_best(): void
    {
        $session1 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 1',
            'date_time' => '2026-08-01 08:00:00',
        ]);
        $session2 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 2',
            'date_time' => '2026-08-02 08:00:00',
        ]);

        $trial1 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session1->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 12.5,
        ]);

        $trial2 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session2->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 11.8, // Best value
        ]);

        $this->assertEquals(11.8, PersonalRecord::where('athlete_id', $this->athlete->id)->first()->best_value);

        // Update trial2 (which was the best) to a worse value (13.0)
        $trial2->update(['value' => 13.0]);

        // The personal record should now be trial1's value (12.5) because 12.5 is better than 13.0
        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $this->athlete->id,
            'indicator_id' => $this->indicator->id,
            'best_value' => 12.5,
        ]);
    }

    public function test_invalidating_best_trial_recalculates_personal_best(): void
    {
        $session1 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 1',
            'date_time' => '2026-08-01 08:00:00',
        ]);
        $session2 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 2',
            'date_time' => '2026-08-02 08:00:00',
        ]);

        $trial1 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session1->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 12.5,
        ]);

        $trial2 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session2->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 11.8, // Best value
        ]);

        // Mark best trial invalid
        $trial2->update(['is_valid' => false]);

        // The personal record should now revert to 12.5
        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $this->athlete->id,
            'indicator_id' => $this->indicator->id,
            'best_value' => 12.5,
        ]);
    }

    public function test_deleting_best_trial_recalculates_personal_best(): void
    {
        $session1 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 1',
            'date_time' => '2026-08-01 08:00:00',
        ]);
        $session2 = Session::create([
            'folder_id' => $this->folder->id,
            'name' => 'Session 2',
            'date_time' => '2026-08-02 08:00:00',
        ]);

        $trial1 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session1->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 12.5,
        ]);

        $trial2 = Trial::create([
            'athlete_id' => $this->athlete->id,
            'session_id' => $session2->id,
            'indicator_id' => $this->indicator->id,
            'trial_number' => 1,
            'value' => 11.8, // Best value
        ]);

        // Delete the best trial
        $trial2->delete();

        // The personal record should now revert to 12.5
        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $this->athlete->id,
            'indicator_id' => $this->indicator->id,
            'best_value' => 12.5,
        ]);
    }
}
