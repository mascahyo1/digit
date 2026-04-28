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

---

## 💓 Heartbeat — Membedakan "Crash" vs "Lambat tapi Masih Jalan"

### Masalah: Staleness Check yang Naif

Bayangkan job memproses 1 triliun baris. Satu step (10%) butuh 30 menit karena datanya besar.

Kalau staleness check berbasis `updated_at` dari progress:

```
t=0m   progress 0%,  updated_at = sekarang
t=5m   status endpoint dipanggil polling
        → "5 menit tanpa update → ERROR!"  ← FALSE ALARM!
        padahal job masih jalan, cuma lagi sibuk proses baris ke-500 juta
```

### Solusi: Dua Cache Key dengan Frekuensi Berbeda

```
download_status_{id}      → progress %
  Update: JARANG — hanya saat persentase naik
  Misal: update tiap 10% = tiap 30 menit untuk dataset besar

download_heartbeat_{id}   → "saya masih hidup"
  Update: SERING — tiap N baris di dalam loop, terlepas dari %
  Misal: update tiap 100.000 baris = tiap beberapa detik
```

### Implementasi

**Job — dua metode yang berbeda tujuan:**

```php
// Progress: hanya saat % milestone tercapai
private function updateProgress(int $progress, string $status, string $message): void
{
    Cache::put("download_status_{$id}", [
        'progress' => $progress,
        'status'   => $status,
        // ...
    ]);
    event(new DownloadProgress(...)); // broadcast ke WebSocket
}

// Heartbeat: bukti masih hidup, tidak broadcast ke WebSocket
private function sendHeartbeat(): void
{
    Cache::put("download_heartbeat_{$id}", now()->toIso8601String(), now()->addHours(2));
}
```

**Dalam loop pemrosesan baris:**

```php
$heartbeatEveryRows = max(1, (int) ($rowsPerStep / 10)); // lebih granular dari progress

for ($step = 1; $step <= $steps; $step++) {
    $this->updateProgress($progress, 'processing', "Step {$step}...");  // per step

    $rowInStep = 0;
    foreach ($rows as $row) {
        // ... proses baris ...

        // Heartbeat lebih granular dari progress
        if (++$rowInStep % $heartbeatEveryRows === 0) {
            $this->sendHeartbeat();
        }
    }
}
```

**Controller — staleness check pakai heartbeat:**

```php
$heartbeat = Cache::get("download_heartbeat_{$downloadId}");

if ($heartbeat) {
    $minutesSince = Carbon::parse($heartbeat)->diffInMinutes(now());

    if ($minutesSince >= 2) {
        // Heartbeat stale → benar-benar crash
        $data['status']  = 'error';
        $data['message'] = "Job crash ({$minutesSince} menit tanpa heartbeat)";
    }
    // Else: heartbeat fresh → job masih hidup meski % belum naik
}
```

### Perbandingan Skenario

```
Dataset 1 triliun baris, step 1 butuh 30 menit:

t=0m   progress 0%, heartbeat fresh
t=2m   belum naik %, TAPI heartbeat diperbarui tiap 100k baris
t=5m   staleness check: heartbeat 3 menit lalu → MASIH HIDUP ✅
t=30m  progress naik ke 10%

Jika worker crash di t=5m:
t=5m   heartbeat berhenti diperbarui
t=7m   staleness check: heartbeat 2 menit lalu → CRASH! ✅ terdeteksi
```

### Aturan Umum Memilih Interval Heartbeat

- **Threshold stale**: 2× interval heartbeat + buffer. Contoh: heartbeat tiap 30 detik → stale setelah 2 menit.
- **Interval heartbeat**: sesuaikan dengan beban proses. Jangan terlalu sering (overhead cache), jangan terlalu jarang (deteksi lambat).
- **Produksi besar**: heartbeat tiap 10.000–100.000 baris. Stale setelah 2–5 menit.

---

## 🔁 Chat Reconnect Catch-up — Tidak Ada Pesan yang Terlewat

### Apakah Chat Perlu Heartbeat?

**Tidak.** Heartbeat ada karena ada background job yang berjalan sendiri dan bisa crash diam-diam. Chat tidak punya background job — server hanya menunggu pesan dari user, lalu broadcast. Tidak ada proses yang bisa "mati tanpa diketahui".

### Apakah Chat Perlu Polling Fallback?

**Tidak perlu terus-menerus.** Berbeda dengan download yang progressnya terus berubah, pesan chat tersimpan permanen di **database**. Saat WebSocket putus lalu reconnect, cukup satu HTTP request untuk mengambil semua yang terlewat.

### Strategi: Catch-up on Reconnect

```
Download:  polling terus tiap 3 detik (karena progress terus berubah di cache)
Chat:      satu request saat reconnect saja (karena pesan ada di DB, tidak hilang)
```

### Implementasi

**Backend — endpoint `GET /private-chat/{user}/messages?since={lastId}`:**

```php
public function messagesSince(Request $request, User $user)
{
    $since = (int) $request->query('since', 0);

    // Ambil semua pesan antara dua user dengan id > since
    $messages = PrivateMessage::with(['sender:id,name'])
        ->where(fn($q) => $q->where('sender_id', $me->id)->where('receiver_id', $user->id))
        ->orWhere(fn($q) => $q->where('sender_id', $user->id)->where('receiver_id', $me->id))
        ->where('id', '>', $since)
        ->oldest()
        ->get();

    return response()->json(['messages' => $messages]);
}
```

