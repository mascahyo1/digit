# 📚 Penjelasan Konsep: Reverb, Echo, pusher-js, concurrently

---

## 🔌 WebSocket vs HTTP — Perbedaan Dasar

### HTTP Biasa (Polling)

Browser harus terus-terusan *bertanya* ke server:

```
Browser → "Sudah selesai?"  → Server: "Belum"
Browser → "Sudah selesai?"  → Server: "Belum"
Browser → "Sudah selesai?"  → Server: "Sudah! Ini datanya."
```

Masalahnya: boros bandwidth, lambat, tidak efisien.

### WebSocket (Push / Real-time)

Koneksi dibuka sekali, lalu server bisa kirim data kapan saja tanpa browser meminta:

```
Browser → "Halo server, saya connect"
Server  → "Oke, koneksi terbuka"
...
Server  → "Progress 30%!"   ← server yang inisiatif kirim
Server  → "Progress 60%!"
Server  → "Selesai 100%!"
```

Satu koneksi permanen, jauh lebih efisien.

---

## 📡 Laravel Reverb

**Reverb adalah WebSocket *server* yang dijalankan di komputer/server kamu sendiri.**

```bash
php artisan reverb:start   # jalan di localhost:8080
```

### Tugasnya:
1. Menerima koneksi WebSocket dari browser
2. Menerima broadcast event dari Laravel (lewat HTTP internal)
3. Mendistribusikan event tersebut ke semua browser yang subscribe channel yang relevan

### Alurnya:
```
Laravel app
  └─ event(new DownloadProgress(...))
       └─ Reverb menerima event
            └─ Reverb push ke browser via WebSocket
                 └─ Browser update UI
```

### Kenapa bukan Pusher (cloud)?

