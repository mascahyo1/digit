<template>
    <Head title="Download Progress" />
    <AppLayout title="Download dengan Progress Real-time">

        <div class="max-w-3xl space-y-6">

            <!-- Info Banner -->
            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 text-sm flex-shrink-0"></i>
                <div class="text-sm text-blue-300">
                    <p class="font-medium mb-0.5">Cara menggunakan:</p>
                    <p class="text-blue-400/80">Pilih format file dan jumlah data, klik <strong>Mulai Download</strong>.
                    Progress akan terupdate secara real-time via <strong>Laravel Reverb WebSocket</strong> tanpa perlu refresh halaman.</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h2 class="text-white font-semibold mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-gear text-gray-400 text-sm"></i>
                    Konfigurasi Download
                </h2>

                <div class="space-y-5">
                    <!-- File Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fa-solid fa-file text-gray-400 mr-1.5"></i>
                            Format File
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="type in fileTypes"
                                :key="type.value"
                                @click="form.file_type = type.value"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all duration-200 text-sm font-medium"
                                :class="form.file_type === type.value
                                    ? 'bg-blue-600/20 border-blue-500/50 text-blue-300'
                                    : 'bg-gray-800 border-gray-700 text-gray-400 hover:border-gray-600 hover:text-gray-200'"
                            >
                                <i :class="type.icon + ' text-base'"></i>
                                <div class="text-left">
                                    <p class="font-medium leading-tight">{{ type.label }}</p>
                                    <p class="text-xs opacity-60 mt-0.5">{{ type.desc }}</p>
                                </div>
                                <i v-if="form.file_type === type.value" class="fa-solid fa-circle-check text-blue-400 ml-auto text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Row Count -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fa-solid fa-table-rows text-gray-400 mr-1.5"></i>
                            Jumlah Data: <span class="text-blue-400 font-mono">{{ form.total_rows.toLocaleString() }}</span> rows
                        </label>
                        <input
                            v-model="form.total_rows"
                            type="range"
                            min="100"
                            max="5000"
                            step="100"
                            class="w-full h-2 bg-gray-700 rounded-full appearance-none cursor-pointer accent-blue-500"
                        />
                        <div class="flex justify-between text-xs text-gray-600 mt-1">
                            <span>100</span>
                            <span>5.000</span>
                        </div>
                    </div>

                    <!-- Quick Presets -->
                    <div class="flex gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 mr-1 self-center">Preset:</span>
                        <button
                            v-for="preset in presets"
                            :key="preset"
                            @click="form.total_rows = preset"
                            class="px-2.5 py-1 text-xs rounded-lg transition-colors duration-150"
                            :class="form.total_rows === preset
                                ? 'bg-blue-600/30 text-blue-300 border border-blue-500/40'
                                : 'bg-gray-800 text-gray-400 border border-gray-700 hover:border-gray-500'"
                        >
                            {{ preset.toLocaleString() }} rows
                        </button>
                    </div>

                    <!-- Submit -->
                    <button
                        @click="startDownload"
                        :disabled="isDownloading"
                        class="w-full flex items-center justify-center gap-2.5 px-5 py-3 rounded-xl font-semibold text-sm transition-all duration-200"
                        :class="isDownloading
                            ? 'bg-gray-700 text-gray-400 cursor-not-allowed'
                            : 'bg-gradient-to-r from-blue-600 to-violet-600 hover:from-blue-500 hover:to-violet-500 text-white shadow-lg shadow-blue-600/20 hover:shadow-blue-500/30 hover:-translate-y-0.5'"
                    >
                        <i v-if="!isDownloading" class="fa-solid fa-download"></i>
                        <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ isDownloading ? 'Sedang Memproses...' : 'Mulai Download' }}
                    </button>
                </div>
            </div>

            <!-- Progress Card -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="currentDownload" class="bg-gray-900 border rounded-2xl p-6 transition-all duration-300"
                    :class="{
                        'border-blue-500/30': currentDownload.status === 'processing',
                        'border-yellow-500/30': currentDownload.status === 'saving',
                        'border-green-500/30': currentDownload.status === 'completed',
                        'border-red-500/30': currentDownload.status === 'error',
                    }">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-semibold flex items-center gap-2 text-sm">
                            <i class="fa-solid fa-satellite-dish text-sm"
                                :class="{
                                    'text-blue-400 animate-pulse': currentDownload.status !== 'completed',
                                    'text-green-400': currentDownload.status === 'completed',
                                }"></i>
                            Progress Real-time via Reverb
                        </h3>

                        <div class="flex items-center gap-2">
                            <!-- Badge mode koneksi: WebSocket atau Polling fallback -->
                            <span v-if="isDownloading" class="px-2 py-0.5 rounded-full text-xs font-medium border flex items-center gap-1"
                                :class="connectionMode === 'websocket'
                                    ? 'bg-green-500/10 text-green-400 border-green-500/20'
                                    : 'bg-orange-500/10 text-orange-400 border-orange-500/20'">
                                <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                                    :class="connectionMode === 'websocket' ? 'bg-green-400' : 'bg-orange-400'"></span>
                                {{ connectionMode === 'websocket' ? 'WebSocket' : 'HTTP Polling' }}
                            </span>

                            <!-- Status Badge -->
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                :class="{
                                    'bg-blue-500/10 text-blue-400 border-blue-500/20': currentDownload.status === 'processing' || currentDownload.status === 'connecting',
                                    'bg-yellow-500/10 text-yellow-400 border-yellow-500/20': currentDownload.status === 'saving',
                                    'bg-green-500/10 text-green-400 border-green-500/20': currentDownload.status === 'completed',
                                    'bg-red-500/10 text-red-400 border-red-500/20': currentDownload.status === 'error',
                                }">
                                <span v-if="currentDownload.status === 'processing' || currentDownload.status === 'connecting'">
                                    <i class="fa-solid fa-spinner fa-spin mr-1 text-xs"></i>Memproses
                                </span>
                                <span v-else-if="currentDownload.status === 'saving'">
                                    <i class="fa-solid fa-floppy-disk mr-1 text-xs"></i>Menyimpan
                                </span>
                                <span v-else-if="currentDownload.status === 'completed'">
                                    <i class="fa-solid fa-circle-check mr-1 text-xs"></i>Selesai
                                </span>
                                <span v-else>
                                    <i class="fa-solid fa-circle-xmark mr-1 text-xs"></i>Error
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex justify-between text-xs text-gray-400 mb-1.5">
                            <span>{{ currentDownload.message || 'Menunggu...' }}</span>
                            <span class="font-mono font-bold" :class="currentDownload.status === 'completed' ? 'text-green-400' : 'text-blue-400'">
                                {{ currentDownload.progress }}%
                            </span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-800 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                                :class="{
                                    'bg-gradient-to-r from-blue-600 to-blue-400': currentDownload.status === 'processing',
                                    'bg-gradient-to-r from-yellow-500 to-amber-400': currentDownload.status === 'saving',
                                    'bg-gradient-to-r from-green-500 to-emerald-400': currentDownload.status === 'completed',
                                    'bg-red-500': currentDownload.status === 'error',
                                }"
                                :style="{ width: currentDownload.progress + '%' }"
                            >
                                <!-- Shimmer effect -->
                                <div v-if="currentDownload.status !== 'completed'" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-[shimmer_1.5s_infinite]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Download ID (untuk debug/belajar) -->
                    <div class="mt-3 pt-3 border-t border-gray-800">
                        <p class="text-xs text-gray-600">
                            <i class="fa-solid fa-fingerprint mr-1"></i>
                            Channel: <span class="font-mono text-gray-500">download.{{ currentDownload.downloadId }}</span>
                        </p>
                    </div>

                    <!-- Download Button (saat selesai) -->
                    <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                        <div v-if="currentDownload.status === 'completed' && currentDownload.fileName" class="mt-4">
                            <a
                                :href="`/download/file/${currentDownload.fileName}`"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-500 text-white rounded-xl font-semibold text-sm transition-all duration-200 shadow-lg shadow-green-600/20"
                                download
                            >
                                <i class="fa-solid fa-file-arrow-down"></i>
                                Unduh File: {{ currentDownload.fileName }}
                            </a>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <!-- History -->
            <div v-if="history.length > 0" class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <h3 class="text-white font-semibold mb-4 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                    Riwayat Download
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="item in history"
                        :key="item.downloadId"
                        class="flex items-center gap-3 px-3 py-2.5 bg-gray-800/50 rounded-lg border border-gray-700/50"
                    >
                        <i class="fa-solid fa-circle-check text-green-400 text-sm flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-white text-sm font-medium truncate">{{ item.fileName }}</p>
                            <p class="text-gray-500 text-xs">{{ item.totalRows?.toLocaleString() }} rows • {{ item.fileType?.toUpperCase() }}</p>
                        </div>
                        <a
                            :href="`/download/file/${item.fileName}`"
                            class="flex-shrink-0 px-2.5 py-1 bg-green-600/20 text-green-400 border border-green-500/20 rounded-lg text-xs hover:bg-green-600/30 transition-colors"
                            download
                        >
                            <i class="fa-solid fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = ref({
    file_type: 'csv',
    total_rows: 1000,
});

