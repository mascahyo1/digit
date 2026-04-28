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
     * Serve file hasil generate.
     */
    /**
     * Polling fallback: kembalikan status terbaru dari cache.
     * Dipanggil frontend saat WebSocket tidak menerima event > X detik.
     */
    public function status(string $downloadId)
    {
        $data = Cache::get("download_status_{$downloadId}");

        if (! $data) {
            return response()->json(['status' => 'not_found'], 404);
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
