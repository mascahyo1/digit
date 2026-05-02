# 🚀 Laravel Reverb Download Progress

Project belajar full-stack modern dengan fitur **download file real-time progress** menggunakan WebSocket via Laravel Reverb.

## 📦 Tech Stack

| Teknologi | Versi | Keterangan |
|-----------|-------|-----------|
| **Laravel** | 13.x | PHP framework backend |
| **Laravel Reverb** | 1.x | WebSocket server resmi Laravel |
| **Vue.js** | 3.x | Frontend framework (Composition API) |
| **Inertia.js** | 2.x | SPA tanpa API — bridge Laravel ↔ Vue |
| **TailwindCSS** | 4.x | Utility-first CSS framework |
| **Flowbite** | 2.x | UI components berbasis Tailwind |
| **Font Awesome** | 6.x | Icon library |
| **Ziggy** | 2.x | Laravel `route()` helper untuk Vue |

---

## ✨ Fitur Utama

- **Download Progress Real-time** — progress bar update tanpa refresh via WebSocket
- **Race-condition Safe** — frontend subscribe channel *sebelum* job di-dispatch
- **WebSocket Fallback + Heartbeat** — disconnect → HTTP polling otomatis; heartbeat deteksi job crash
- **Background Queue** — generate file berjalan di background (database queue)
- **Multi-format** — export CSV atau TXT dengan jumlah row 100–5.000
- **Riwayat Download** — history download session

---

## 🔄 Cara Kerja Download Progress (Race-condition Safe)

### Step 1 — Klik "Mulai Download"

Frontend POST ke `/download/prepare` — backend generate UUID dan simpan metadata ke cache. Job **belum** dijalankan.

### Step 2 — Subscribe WebSocket Channel

Frontend subscribe ke channel `download.{id}` via Laravel Echo **sebelum** job di-dispatch. Ini mencegah race condition.

### Step 3 — Tunggu Konfirmasi Reverb

Menunggu event `pusher:subscription_succeeded` — konfirmasi dari Reverb bahwa channel sudah aktif dan siap menerima event.

### Step 4 — Dispatch Job ke Queue

Setelah channel aktif, frontend POST ke `/download/dispatch`. Backend ambil metadata dari cache lalu dispatch `ProcessDownload` job.

### Step 5 — Broadcast Event per Step

Job berjalan di background, broadcast `DownloadProgress` event setiap 10% selesai via Reverb WebSocket ke channel yang sudah aktif.

### Step 6 — Progress Bar Terupdate

Laravel Echo menerima event, Vue reactivity update progress bar real-time. Selesai 100% → tombol unduh file muncul.

### Kunci Anti Race Condition

```
POST /prepare → subscribe Echo → konfirmasi → POST /dispatch
```

Frontend subscribe channel **sebelum** job di-dispatch, jadi tidak ada event yang terlewat.

---

## 💓 WebSocket Fallback + Heartbeat

### Fallback: WebSocket → HTTP Polling

Jika WebSocket disconnect, frontend otomatis switch ke HTTP polling via `GET /download/status/{downloadId}` tiap 3 detik. Saat WebSocket reconnect, kembali ke WebSocket.

### Heartbeat: Deteksi Job Crash

Dua cache key terpisah:

| Cache Key | Frekuensi Update | Fungsi |
|-----------|-----------------|--------|
| `download_status_{id}` | Jarang (tiap step 10%) | Progress & status download |
| `download_heartbeat_{id}` | Sering (tiap N baris) | Tanda job masih hidup |

Jika `download_status` menunjukkan "processing" tapi `download_heartbeat` tidak update dalam 2+ menit → job dianggap crash → status diubah ke "error". Heartbeat memastikan job lambat tidak salah dianggap crash.

---

## ⚙️ Instalasi

### Prasyarat
- PHP 8.2+
- Composer
- Node.js + NPM
- MySQL

### Langkah Setup

```bash
# 1. Clone repo
git clone https://github.com/mascahyo1/digit.git
cd digit

# 2. Install dependencies
composer install
npm install

# 3. Copy & konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database & Reverb di .env
# DB_DATABASE=laravel_reverb
# DB_USERNAME=root
# DB_PASSWORD=
#
# REVERB_APP_ID=reverb-local
# REVERB_APP_KEY=<generate-random-key>
# REVERB_APP_SECRET=<generate-random-secret>
# REVERB_HOST=localhost
# REVERB_PORT=8080
# REVERB_SCHEME=http

# 5. Jalankan migration
php artisan migrate

# 6. Build assets
npm run build
```

### Menjalankan (butuh 3 terminal)

```bash
# Terminal 1 — Web server
php artisan serve

# Terminal 2 — Reverb WebSocket server
php artisan reverb:start --debug

# Terminal 3 — Queue worker
php artisan queue:work --verbose

# (Opsional) Terminal 4 — Vite HMR dev mode
npm run dev
```

Buka `http://localhost:8000` di browser.

---

## 📁 Struktur File Utama

```
app/
├── Events/
│   └── DownloadProgress.php     # Event broadcast via Reverb
├── Jobs/
│   └── ProcessDownload.php      # Background job generate file
└── Http/Controllers/
    └── DownloadController.php   # prepare, dispatch, download

resources/js/
├── Layouts/
│   └── AppLayout.vue            # Sidebar dark mode layout
└── Pages/
    ├── Welcome.vue              # Landing page + diagram flow
    └── Download.vue             # Download + progress bar real-time
```

---

## 📚 Konsep yang Dipelajari

- **Laravel Reverb** — WebSocket server & broadcasting
- **Laravel Echo** — subscribe channel dari frontend
- **Queue & Jobs** — background processing dengan heartbeat monitor
- **Inertia.js** — SPA tanpa REST API
- **Race Condition** — cara menghindarinya pada WebSocket (subscribe sebelum dispatch)
- **WebSocket Fallback** — graceful degradation ke HTTP polling saat disconnect
- **Heartbeat Pattern** — deteksi job crash via cache key terpisah (status vs heartbeat)

> 💡 **Penjelasan lengkap** semua library (Reverb, Echo, pusher-js, concurrently) tersedia di **[CONCEPTS.md](./CONCEPTS.md)**

---

## 📄 Lisensi

MIT License — bebas digunakan untuk belajar.
