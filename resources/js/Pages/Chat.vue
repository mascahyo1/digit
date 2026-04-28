<template>
    <Head title="Web Chat" />
    <AppLayout title="Web Chat — Real-time">

        <div class="max-w-3xl flex flex-col gap-4">

            <!-- Setup nama jika belum ada -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                <div v-if="!senderName" class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-user text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-white font-semibold">Siapa nama kamu?</h2>
                            <p class="text-gray-500 text-xs">Nama ini akan tampil di chat</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input
                            v-model="nameInput"
                            @keydown.enter="setName"
                            type="text"
                            placeholder="Masukkan nama kamu..."
                            maxlength="30"
                            class="flex-1 bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        />
                        <button
                            @click="setName"
                            :disabled="!nameInput.trim()"
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-xl text-sm font-medium transition-all duration-200"
                        >
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Chat UI (muncul setelah nama di-set) -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="senderName" class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden flex flex-col" style="height: 65vh">

                    <!-- Header Chat -->
                    <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900/90 backdrop-blur-sm flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: avatarColor }">
                                    {{ senderName.charAt(0).toUpperCase() }}
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-gray-900"></span>
                            </div>
                            <div>
                                <p class="text-white text-sm font-semibold">{{ senderName }}</p>
                                <p class="text-gray-500 text-xs flex items-center gap-1">
                                    <i class="fa-solid fa-satellite-dish text-blue-400 animate-pulse text-xs"></i>
                                    Terhubung via Reverb
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 bg-gray-800 px-2.5 py-1 rounded-full border border-gray-700">
                                <i class="fa-solid fa-users mr-1"></i>
                                channel: chat
                            </span>
                            <button @click="clearName" class="text-gray-600 hover:text-gray-400 transition-colors text-xs px-2 py-1 rounded-lg hover:bg-gray-800">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div ref="messagesContainer"
                        class="flex-1 overflow-y-auto px-5 py-4 space-y-3 scroll-smooth">

                        <!-- Empty State -->
                        <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                            <div class="w-14 h-14 rounded-2xl bg-gray-800 border border-gray-700 flex items-center justify-center mb-3">
                                <i class="fa-solid fa-comments text-gray-600 text-xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada pesan</p>
                            <p class="text-gray-600 text-xs mt-1">Jadilah yang pertama memulai obrolan!</p>
                        </div>

                        <!-- Pesan -->
                        <div v-for="msg in messages" :key="msg.id"
                            class="flex items-end gap-2.5"
                            :class="msg.sender === senderName ? 'flex-row-reverse' : 'flex-row'">

                            <!-- Avatar -->
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mb-1"
                                :style="{ backgroundColor: msg.avatar_color }">
                                {{ msg.sender.charAt(0).toUpperCase() }}
                            </div>

                            <!-- Bubble -->
                            <div class="max-w-xs lg:max-w-sm">
                                <!-- Nama pengirim (hanya untuk pesan orang lain) -->
                                <p v-if="msg.sender !== senderName" class="text-gray-500 text-xs mb-1 ml-1">{{ msg.sender }}</p>

                                <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed"
                                    :class="msg.sender === senderName
                                        ? 'bg-blue-600 text-white rounded-br-sm'
                                        : 'bg-gray-800 text-gray-100 rounded-bl-sm border border-gray-700'">
                                    {{ msg.body }}
                                </div>
                                <p class="text-gray-600 text-xs mt-1"
                                    :class="msg.sender === senderName ? 'text-right mr-1' : 'ml-1'">
                                    {{ msg.time }}
                                </p>
                            </div>
                        </div>

                        <!-- Typing indicator (animasi dots) -->
                        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100">
                            <div v-if="someoneTyping" class="flex items-end gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-ellipsis text-gray-400 text-xs"></i>
                                </div>
                                <div class="bg-gray-800 border border-gray-700 rounded-2xl rounded-bl-sm px-4 py-3">
                                    <div class="flex gap-1 items-center">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Input Area -->
                    <div class="px-4 py-3 border-t border-gray-800 bg-gray-900/50 flex-shrink-0">
                        <div class="flex items-end gap-2">
                            <div class="flex-1 bg-gray-800 border border-gray-700 rounded-xl focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-all">
                                <textarea
                                    v-model="messageInput"
                                    @keydown.enter.exact.prevent="sendMessage"
                                    @input="onTyping"
                                    placeholder="Ketik pesan... (Enter untuk kirim)"
                                    rows="1"
                                    maxlength="1000"
                                    class="w-full bg-transparent text-white placeholder-gray-500 text-sm px-4 py-3 focus:outline-none resize-none max-h-28"
                                    style="field-sizing: content;"
                                ></textarea>
                            </div>
                            <button
                                @click="sendMessage"
                                :disabled="!messageInput.trim() || sending"
                                class="w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center transition-all duration-200"
                                :class="messageInput.trim() && !sending
                                    ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/20 hover:-translate-y-0.5'
                                    : 'bg-gray-700 text-gray-500 cursor-not-allowed'"
                            >
                                <i v-if="!sending" class="fa-solid fa-paper-plane text-sm"></i>
                                <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-gray-700 text-xs mt-1.5 px-1">
                            <kbd class="text-gray-600">Enter</kbd> kirim &nbsp;·&nbsp; <kbd class="text-gray-600">Shift+Enter</kbd> baris baru
                        </p>
                    </div>
                </div>
            </Transition>

            <!-- Info box -->
            <div v-if="senderName" class="bg-gray-900/50 border border-gray-800 rounded-xl p-4 text-xs text-gray-600 flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-blue-500/60 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="text-gray-500 font-medium mb-0.5">Cara kerja chat ini:</p>
                    Pesan dikirim ke Laravel → disimpan ke database → di-broadcast via Reverb WebSocket → semua user yang buka halaman ini terima pesan real-time tanpa refresh.
                    Coba buka tab browser kedua dan chat dari sana!
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