const fileTypes = [
    { value: 'csv', label: 'CSV', icon: 'fa-solid fa-file-csv text-green-400', desc: 'Comma-separated values' },
    { value: 'txt', label: 'TXT', icon: 'fa-solid fa-file-lines text-blue-400', desc: 'Plain text format' },
];

const presets         = [100, 500, 1000, 2000, 5000];
const isDownloading   = ref(false);
const currentDownload = ref(null);
const history         = ref([]);
const connectionMode  = ref('websocket'); // 'websocket' | 'polling' | 'reconnecting'

let echoChannel    = null;
let silenceTimer   = null; // timer deteksi "tidak ada event"
let pollingTimer   = null; // interval HTTP polling

// ── Konstanta ─────────────────────────────────────────────────────────────────
const SILENCE_TIMEOUT_MS = 12_000; // 12 detik tanpa event → switch ke polling
const POLLING_INTERVAL_MS = 3_000; // poll setiap 3 detik

// ── Fungsi utama ──────────────────────────────────────────────────────────────

function applyPayload(payload) {
    currentDownload.value = {
        ...currentDownload.value,
        progress: payload.progress,
        status:   payload.status,
        message:  payload.message,
        fileName: payload.file_name ?? payload.file_name ?? currentDownload.value?.fileName,
    };

    if (payload.status === 'completed' || payload.status === 'error') {
        finishDownload(payload.status === 'completed');
    }
}