**Frontend — `Chat.vue` (Pusher state_change listener):**

```js
// Track ID pesan terakhir yang diketahui
const lastMessageId = computed(() => Math.max(...messages.value.map(m => m.id)));

window.Echo.connector.pusher.connection.bind('state_change', async ({ previous, current }) => {
    if (current === 'connected') {
        wsState.value = 'connected';

        // Reconnect setelah disconnect → fetch pesan yang terlewat
        if (previous === 'connecting' || previous === 'disconnected') {
            const { data } = await axios.get(
                `/private-chat/${chatWith.id}/messages?since=${lastMessageId.value}`
            );
            // Tambahkan pesan baru, hindari duplikat
            const existingIds = new Set(messages.value.map(m => m.id));
            const newMsgs = data.messages.filter(m => !existingIds.has(m.id));
            messages.value.push(...newMsgs.map(m => ({ ...m, _missed: true })));
        }
    } else if (current === 'connecting') {
        wsState.value = 'connecting';  // tampil banner "Reconnecting..."
    } else {
        wsState.value = 'disconnected';
    }
});
```

### Alur Saat Koneksi Terputus

```
t=0m   User A dan B chat normal via WebSocket
t=1m   ⚠️ Koneksi User A putus (Reverb restart / internet flicker)
        → banner kuning "WebSocket terputus" muncul di UI User A
        → indikator avatar berubah warna

t=1-3m User B kirim 3 pesan → tidak sampai ke User A (WS putus)
        → pesan tersimpan di DB oleh server ✅

t=3m   WebSocket User A reconnect (Pusher auto-reconnect)
        → state_change: 'connecting' → 'connected'
        → GET /private-chat/B/messages?since=142
        → server kembalikan 3 pesan yang terlewat ✅
        → pesan muncul dengan tanda 📶⃠ (wifi-slash) di UI

t=3m+  Banner kuning hilang, chat kembali normal
```

### Mengapa Tidak Polling Terus seperti Download?

Karena **database adalah source of truth yang permanen**. Pesan tidak bisa hilang dari DB. Jadi tidak perlu cek terus-menerus — cukup tanya "ada yang baru?" sekali saat koneksi pulih.

Polling terus-menerus untuk chat justru boros dan tidak perlu:
- Setiap user polling tiap 3 detik = banyak request mubazir ke DB
- WebSocket sudah jalan normal → polling jadi sia-sia
- DB adalah persistent store, tidak ada "race" seperti cache yang bisa expire

---

## 🟢 Online Status via Heartbeat — Siapa yang Sedang Aktif?

### Konteks: Heartbeat di Chat vs Download

Heartbeat di download dipakai untuk **mendeteksi crash server-side job**.

Heartbeat di chat dipakai untuk hal yang berbeda: **mendeteksi apakah user masih membuka halaman** (client-side presence). Keduanya menggunakan mekanisme yang sama (cache TTL), tapi tujuannya berbeda.

### Cara Kerja

```
User buka /chat atau /private-chat → frontend mulai kirim heartbeat setiap 30 detik
  → POST /chat/heartbeat
  → server: Cache::put("user_online_{id}", true, TTL 70 detik)

User tutup tab / logout → heartbeat berhenti
  → 70 detik berlalu → cache key expired → user dianggap offline
```

Kenapa TTL 70 detik untuk interval 30 detik? Memberi toleransi satu kali gagal ping (misal koneksi lambat sesaat), tanpa langsung dianggap offline.

### Implementasi

**Backend — `POST /chat/heartbeat`:**
```php
public function heartbeat(Request $request)
{
    Cache::put("user_online_{$userId}", true, now()->addSeconds(70));
    return response()->json(['ok' => true]);
}
```

**Backend — cek status saat load daftar user:**
```php
$users = User::where('id', '!=', $me->id)->get()->map(fn($u) => [
    'id'        => $u->id,
    'name'      => $u->name,
    'is_online' => Cache::has("user_online_{$u->id}"),  // true/false
    // ...
]);
```

**Frontend — kirim heartbeat saat komponen aktif:**
```js
let heartbeatTimer = null;

onMounted(() => {
    sendHeartbeat();                              // langsung saat buka halaman
    heartbeatTimer = setInterval(sendHeartbeat, 30_000);  // lanjut tiap 30 detik
});

onUnmounted(() => {
    clearInterval(heartbeatTimer);  // berhenti saat user tutup halaman
});

function sendHeartbeat() {
    axios.post(route('chat.heartbeat')).catch(() => {});
}
```

**Frontend — tampilkan di UI:**
```html
<!-- Dot online/offline -->
<span :class="user.is_online ? 'bg-green-400' : 'bg-gray-600'"></span>

<!-- Teks status -->
<p :class="user.is_online ? 'text-green-400' : 'text-gray-500'">
    {{ user.is_online ? 'Online' : 'Offline' }}
</p>
```

### Trade-off vs Presence Channel

Alternatif yang lebih "proper" adalah **Presence Channel** dari Pusher/Reverb yang secara native tahu siapa yang sedang subscribe. Namun heartbeat cache memiliki keunggulan:

- Lebih sederhana diimplementasi
- Tidak terikat pada WebSocket (bekerja bahkan jika WS putus sesaat)
- Bisa dipakai lintas halaman (bukan hanya di halaman chat tertentu)
- Kontrol penuh atas logika kapan dianggap "online"

Kekurangannya: ada delay maksimal 70 detik sebelum status berubah ke offline.