// ── Props dari Inertia (50 pesan terakhir dari database) ─────────────────────
const props = defineProps({
    messages: { type: Array, default: () => [] },
});

// ── State ────────────────────────────────────────────────────────────────────
const messages    = ref([...props.messages]);
const messageInput = ref('');
const nameInput    = ref('');
const sending      = ref(false);
const messagesContainer = ref(null);
const someoneTyping = ref(false);
let typingTimer = null;

// Warna avatar — 10 pilihan warna yang bagus di dark mode
const COLORS = [
    '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b',
    '#10b981', '#06b6d4', '#f97316', '#6366f1',
    '#14b8a6', '#84cc16',
];

// ── Ambil / set nama + warna dari localStorage ───────────────────────────────
const senderName  = ref(localStorage.getItem('chat_name') || '');
const avatarColor = ref(localStorage.getItem('chat_color') || '');

function setName() {
    if (!nameInput.value.trim()) return;
    const color = COLORS[Math.floor(Math.random() * COLORS.length)];
    senderName.value  = nameInput.value.trim();
    avatarColor.value = color;
    localStorage.setItem('chat_name', senderName.value);
    localStorage.setItem('chat_color', avatarColor.value);
    nextTick(() => scrollToBottom());
}

function clearName() {
    senderName.value = '';
    avatarColor.value = '';
    localStorage.removeItem('chat_name');
    localStorage.removeItem('chat_color');
}

// ── Scroll ke bawah ──────────────────────────────────────────────────────────
function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

// ── Kirim pesan ──────────────────────────────────────────────────────────────
async function sendMessage() {
    const body = messageInput.value.trim();
    if (!body || sending.value || !senderName.value) return;

    sending.value = true;

    // Tambah ke UI langsung (optimistic update) agar terasa cepat
    const tempMsg = {
        id: 'temp-' + Date.now(),
        sender: senderName.value,
        avatar_color: avatarColor.value,
        body,
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    };
    messages.value.push(tempMsg);
    messageInput.value = '';
    await nextTick();
    scrollToBottom();

    try {
        await axios.post('/chat/send', {
            sender:       senderName.value,
            avatar_color: avatarColor.value,
            body,
        });
    } catch (err) {
        // Hapus optimistic message jika gagal
        messages.value = messages.value.filter(m => m.id !== tempMsg.id);
        console.error('Gagal kirim pesan:', err);
    } finally {
        sending.value = false;
    }
}

// ── Typing indicator ─────────────────────────────────────────────────────────
function onTyping() {
    // Broadcast bahwa kita sedang mengetik (via Reverb)
    // Untuk kesederhanaan, kita pakai timeout lokal saja
    clearTimeout(typingTimer);
}

// ── Subscribe ke channel Reverb ──────────────────────────────────────────────
let echoChannel = null;

function subscribeToChat() {
    echoChannel = window.Echo.channel('chat')
        .listen('.message.sent', (payload) => {
            // Pesan dari orang lain masuk via WebSocket
            if (payload.sender !== senderName.value) {
                messages.value.push(payload);
                nextTick(() => scrollToBottom());
            }
        });
}

// ── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => {
    subscribeToChat();
    nextTick(() => scrollToBottom());
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leave('chat');
    }
    clearTimeout(typingTimer);
});

// Auto scroll saat pesan baru masuk
watch(() => messages.value.length, () => {
    nextTick(() => scrollToBottom());
});
</script>