function finishDownload(success = true) {
    isDownloading.value = false;
    stopSilenceTimer();
    stopPolling();

    if (success && echoChannel && currentDownload.value) {
        history.value.unshift({ ...currentDownload.value });
        window.Echo.leave(`download.${currentDownload.value.downloadId}`);
        echoChannel = null;
    }
}

// ── Silence timer: reset setiap kali event WebSocket masuk ───────────────────
// Jika tidak ada event dalam SILENCE_TIMEOUT_MS, switch ke polling.

function resetSilenceTimer(downloadId) {
    clearTimeout(silenceTimer);
    silenceTimer = setTimeout(() => {
        if (!isDownloading.value) return;
        // Hanya switch ke polling kalau belum polling
        if (connectionMode.value === 'websocket') {
            connectionMode.value = 'polling';
            startPolling(downloadId);
        }
    }, SILENCE_TIMEOUT_MS);
}

function stopSilenceTimer() {
    clearTimeout(silenceTimer);
}

// ── HTTP Polling fallback ─────────────────────────────────────────────────────
function startPolling(downloadId) {
    if (pollingTimer) return; // sudah berjalan

    pollingTimer = setInterval(async () => {
        try {
            const { data } = await axios.get(`/download/status/${downloadId}`);
            applyPayload(data);

            // Kalau WebSocket tiba-tiba reconnect di tengah polling, hentikan polling
            if (connectionMode.value === 'websocket') {
                stopPolling();
            }
        } catch (err) {
            // Status not found (belum dispatch atau sudah expired) — lanjut polling
            if (err.response?.status !== 404) {
                console.error('Polling error:', err);
            }
        }
    }, POLLING_INTERVAL_MS);
}

