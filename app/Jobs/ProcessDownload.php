<?php

namespace App\Jobs;

use App\Events\DownloadProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProcessDownload implements ShouldQueue
{
    use Queueable;

    // Jika job gagal (exception), akan ditandai error di cache
    public int $tries = 1;

    public function __construct(
        public string $downloadId,
        public string $fileType = 'csv',
        public int $totalRows = 1000,
    ) {}

    public function handle(): void
    {
        $steps    = 10;
        $fileName = 'export_' . $this->downloadId . '.' . $this->fileType;

        $this->updateProgress(0, 'processing', 'Memulai proses generate file...');
        sleep(1);

        $content     = '';
        $rowsPerStep = (int) ceil($this->totalRows / $steps);

        if ($this->fileType === 'csv') {
            $content .= "ID,Nama,Email,Tanggal,Status\n";
        } elseif ($this->fileType === 'txt') {
            $content .= "=== DATA EXPORT ===\n";
        }

        for ($step = 1; $step <= $steps; $step++) {
            $progress = (int) (($step / $steps) * 90);

            $this->updateProgress($progress, 'processing', "Menggenerate data... ({$step}/{$steps} batch)");

            for ($row = 1; $row <= $rowsPerStep; $row++) {
                $id = (($step - 1) * $rowsPerStep) + $row;
                if ($id > $this->totalRows) break;

                if ($this->fileType === 'csv') {
                    $content .= "{$id},User {$id},user{$id}@example.com," . now()->subDays(rand(1, 365))->format('Y-m-d') . ",aktif\n";
                } else {
                    $content .= "Record #{$id} | User {$id} | user{$id}@example.com | " . now()->format('Y-m-d') . "\n";
                }
            }

            sleep(1);
        }

        $this->updateProgress(95, 'saving', 'Menyimpan file...');
        Storage::disk('local')->put("downloads/{$fileName}", $content);
        sleep(1);

        $this->updateProgress(100, 'completed', 'File siap didownload!', $fileName);
    }

    /**
     * Jika job gagal (exception tak terduga), tandai sebagai error di cache.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateProgress(0, 'error', 'Terjadi kesalahan: ' . $exception->getMessage());
    }

    /**
     * Broadcast event ke WebSocket DAN simpan ke cache sekaligus.
     * Cache dipakai sebagai fallback ketika WebSocket client putus.
     */
    private function updateProgress(
        int $progress,
        string $status,
        string $message,
        ?string $fileName = null,
    ): void {
        $payload = [
            'download_id' => $this->downloadId,
            'progress'    => $progress,
            'status'      => $status,
            'message'     => $message,
            'file_name'   => $fileName,
            'updated_at'  => now()->toIso8601String(),
        ];

        // 1. Simpan ke cache (TTL 2 jam) — ini yang dibaca saat polling fallback
        Cache::put("download_status_{$this->downloadId}", $payload, now()->addHours(2));

        // 2. Broadcast via Reverb WebSocket seperti biasa
        event(new DownloadProgress(
            downloadId: $this->downloadId,
            progress: $progress,
            status: $status,
            message: $message,
            fileName: $fileName,
        ));
    }
}
