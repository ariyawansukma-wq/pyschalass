<?php

namespace App\Jobs;

use App\Exports\FolderExport;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\GeneratedReport;
use App\Models\Institution;
use App\Models\SportBranch;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateBatchReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes timeout for large batches
    public int $tries = 2;

    public function __construct(
        public ?int $userId,
        public ?int $folderId = null,
        public ?int $sportBranchId = null,
        public string $format = 'excel', // 'excel' or 'pdf'
        public ?int $institutionId = null,
        public array $filters = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting GenerateBatchReportJob: Format={$this->format}, Folder={$this->folderId}, SportBranch={$this->sportBranchId}");

        $folder = $this->folderId ? Folder::find($this->folderId) : null;
        $sportBranch = $this->sportBranchId ? SportBranch::find($this->sportBranchId) : null;

        // Build athlete query
        $query = Athlete::with(['sportBranch.indicators', 'folders']);

        if ($this->folderId) {
            $query->whereHas('folders', fn($q) => $q->where('folders.id', $this->folderId));
        }

        if ($this->sportBranchId) {
            $query->where('sport_branch_id', $this->sportBranchId);
        }

        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('athlete_number', 'like', "%{$search}%");
            });
        }

        // Process in chunks of 100 athletes to keep memory ultra low
        $allAthletes = collect();
        $query->orderBy('name')->chunk(100, function ($chunk) use (&$allAthletes) {
            foreach ($chunk as $athlete) {
                $allAthletes->push($athlete);
            }
        });

        $totalCount = $allAthletes->count();
        $dateStr = now()->format('Y-m-d_His');

        $title = 'Batch Export - ' . ($folder ? $folder->name : ($sportBranch ? $sportBranch->name : 'All Athletes'));
        $filename = 'Export_' . ($folder ? 'Folder_' . $folder->id : ($sportBranch ? 'Branch_' . $sportBranch->id : 'Athletes')) . "_{$dateStr}.xlsx";

        if ($this->format === 'excel') {
            $export = new FolderExport($folder, $allAthletes);
            $relativePath = "exports/excel/{$filename}";

            Excel::store($export, $relativePath, 'public');

            $fullPath = Storage::disk('public')->path($relativePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            $exportCenter = new \App\Services\ExportCenterService();
            $exportCenter->archiveFile(
                $this->userId,
                $this->folderId,
                $title . " ({$totalCount} Athletes)",
                'excel',
                $filename,
                $relativePath,
                $fileSize
            );

            Log::info("GenerateBatchReportJob completed: {$relativePath}, Size={$fileSize} bytes");
        }
    }
}