[Pusher](https://pusher.com/) adalah layanan WebSocket cloud berbayar. Reverb adalah **alternatif gratis** yang jalan di server sendiri, tanpa batas koneksi, tanpa biaya bulanan. Protokolnya sengaja dibuat kompatibel dengan Pusher agar bisa pakai ekosistem library yang sama.

---

## 📦 pusher-js

**Library JavaScript di sisi browser** yang tahu cara berkomunikasi menggunakan protokol WebSocket Pusher/Reverb.

```js
import Pusher from 'pusher-js';

const pusher = new Pusher('app-key', {
    wsHost: 'localhost',
    wsPort: 8080,
    cluster: 'mt1',
});

// Subscribe ke channel
const channel = pusher.subscribe('download.abc-123');

// Dengarkan event
channel.bind('App\\Events\\DownloadProgress', (data) => {
    console.log(data.progress); // 30, 60, 100...
});
```

Kamu **tidak pakai pusher-js secara langsung** di project ini, tapi dia wajib ada karena Laravel Echo menggunakannya sebagai *engine* di balik layar.

---

## 🔊 Laravel Echo

**Wrapper (pembungkus) di atas pusher-js** yang membuat sintaksnya lebih simpel dan sesuai konvensi Laravel.

### Perbandingan: pusher-js mentah vs Laravel Echo

**Dengan pusher-js langsung (verbose):**
```js
const pusher = new Pusher('key', { wsHost: 'localhost', wsPort: 8080 });
const ch = pusher.subscribe('download.abc-123');
ch.bind('App\\Events\\DownloadProgress', (data) => {
    console.log(data);
});
```

**Dengan Laravel Echo (bersih):**
```js
window.Echo.channel('download.abc-123')
    .listen('.progress.updated', (data) => {
        console.log(data);
    });
```

### Konfigurasi Echo (di `bootstrap.js`):

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher; // wajib: Echo butuh Pusher tersedia global

window.Echo = new Echo({
    broadcaster: 'reverb',    // pakai Reverb, bukan Pusher cloud
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,      // localhost
    wsPort: import.meta.env.VITE_REVERB_PORT,      // 8080
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

### Hubungan antar library:

```
Laravel Echo
  └─ menggunakan pusher-js sebagai "engine" koneksi
       └─ konek ke Reverb WebSocket server (port 8080)
```

### Fitur tambahan Echo:

| Fitur | Keterangan |
|-------|-----------|
| `.channel()` | Public channel — siapa saja bisa subscribe |
| `.private()` | Private channel — perlu auth Laravel |
| `.presence()` | Presence channel — tahu siapa saja yang online |
| Auto-reconnect | Otomatis reconnect jika koneksi putus |
| `.stopListening()` | Berhenti dengarkan event tertentu |
| `Echo.leave()` | Unsubscribe dari channel |

---

## ⚡ concurrently

**Bukan bagian dari WebSocket.** Ini adalah utilitas development untuk menjalankan beberapa perintah terminal sekaligus dalam satu perintah.

### Tanpa concurrently — butuh 3 terminal terpisah:

```bash
# Terminal 1
php artisan reverb:start --debug

# Terminal 2
php artisan queue:work --verbose

# Terminal 3
npm run vite
```

### Dengan concurrently — cukup 1 perintah:

```bash
npm run dev
```

Karena di `package.json` sudah dikonfigurasi:

```json
"scripts": {
    "dev": "concurrently --names \"vite,reverb,queue\" --prefix-colors \"cyan,blue,green\" \"vite\" \"php artisan reverb:start --debug\" \"php artisan queue:work --verbose\""
}
```

Output di terminal akan berwarna-warni dan berlabel:

```
[vite]   → output dari Vite (hot reload, port 5173)
[reverb] → output dari Reverb WebSocket server (port 8080)
[queue]  → output dari queue worker (background jobs)
```

---

## 🗺️ Gambaran Lengkap Semua Komponen

```
┌─────────────────────────────────────────────────────────────┐
│  Browser                                                     │
│                                                              │
│  Download.vue                                                │
│    └─ window.Echo.channel('download.xyz')                   │
│         └─ .listen('.progress.updated', fn)                  │
│              │                                               │
│         Laravel Echo (laravel-echo)                          │
│              │                                               │
│         pusher-js (driver WebSocket)                         │
│              │                                               │
└──────────────┼──────────────────────────────────────────────┘
               │ WebSocket (ws://localhost:8080)
┌──────────────┼──────────────────────────────────────────────┐
│  Server      │                                               │
│              │                                               │
│  Reverb WebSocket Server (port 8080)                         │
│    └─ menerima koneksi browser                              │
│    └─ menerima event dari Laravel                            │
│    └─ push event ke browser yang subscribe                   │
│              │                                               │
│  Laravel App (port 80/8000)                                  │
│    └─ Queue Worker                                           │
│         └─ ProcessDownload Job                               │
│              └─ event(new DownloadProgress(...))             │
│                   └─ kirim ke Reverb                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 🧠 Ringkasan

| Library | Jalan di | Peran |
|---------|----------|-------|
| **Laravel Reverb** | Server (PHP) | WebSocket server, distribusi event |
| **pusher-js** | Browser (JS) | Engine WebSocket, tahu protokol Pusher |
| **laravel-echo** | Browser (JS) | Wrapper pusher-js, sintaks lebih mudah |
| **concurrently** | Terminal (Dev) | Jalankan Vite + Reverb + Queue 1 perintah |

---

## 🔄 WebSocket Fallback ke HTTP Polling

### Masalah: Progress Stuck

Bayangkan download sedang berjalan di 60%. Tiba-tiba:
- Reverb server restart
- Koneksi internet client putus sebentar
- Browser tab di-minimize terlalu lama

Akibatnya: **tidak ada event WebSocket yang masuk** → progress bar **stuck forever** di 60%.

### Solusi: Dual-mode Progress Tracking

Project ini menggunakan strategi **dua jalur sekaligus**:

```
Job berjalan di background
  │
  ├─ 1. Broadcast via WebSocket (Reverb)  → cepat, real-time
  └─ 2. Simpan ke Cache (database/redis)  → sebagai cadangan
```

Jika WebSocket hidup → pakai WebSocket.
Jika WebSocket mati → baca dari cache via HTTP.

### Implementasi

**Backend — `ProcessDownload.php`:**
```php
private function updateProgress(int $progress, string $status, string $message): void
{
    // Simpan ke cache (TTL 2 jam) — untuk polling fallback
    Cache::put("download_status_{$downloadId}", [
        'progress' => $progress,
        'status'   => $status,
        'message'  => $message,
    ], now()->addHours(2));

    // Broadcast via WebSocket seperti biasa
    event(new DownloadProgress(...));
}
```

**Backend — `GET /download/status/{downloadId}`:**
```php
public function status(string $downloadId)
{
    $data = Cache::get("download_status_{$downloadId}");
    return response()->json($data); // dibaca saat polling
}
```

**Frontend — `Download.vue` (logika utama):**
```js
const SILENCE_TIMEOUT_MS  = 12_000; // 12 detik tanpa event → switch polling
const POLLING_INTERVAL_MS =  3_000; // poll setiap 3 detik

// Reset timer setiap kali event WebSocket masuk
function resetSilenceTimer(downloadId) {
    clearTimeout(silenceTimer);
    silenceTimer = setTimeout(() => {
        // Tidak ada event 12 detik → switch ke polling
        connectionMode.value = 'polling';
        startPolling(downloadId);
    }, SILENCE_TIMEOUT_MS);
}

// HTTP Polling fallback
function startPolling(downloadId) {
    pollingTimer = setInterval(async () => {
        const { data } = await axios.get(`/download/status/${downloadId}`);
        applyPayload(data); // update UI dari cache
    }, POLLING_INTERVAL_MS);
}
```

### Alur Lengkap Saat Koneksi Bermasalah

```
t=0s   Download mulai, subscribe Echo channel
        → silence timer dimulai (12 detik)

t=3s   Event WebSocket masuk (progress 30%)
        → silence timer direset

t=6s   Event WebSocket masuk (progress 60%)
        → silence timer direset

t=7s   ⚠️ Reverb tiba-tiba mati!

t=19s  Silence timer habis (12 detik tanpa event)
        → connectionMode = 'polling'
        → mulai HTTP polling setiap 3 detik

t=22s  GET /download/status/{id} → progress 70% dari cache ✅
t=25s  GET /download/status/{id} → progress 80% dari cache ✅
t=28s  GET /download/status/{id} → progress 90% dari cache ✅

t=30s  Reverb nyala lagi, Echo reconnect
        → connectionMode = 'websocket'
        → polling berhenti otomatis

t=32s  Event WebSocket masuk (progress 100%, completed) ✅
```

### Indikator di UI

Saat proses berjalan, ada badge kecil di pojok progress card:

| Badge | Arti |
|-------|------|
| 🟢 **WebSocket** | Menerima event real-time dari Reverb |
| 🟠 **HTTP Polling** | WebSocket tidak responsif, fallback ke HTTP setiap 3 detik |

### Trade-off

| Aspek | WebSocket | HTTP Polling |
|-------|-----------|-------------|
| Kecepatan update | Instan (< 100ms) | Maksimal 3 detik delay |
| Beban server | Rendah (1 koneksi) | Lebih tinggi (request per 3 detik) |
| Keandalan | Bergantung pada koneksi WS | Selalu bisa jika HTTP ok |
| Kompleksitas | Sederhana | Butuh endpoint + cache |

> **Kesimpulan:** WebSocket untuk performa, polling untuk keandalan. Kombinasi keduanya menghasilkan sistem yang robust.
