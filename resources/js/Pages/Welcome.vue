<template>
    <Head title="Beranda" />
    <AppLayout title="Beranda">
        <!-- Hero -->
        <div class="max-w-4xl">
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full text-blue-400 text-xs font-medium mb-4">
                    <i class="fa-solid fa-rocket text-xs"></i>
                    Laravel + Reverb Learning Project
                </div>
                <h1 class="text-4xl font-bold text-white mb-3 leading-tight">
                    Real-time Download<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-violet-400">
                        dengan Laravel Reverb
                    </span>
                </h1>
                <p class="text-gray-400 text-lg leading-relaxed max-w-2xl">
                    Project belajar menggunakan stack modern: <strong class="text-white">Laravel 13</strong>,
                    <strong class="text-white">Vue 3</strong>, <strong class="text-white">Inertia.js</strong>,
                    dan <strong class="text-white">Laravel Reverb</strong> untuk WebSocket real-time.
                </p>
            </div>

            <!-- CTA Button -->
            <div class="flex gap-3 mb-12">
                <Link
                    :href="route('download')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-medium rounded-lg transition-all duration-200 shadow-lg shadow-blue-600/30 hover:shadow-blue-500/40 hover:-translate-y-0.5"
                >
                    <i class="fa-solid fa-download text-sm"></i>
                    Coba Download Progress
                </Link>
                <a
                    href="https://laravel.com/docs"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white font-medium rounded-lg transition-all duration-200 border border-gray-700"
                >
                    <i class="fa-solid fa-book text-sm"></i>
                    Dokumentasi
                </a>
            </div>

            <!-- Stack Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-10">
                <div v-for="tech in techStack" :key="tech.name"
                    class="bg-gray-900 border border-gray-800 rounded-xl p-4 hover:border-gray-700 transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                            :class="tech.iconBg">
                            <i :class="tech.icon"></i>
                        </div>
                        <span class="text-white font-medium text-sm">{{ tech.name }}</span>
                    </div>
                    <p class="text-gray-500 text-xs leading-relaxed">{{ tech.desc }}</p>
                    <div class="mt-2">
                        <span class="text-xs font-mono" :class="tech.versionColor">{{ tech.version }}</span>
                    </div>
                </div>
            </div>

            <!-- How it Works -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-white font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-diagram-project text-blue-400"></i>
                        Cara Kerja Download Progress
                    </h2>
                    <span class="text-xs px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/20 rounded-full">
                        <i class="fa-solid fa-shield-halved mr-1"></i>Race-condition safe
                    </span>
                </div>

                <!-- Steps: 2 kolom grid agar 6 step tidak terlalu panjang -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div v-for="(step, i) in steps" :key="i"
                        class="flex items-start gap-3 p-3 rounded-xl border transition-colors duration-200"
                        :class="step.highlight
                            ? 'bg-blue-600/10 border-blue-500/25'
                            : 'bg-gray-800/40 border-gray-700/50'">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5"
                            :class="step.highlight
                                ? 'bg-blue-600/30 border border-blue-500/40 text-blue-300'
                                : 'bg-gray-700 border border-gray-600 text-gray-400'">
                            {{ i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <i :class="step.icon + ' text-xs'"></i>
                                <p class="text-white text-sm font-medium">{{ step.title }}</p>
                            </div>
                            <p class="text-gray-500 text-xs leading-relaxed">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-4 pt-4 border-t border-gray-800 flex items-center gap-4 text-xs text-gray-600">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-blue-600/30 border border-blue-500/40 inline-block"></span>
                        Kunci anti race condition
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-gray-700 border border-gray-600 inline-block"></span>
                        Step normal
                    </span>
                </div>
            </div>

            <!-- Fitur Unggulan -->
            <div class="mt-6">
                <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-star text-yellow-400"></i>
                    Fitur & Konsep yang Dipelajari
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="feat in features" :key="feat.title"
                        class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-700 transition-all duration-200 hover:-translate-y-0.5">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="feat.iconBg">
                                <i :class="feat.icon + ' text-sm'"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-white text-sm font-semibold">{{ feat.title }}</p>
                                    <span v-if="feat.badge" class="text-xs px-1.5 py-0.5 rounded font-medium"
                                        :class="feat.badgeClass">
                                        {{ feat.badge }}
                                    </span>
                                </div>
                                <p class="text-gray-500 text-xs leading-relaxed">{{ feat.desc }}</p>
                                <p v-if="feat.detail" class="text-gray-600 text-xs mt-1.5 font-mono border-t border-gray-800 pt-1.5">
                                    {{ feat.detail }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 text-xs mt-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-book-open text-blue-500/50"></i>
                    Penjelasan lengkap dengan kode ada di
                    <a href="https://github.com/mascahyo1/digit/blob/master/CONCEPTS.md"
                        target="_blank" class="text-blue-400 hover:text-blue-300 transition-colors">CONCEPTS.md</a>
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const techStack = [
    { name: 'Laravel 13', version: 'v13.x', icon: 'fa-brands fa-laravel text-red-400', iconBg: 'bg-red-500/10', versionColor: 'text-red-400', desc: 'PHP framework backend dengan Eloquent ORM dan Queue.' },
    { name: 'Vue 3', version: 'v3.x', icon: 'fa-brands fa-vuejs text-green-400', iconBg: 'bg-green-500/10', versionColor: 'text-green-400', desc: 'Composition API, reaktif, performa tinggi.' },
    { name: 'Inertia.js', version: 'v2.x', icon: 'fa-solid fa-layer-group text-purple-400', iconBg: 'bg-purple-500/10', versionColor: 'text-purple-400', desc: 'SPA tanpa API—bridge antara Laravel dan Vue.' },
    { name: 'Laravel Reverb', version: 'v1.x', icon: 'fa-solid fa-tower-broadcast text-blue-400', iconBg: 'bg-blue-500/10', versionColor: 'text-blue-400', desc: 'WebSocket server resmi Laravel untuk real-time.' },
    { name: 'TailwindCSS', version: 'v4.x', icon: 'fa-solid fa-wand-magic-sparkles text-cyan-400', iconBg: 'bg-cyan-500/10', versionColor: 'text-cyan-400', desc: 'Utility-first CSS framework modern.' },
    { name: 'Flowbite', version: 'v2.x', icon: 'fa-solid fa-puzzle-piece text-indigo-400', iconBg: 'bg-indigo-500/10', versionColor: 'text-indigo-400', desc: 'UI component library berbasis Tailwind.' },
];

const steps = [
    {
        title: 'Klik "Mulai Download"',
        desc: 'Frontend POST ke /download/prepare — backend generate UUID dan simpan metadata ke cache. Job belum dijalankan.',
        icon: 'fa-solid fa-computer-mouse text-gray-400',
        highlight: false,
    },
    {
        title: 'Subscribe WebSocket Channel',
        desc: 'Frontend subscribe ke channel "download.{id}" via Laravel Echo sebelum job di-dispatch. Ini mencegah race condition.',
        icon: 'fa-solid fa-plug text-blue-400',
        highlight: true,
    },
    {
        title: 'Tunggu Konfirmasi Reverb',
        desc: 'Menunggu event "pusher:subscription_succeeded" — konfirmasi dari Reverb bahwa channel sudah aktif dan siap menerima event.',
        icon: 'fa-solid fa-circle-check text-blue-400',
        highlight: true,
    },
    {
        title: 'Dispatch Job ke Queue',
        desc: 'Setelah channel aktif, frontend POST ke /download/dispatch. Backend ambil metadata dari cache lalu dispatch ProcessDownload job.',
        icon: 'fa-solid fa-paper-plane text-gray-400',
        highlight: false,
    },
    {
        title: 'Broadcast Event per Step',
        desc: 'Job berjalan di background, broadcast DownloadProgress event setiap 10% selesai via Reverb WebSocket ke channel yang sudah aktif.',
        icon: 'fa-solid fa-tower-broadcast text-gray-400',
        highlight: false,
    },
    {
        title: 'Progress Bar Terupdate',
        desc: 'Laravel Echo menerima event, Vue reactivity update progress bar real-time. Selesai 100% → tombol unduh file muncul.',
        icon: 'fa-solid fa-chart-line text-gray-400',
        highlight: false,
    },
];

const features = [
    {
        title: 'Race-condition Safe',
        badge: 'Download',
        badgeClass: 'bg-blue-500/10 text-blue-400',
        icon: 'fa-solid fa-shield-halved text-blue-400',
        iconBg: 'bg-blue-500/10',
        desc: 'Frontend subscribe ke WebSocket channel SEBELUM job di-dispatch. Menggunakan dua endpoint: /prepare (dapat ID) lalu /dispatch (jalankan job).',
        detail: 'POST /prepare → subscribe Echo → konfirmasi → POST /dispatch',
    },
    {
        title: 'WebSocket Fallback ke HTTP Polling',
        badge: 'Resilient',
        badgeClass: 'bg-orange-500/10 text-orange-400',
        icon: 'fa-solid fa-rotate text-orange-400',
        iconBg: 'bg-orange-500/10',
        desc: 'Jika WebSocket tidak mengirim event > 12 detik, frontend otomatis beralih ke HTTP polling setiap 3 detik. Progress tidak pernah stuck. Balik ke WebSocket saat reconnect.',
        detail: 'Silence 12s → poll /download/status/{id} dari cache → reconnect → stop polling',
    },
    {
        title: 'Private Chat + Authentication',
        badge: 'Chat',
        badgeClass: 'bg-violet-500/10 text-violet-400',
        icon: 'fa-solid fa-lock text-violet-400',
        iconBg: 'bg-violet-500/10',
        desc: 'Chat 1-on-1 dengan Reverb private channel. Hanya dua user yang terlibat bisa subscribe. Auth via Laravel session + /broadcasting/auth endpoint.',
        detail: 'Echo.private("chat.{a}-{b}") → /broadcasting/auth → channel authorized',
    },
    {
        title: 'Real-time Notification Badge',
        badge: 'Live',
        badgeClass: 'bg-red-500/10 text-red-400',
        icon: 'fa-solid fa-bell text-red-400',
        iconBg: 'bg-red-500/10',
        desc: 'Badge merah di sidebar update real-time saat ada pesan private masuk — tanpa refresh halaman. Pakai channel notifikasi terpisah per user.',
        detail: 'Echo.private("notifications.{userId}") → badge++ di AppLayout',
    },
];
</script>