function stopPolling() {
    clearInterval(pollingTimer);
    pollingTimer = null;
}

// ── Main: start download ──────────────────────────────────────────────────────
async function startDownload() {
    if (isDownloading.value) return;

    // Cleanup download sebelumnya
    if (echoChannel && currentDownload.value) {
        window.Echo.leave(`download.${currentDownload.value.downloadId}`);
        echoChannel = null;
    }
    stopSilenceTimer();
    stopPolling();

    isDownloading.value  = true;
    connectionMode.value = 'websocket';
    currentDownload.value = null;

    try {
        // STEP 1: Minta download ID
        const { data } = await axios.post('/download/prepare', {
            file_type: form.value.file_type,
            total_rows: form.value.total_rows,
        });

        const downloadId = data.download_id;

        currentDownload.value = {
            downloadId,
            progress: 0,
            status: 'connecting',
            message: 'Menghubungkan ke Reverb WebSocket...',
            fileName: null,
            totalRows: form.value.total_rows,
            fileType: form.value.file_type,
        };

        // STEP 2: Subscribe Echo channel SEBELUM dispatch
        await new Promise((resolve) => {
            echoChannel = window.Echo
                .channel(`download.${downloadId}`)
                .listen('.progress.updated', (payload) => {
                    // Kalau sedang polling, kembali ke WebSocket mode
                    if (connectionMode.value === 'polling') {
                        connectionMode.value = 'websocket';
                        stopPolling();
                    }
                    // Reset timer setiap kali event masuk
                    resetSilenceTimer(downloadId);
                    applyPayload(payload);
                });

            // Deteksi reconnect Pusher → kalau sebelumnya polling, switch kembali
            window.Echo.connector.pusher.connection.bind('connected', () => {
                if (connectionMode.value === 'polling' && isDownloading.value) {
                    connectionMode.value = 'websocket';
                    stopPolling();
                    resetSilenceTimer(downloadId);
                }
            });

            echoChannel.pusher.channel(`download.${downloadId}`)
                ?.bind('pusher:subscription_succeeded', () => resolve())
                ?? resolve();

            setTimeout(resolve, 5000);
        });

        currentDownload.value.message = 'Terhubung! Memulai proses generate file...';
        currentDownload.value.status  = 'processing';

        // STEP 3: Mulai silence timer, lalu dispatch job
        resetSilenceTimer(downloadId);
        await axios.post('/download/dispatch', { download_id: downloadId });

    } catch (err) {
        isDownloading.value = false;
        stopSilenceTimer();
        stopPolling();
        if (currentDownload.value) {
            currentDownload.value.status  = 'error';
            currentDownload.value.message = 'Terjadi kesalahan: ' + (err.response?.data?.message ?? err.message);
        }
        console.error('Error:', err);
    }
}

onUnmounted(() => {
    if (echoChannel && currentDownload.value) {
        window.Echo.leave(`download.${currentDownload.value.downloadId}`);
    }
    stopSilenceTimer();
    stopPolling();
});
</script>

<style scoped>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
</style>
