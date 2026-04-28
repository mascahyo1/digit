<template>
    <Head title="Web Chat" />
    <AppLayout title="Web Chat — Real-time">
        <div class="max-w-3xl flex flex-col gap-4">

            <!-- Chat UI -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden flex flex-col" style="height: 65vh">

                <!-- Header Chat -->
                <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900/90 backdrop-blur-sm flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                :style="{ backgroundColor: myColor }">
                                {{ authUser.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-gray-900"></span>
                        </div>
                        <div>
                            <p class="text-white text-sm font-semibold">{{ authUser.name }}</p>
                            <p class="text-gray-500 text-xs flex items-center gap-1">
                                <i class="fa-solid fa-satellite-dish text-blue-400 animate-pulse text-xs"></i>
                                Terhubung via Reverb
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 bg-gray-800 px-2.5 py-1 rounded-full border border-gray-700">
                            <i class="fa-solid fa-users mr-1"></i>
                            channel: chat (public)
                        </span>
                    </div>
                </div>

                <!-- Online Users Bar -->
                <div v-if="onlineUsers.length > 0" class="px-5 py-2.5 bg-gray-900/50 border-b border-gray-800 flex items-center gap-2 overflow-x-auto flex-shrink-0">
                    <p class="text-xs text-gray-500 mr-2 flex-shrink-0">
                        <i class="fa-solid fa-circle text-[8px] text-green-400 mr-1"></i>
                        Online ({{ onlineUsers.length }}):
                    </p>
                    <div class="flex items-center gap-1.5">
                        <div v-for="user in onlineUsers" :key="user.id" class="relative group cursor-default" :title="user.name">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold"
                                :style="{ backgroundColor: COLORS[user.id % COLORS.length] }">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-green-400 rounded-full border border-gray-900"></span>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 scroll-smooth">

                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-800 border border-gray-700 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-comments text-gray-600 text-xl"></i>
                        </div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada pesan</p>
                        <p class="text-gray-600 text-xs mt-1">Jadilah yang pertama memulai obrolan!</p>
                    </div>

                    <div v-for="msg in messages" :key="msg.id"
                        class="flex items-end gap-2.5"
                        :class="msg.sender === authUser.name ? 'flex-row-reverse' : 'flex-row'">

                        <div class="relative flex-shrink-0 mb-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                :style="{ backgroundColor: msg.avatar_color }">
                                {{ msg.sender.charAt(0).toUpperCase() }}
                            </div>
                            <span v-if="onlineUsers.some(u => u.name === msg.sender)" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-gray-900" title="Online"></span>
                        </div>

                        <div class="max-w-xs lg:max-w-sm">
                            <p v-if="msg.sender !== authUser.name" class="text-gray-500 text-xs mb-1 ml-1">{{ msg.sender }}</p>
                            <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed"
                                :class="msg.sender === authUser.name
                                    ? 'bg-blue-600 text-white rounded-br-sm'
                                    : 'bg-gray-800 text-gray-100 rounded-bl-sm border border-gray-700'">
                                {{ msg.body }}
                            </div>
                            <p class="text-gray-600 text-xs mt-1"
                                :class="msg.sender === authUser.name ? 'text-right mr-1' : 'ml-1'">
                                {{ msg.time }}
                            </p>
                        </div>
                    </div>

                    <!-- Typing indicator -->
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

            <!-- Info box -->
            <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-4 text-xs text-gray-600 flex items-start gap-3">
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
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    messages: { type: Array, default: () => [] },
});

const page     = usePage();
const authUser = page.props.auth.user;

// Warna avatar deterministik berdasarkan user ID — konsisten tiap session
const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f97316', '#6366f1'];
const myColor = computed(() => COLORS[authUser.id % COLORS.length]);

const messages          = ref([...props.messages]);
const messageInput      = ref('');
const sending           = ref(false);
const messagesContainer = ref(null);
const someoneTyping     = ref(false);
const onlineUsers       = ref([]);

function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

// ── Kirim pesan ───────────────────────────────────────────────────────────────
async function sendMessage() {
    const body = messageInput.value.trim();
    if (!body || sending.value) return;

    sending.value = true;

    const tempMsg = {
        id:           'temp-' + Date.now(),
        sender:       authUser.name,
        avatar_color: myColor.value,
        body,
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    };
    messages.value.push(tempMsg);
    messageInput.value = '';
    await nextTick();
    scrollToBottom();

    try {
        const { data } = await axios.post(route('chat.send'), { body });
        const idx = messages.value.findIndex(m => m.id === tempMsg.id);
        if (idx !== -1) messages.value[idx] = data.message;
    } catch (err) {
        messages.value = messages.value.filter(m => m.id !== tempMsg.id);
        console.error('Gagal kirim pesan:', err);
    } finally {
        sending.value = false;
    }
}

// ── Heartbeat — ping setiap 30 detik agar server tahu kita online ────────────
// TTL cache di server: 70 detik. Kalau tidak ada heartbeat > 70 detik → offline.
let heartbeatTimer = null;

function sendHeartbeat() {
    axios.post(route('chat.heartbeat')).then(({ data }) => {
        if (data.online_users) {
            onlineUsers.value = data.online_users;
        }
    }).catch(() => {});
}

// ── WebSocket ─────────────────────────────────────────────────────────────────
let echoChannel = null;

onMounted(() => {
    scrollToBottom();

    echoChannel = window.Echo.channel('chat')
        .listen('.message.sent', (payload) => {
            if (payload.sender !== authUser.name) {
                messages.value.push(payload);
                nextTick(() => scrollToBottom());
            }
        });

    // Kirim heartbeat segera + setiap 30 detik
    sendHeartbeat();
    heartbeatTimer = setInterval(sendHeartbeat, 30_000);
});

onUnmounted(() => {
    if (echoChannel) window.Echo.leave('chat');
    clearInterval(heartbeatTimer);
});

watch(() => messages.value.length, () => {
    nextTick(() => scrollToBottom());
});
</script>
