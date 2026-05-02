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
- **Background Queue** — generate file berjalan di background (database queue)
- **Multi-format** — export CSV atau TXT dengan jumlah row 100–5.000
- **Riwayat Download** — history download session

---

## 🔄 Flow Arsitektur (Race-condition Safe)

```
1. User klik "Mulai Download"
   └─ POST /download/prepare → backend generate UUID, simpan ke cache

2. Frontend subscribe Echo channel "download.{id}"
   └─ SEBELUM job di-dispatch (kunci utama!)

3. Tunggu konfirmasi "pusher:subscription_succeeded" dari Reverb
   └─ Memastikan WebSocket channel benar-benar aktif

4. POST /download/dispatch → backend ambil metadata dari cache, dispatch job

5. Queue worker proses job → broadcast DownloadProgress event tiap 10%

6. Laravel Echo menerima event → Vue update progress bar real-time

7. Selesai 100% → tombol unduh file muncul
```

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
- **Queue & Jobs** — background processing
- **Inertia.js** — SPA tanpa REST API
- **Race Condition** — cara menghindarinya pada WebSocket

> 💡 **Penjelasan lengkap** semua library (Reverb, Echo, pusher-js, concurrently) tersedia di **[CONCEPTS.md](./CONCEPTS.md)**

---

## 📄 Lisensi

MIT License — bebas digunakan untuk belajar.
