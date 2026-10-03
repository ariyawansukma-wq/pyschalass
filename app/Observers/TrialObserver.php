<?php

namespace App\Observers;

use App\Models\Trial;
use App\Services\PersonalRecordManager;

class TrialObserver
{
    private PersonalRecordManager $manager;

    public function __construct()
    {
        $this->manager = new PersonalRecordManager();
    }

    public function created(Trial $trial): void
    {
        $this->manager->syncTrial($trial);
    }

    public function updated(Trial $trial): void
    {
        $this->manager->syncTrial($trial);
    }

    public function deleted(Trial $trial): void
    {
        $this->manager->deleteTrial($trial);
    }
}
