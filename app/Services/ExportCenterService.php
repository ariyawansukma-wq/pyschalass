<?php

namespace App\Services;

use App\Jobs\GenerateBatchReportJob;
use App\Models\GeneratedReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class ExportCenterService
{
    /**
     * Max files kept per user in archive (50 files).
     */
    public const MAX_FILES_PER_USER = 50;

    /**
     * Max quota per user in bytes (500 MB).
     */
    public const MAX_QUOTA_PER_USER = 524288000; // 500 * 1024 * 1024 bytes (500 MB)

    /**
     * Automatically prune oldest files if user exceeds MAX_FILES_PER_USER or storage quota limit.
     */
    public function pruneOldFiles(?int $userId, int $incomingBytes = 0): void
    {
        $query = GeneratedReport::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // 1. File Count Limit: if count >= MAX_FILES_PER_USER, remove oldest so new count <= MAX_FILES_PER_USER
        $totalFiles = (clone $query)->count();
        if ($totalFiles >= self::MAX_FILES_PER_USER) {
            $deleteCount = ($totalFiles - self::MAX_FILES_PER_USER) + 1;
            $oldestFiles = (clone $query)->orderBy('created_at', 'asc')->limit($deleteCount)->get();
            foreach ($oldestFiles as $report) {
                $report->delete(); // static::deleting in model deletes physical file from storage disk
            }
        }

        // 2. Storage Quota Limit: if (totalBytes + incomingBytes) > MAX_QUOTA_PER_USER, remove oldest
        $totalBytes = (clone $query)->sum('file_size');
        while (($totalBytes + $incomingBytes) > self::MAX_QUOTA_PER_USER) {
            $oldest = (clone $query)->orderBy('created_at', 'asc')->first();
            if (!$oldest) {
                break;
            }
            $freedBytes = $oldest->file_size ?: 0;
            $oldest->delete();
            $totalBytes -= $freedBytes;
        }
    }

    /**
     * Store an uploaded file (from client-side auto-sync like useReportPdf) into public storage.
     */
    public function storeUploadedFile(UploadedFile $file, string $fileType, string $title, ?int $folderId, ?User $user): GeneratedReport
    {
        $userId = $user ? $user->id : auth()->id();
        $fileSize = $file->getSize() ?: 0;

        // Auto prune oldest files before saving if limit is reached
        $this->pruneOldFiles($userId, $fileSize);

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: ($fileType === 'pdf' ? 'pdf' : 'xlsx');
        $filename = uniqid('export_') . '_' . time() . '.' . $extension;
        
        $directory = "exports/{$fileType}";
        $relativePath = $file->storeAs($directory, $filename, 'public');

        return GeneratedReport::create([
            'user_id'   => $userId,
            'folder_id' => $folderId,
            'title'     => $title,
            'file_type' => $fileType,
            'file_name' => $originalName ?: $filename,
            'file_path' => $relativePath,
            'file_size' => $fileSize,
        ]);
    }

    /**
     * Archive an already generated/saved file in public storage into the generated_reports table.
     */
    public function archiveFile($user, ?int $folderId, string $title, string $fileType, string $filename, string $relativePath, int $fileSize): GeneratedReport
    {
        $userId = $user instanceof User ? $user->id : (is_numeric($user) ? (int) $user : auth()->id());

        // Auto prune oldest files before archiving if limit is reached
        $this->pruneOldFiles($userId, $fileSize);

        return GeneratedReport::create([
            'user_id'   => $userId,
            'folder_id' => $folderId,
            'title'     => $title,
            'file_type' => $fileType,
            'file_name' => $filename,
            'file_path' => $relativePath,
            'file_size' => $fileSize,
        ]);
    }

    /**
     * Dispatch a background queue job for batch export generation.
     */
    public function dispatchBatchExport(?User $user, ?int $folderId = null, ?int $sportBranchId = null, string $format = 'excel', ?int $institutionId = null, array $filters = []): void
    {
        $userId = $user ? $user->id : auth()->id();

        GenerateBatchReportJob::dispatch(
            $userId,
            $folderId,
            $sportBranchId,
            $format,
            $institutionId,
            $filters
        );
    }
}
