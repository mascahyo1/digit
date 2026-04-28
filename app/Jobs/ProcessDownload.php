<?php

namespace App\Jobs;

use App\Events\DownloadProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDownload implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $downloadId,
        public string $fileType = 'csv',
        public int $totalRows = 1000,
    ) {}

    public function handle(): void
    {
        $steps = 10;
        $fileName = 'export_' . $this->downloadId . '.' . $this->fileType;

        // Broadcast: mulai
        event(new DownloadProgress(
            downloadId: $this->downloadId,
            progress: 0,
            status: 'processing',
            message: 'Memulai proses generate file...',
        ));

        sleep(1);

        // Simulasi generate data bertahap (10 step = 10% tiap step)
        $content = '';
        $rowsPerStep = (int) ceil($this->totalRows / $steps);

        if ($this->fileType === 'csv') {
            $content .= "ID,Nama,Email,Tanggal,Status\n";
        } elseif ($this->fileType === 'txt') {
            $content .= "=== DATA EXPORT ===\n";
        }

        for ($step = 1; $step <= $steps; $step++) {
            $progress = (int) (($step / $steps) * 90); // max 90% saat generate

            event(new DownloadProgress(
                downloadId: $this->downloadId,
                progress: $progress,
                status: 'processing',
                message: "Menggenerate data... ({$step}/{$steps} batch)",
            ));

            // Generate rows untuk step ini
            for ($row = 1; $row <= $rowsPerStep; $row++) {
                $id = (($step - 1) * $rowsPerStep) + $row;
                if ($id > $this->totalRows) break;

                if ($this->fileType === 'csv') {
                    $content .= "{$id},User {$id},user{$id}@example.com," . now()->subDays(rand(1, 365))->format('Y-m-d') . ",aktif\n";
                } else {
                    $content .= "Record #{$id} | User {$id} | user{$id}@example.com | " . now()->format('Y-m-d') . "\n";
                }
            }

            sleep(1); // Simulasi proses berat
        }

        // Simpan file ke storage
        event(new DownloadProgress(
            downloadId: $this->downloadId,
            progress: 95,
            status: 'saving',
            message: 'Menyimpan file...',
        ));

        Storage::disk('local')->put("downloads/{$fileName}", $content);

        sleep(1);

        // Selesai!
        event(new DownloadProgress(
            downloadId: $this->downloadId,
            progress: 100,
            status: 'completed',
            message: 'File siap didownload!',
            fileName: $fileName,
        ));
    }
}
