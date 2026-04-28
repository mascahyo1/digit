<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DownloadController extends Controller
{
    public function index()
    {
        return Inertia::render('Download');
    }

    /**
     * Step 1: Generate download ID saja.
     * Frontend akan subscribe ke channel ini SEBELUM job di-dispatch.
     */
    public function prepare(Request $request)
    {
        $request->validate([
            'file_type'  => 'required|in:csv,txt',
            'total_rows' => 'required|integer|min:100|max:5000',
        ]);

        $downloadId = Str::uuid()->toString();

        // Simpan metadata sementara di cache (5 menit),
        // agar step dispatch bisa validasi ID-nya sah.
        cache()->put("download_meta_{$downloadId}", [
            'file_type'  => $request->file_type,
            'total_rows' => $request->total_rows,
        ], now()->addMinutes(5));

        return response()->json([
            'download_id' => $downloadId,
        ]);
    }

    /**
     * Step 2: Frontend sudah subscribe → baru dispatch job.
     * Dipanggil setelah frontend berhasil listen ke channel.
     */
    public function dispatch(Request $request)
    {
        $request->validate([
            'download_id' => 'required|uuid',
        ]);

        $downloadId = $request->download_id;
        $meta = cache()->pull("download_meta_{$downloadId}");

        if (! $meta) {
            return response()->json([
                'message' => 'Download ID tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        ProcessDownload::dispatch(
            downloadId: $downloadId,
            fileType:   $meta['file_type'],
            totalRows:  $meta['total_rows'],
        );

        return response()->json([
            'message' => 'Job berhasil didispatch!',
        ]);
    }

    /**
     * Polling fallback: kembalikan status terbaru dari cache.
     * Dipanggil frontend saat WebSocket tidak menerima event.
     *
     * Skenario error yang ditangani:
     * 1. Exception PHP di job       → failed() dipanggil Laravel → cache di-update ke 'error'
     * 2. Queue worker crash/di-kill → failed() TIDAK dipanggil   → cache tidak di-update
     *    → ditangani di sini via staleness check: jika tidak ada update > STALE_MINUTES,
     *      paksa status menjadi 'error' agar frontend tidak stuck selamanya.
     */
    public function status(string $downloadId)
    {
        $data = Cache::get("download_status_{$downloadId}");

        if (! $data) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Staleness check — hanya untuk status yang masih "berjalan"
        //
        // PENTING: cek HEARTBEAT, bukan updated_at dari progress!
        //
        // Kenapa? Karena untuk dataset besar (1 triliun baris):
        //   - Progress (%) mungkin tidak naik selama 30 menit (satu step = 10%)
        //   - Tapi job masih jalan dan kirim heartbeat setiap N baris
        //   - Jika cek updated_at progress → FALSE ALARM: dianggap crash padahal masih jalan
        //   - Jika cek heartbeat → BENAR: heartbeat masih fresh = job masih hidup
        //
        $HEARTBEAT_STALE_MINUTES = 2; // heartbeat lebih agresif: 2 menit tanpa heartbeat = crash

        $isRunning = in_array($data['status'], ['processing', 'saving', 'connecting']);

        if ($isRunning) {
            $heartbeat = Cache::get("download_heartbeat_{$downloadId}");

            if ($heartbeat) {
                // Ada heartbeat — cek apakah masih fresh
                $minutesSinceHeartbeat = \Carbon\Carbon::parse($heartbeat)->diffInMinutes(now());

                if ($minutesSinceHeartbeat >= $HEARTBEAT_STALE_MINUTES) {
                    // Heartbeat stale: job crash/killed tanpa memanggil failed()
                    $data['status']  = 'error';
                    $data['message'] = "Job berhenti merespons ({$minutesSinceHeartbeat} menit tanpa heartbeat). "
                        . 'Worker mungkin crash, kehabisan memory, atau di-restart.';
                }
                // Else: heartbeat masih fresh → job masih hidup, meski % belum naik
                // (kasus dataset sangat besar yang prosesnya lambat)
            } else {
                // Tidak ada heartbeat sama sekali → job mungkin belum dimulai
                // atau sangat awal. Biarkan — progress akan kirim heartbeat segera.
            }
        }

        return response()->json($data);
    }

    public function download(string $fileName)
    {
        $path = "downloads/{$fileName}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->download($path, $fileName);
    }
}
