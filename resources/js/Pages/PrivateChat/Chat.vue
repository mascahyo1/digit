<template>
    <Head :title="`Chat dengan ${chatWith.name}`" />
    <AppLayout :title="`💬 ${chatWith.name}`">
        <div class="max-w-3xl flex flex-col gap-4">

            <!-- Back button -->
            <div>
                <Link :href="route('private-chat.users')"
                    class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Kembali ke daftar user
                </Link>
            </div>

            <!-- Chat UI -->
            <div class="bg-gray-900 border border-violet-500/20 rounded-2xl overflow-hidden flex flex-col" style="height: 65vh">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900/90 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                :style="{ backgroundColor: getColor(chatWith.id) }">
                                {{ chatWith.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-gray-900"
                                :class="wsState === 'connected' ? 'bg-green-400' : 'bg-yellow-400 animate-pulse'"></span>
                        </div>
                        <div>
                            <p class="text-white text-sm font-semibold">{{ chatWith.name }}</p>
                            <p class="text-xs flex items-center gap-1"
                                :class="wsState === 'connected' ? 'text-gray-500' : 'text-yellow-400'">
                                <i class="fa-solid fa-lock text-violet-400 text-xs"></i>
                                <span v-if="wsState === 'connected'">Private — {{ channelName }}</span>
                                <span v-else-if="wsState === 'connecting'">
                                    <i class="fa-solid fa-circle-notch fa-spin mr-0.5"></i>Reconnecting...
                                </span>
                                <span v-else>WebSocket terputus</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Badge status koneksi -->
                        <span class="text-xs px-2 py-0.5 rounded-full border flex items-center gap-1.5"
                            :class="wsState === 'connected'
                                ? 'bg-green-500/10 text-green-400 border-green-500/20'
                                : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="wsState === 'connected' ? 'bg-green-400' : 'bg-yellow-400 animate-pulse'"></span>
                            {{ wsState === 'connected' ? 'WebSocket' : 'Reconnecting' }}
                        </span>
                        <span class="text-xs px-2.5 py-1 bg-violet-500/10 text-violet-400 border border-violet-500/20 rounded-full">
                            <i class="fa-solid fa-shield-halved mr-1"></i>Private
                        </span>
                    </div>
                </div>

                <!-- Reconnecting banner -->
                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="wsState !== 'connected'" class="px-4 py-2 bg-yellow-500/10 border-b border-yellow-500/20 flex items-center gap-2 flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-yellow-400 text-xs"></i>
                        <p class="text-yellow-400 text-xs">
                            Koneksi WebSocket terputus.
                            <span v-if="wsState === 'connecting'">Sedang mencoba reconnect...</span>
                            <span v-else>Pesan yang dikirim lawan tidak akan muncul sampai koneksi pulih.</span>
                        </p>
                        <span v-if="missedCount > 0" class="ml-auto text-xs bg-yellow-500/20 text-yellow-300 px-2 py-0.5 rounded-full">
                            {{ missedCount }} pesan baru saat offline
                        </span>
                    </div>
                </Transition>

                <!-- Messages -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 scroll-smooth">

                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-800 border border-gray-700 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-lock text-gray-600 text-xl"></i>
                        </div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada pesan</p>
                        <p class="text-gray-600 text-xs mt-1">Mulai percakapan private dengan {{ chatWith.name }}</p>
                    </div>

                    <div v-for="msg in messages" :key="msg.id"
                        class="flex items-end gap-2.5"
                        :class="msg.sender_id === authUser.id ? 'flex-row-reverse' : 'flex-row'">

                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mb-1"
                            :style="{ backgroundColor: getColor(msg.sender_id) }">
                            {{ msg.sender_id === authUser.id ? authUser.name.charAt(0) : chatWith.name.charAt(0) }}
                        </div>

                        <div class="max-w-xs lg:max-w-sm">
                            <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed"
                                :class="[
                                    msg.sender_id === authUser.id
                                        ? 'bg-violet-600 text-white rounded-br-sm'
                                        : 'bg-gray-800 text-gray-100 rounded-bl-sm border border-gray-700',
                                    msg._missed ? 'ring-1 ring-yellow-400/40' : ''
                                ]">
                                {{ msg.body }}
                            </div>
                            <p class="text-gray-600 text-xs mt-1 flex items-center gap-1"
                                :class="msg.sender_id === authUser.id ? 'justify-end mr-1' : 'ml-1'">
                                <span v-if="msg._missed" class="text-yellow-500/70">
                                    <i class="fa-solid fa-wifi-slash text-xs"></i>
                                </span>
                                {{ msg.time }}
                                <i v-if="msg.sender_id === authUser.id" class="fa-solid fa-check-double ml-0.5"
                                    :class="msg.is_read ? 'text-violet-400' : 'text-gray-600'"></i>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="px-4 py-3 border-t border-gray-800 bg-gray-900/50 flex-shrink-0">
                    <div class="flex items-end gap-2">
                        <div class="flex-1 bg-gray-800 border border-gray-700 rounded-xl focus-within:border-violet-500 focus-within:ring-1 focus-within:ring-violet-500 transition-all"
                            :class="wsState !== 'connected' ? 'border-yellow-500/30' : ''">
                            <textarea
                                v-model="messageInput"
                                @keydown.enter.exact.prevent="sendMessage"
                                :placeholder="wsState === 'connected' ? 'Pesan private... (Enter untuk kirim)' : 'Pesan tetap bisa dikirim, akan terkirim saat reconnect...'"
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
                                ? 'bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/20 hover:-translate-y-0.5'
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
                        <i class="fa-solid fa-lock text-violet-500/50 mr-1"></i>
                        Hanya kamu dan {{ chatWith.name }} yang bisa melihat pesan ini
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    chatWith:    { type: Object, required: true },
    messages:    { type: Array, default: () => [] },
    channelName: { type: String, required: true },
});

const page     = usePage();
const authUser = page.props.auth.user;

const messages           = ref([...props.messages]);
const messageInput       = ref('');
const sending            = ref(false);
const messagesContainer  = ref(null);
const wsState            = ref('connected'); // 'connected' | 'connecting' | 'disconnected'
const missedCount        = ref(0);

// ID pesan terakhir yang diketahui — dipakai untuk catch-up saat reconnect
const lastMessageId = computed(() => {
    const real = messages.value.filter(m => typeof m.id === 'number');
    return real.length > 0 ? Math.max(...real.map(m => m.id)) : 0;
});

const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f97316', '#6366f1'];
function getColor(id) { return COLORS[id % COLORS.length]; }

function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

// ── Catch-up: ambil pesan yang missed selama disconnect ──────────────────────
async function catchUpMessages() {
    if (lastMessageId.value === 0) return;

    try {
        const { data } = await axios.get(
            route('private-chat.messages-since', props.chatWith.id),
            { params: { since: lastMessageId.value } }
        );

        if (data.messages.length > 0) {
            // Tandai pesan catch-up dengan flag _missed agar tampil berbeda di UI
            const catchUpMsgs = data.messages.map(m => ({ ...m, _missed: true }));

            // Hindari duplikat (misal pesan yang sudah masuk via WebSocket sebelum putus)
            const existingIds = new Set(messages.value.map(m => m.id));
            const newMsgs = catchUpMsgs.filter(m => !existingIds.has(m.id));

            if (newMsgs.length > 0) {
                missedCount.value = newMsgs.length;
                messages.value.push(...newMsgs);
                await nextTick();
                scrollToBottom();

                // Reset badge setelah 4 detik
                setTimeout(() => { missedCount.value = 0; }, 4000);
            }
        }
    } catch (err) {
        console.error('Catch-up gagal:', err);
    }
}

// ── Kirim pesan ───────────────────────────────────────────────────────────────
async function sendMessage() {
    const body = messageInput.value.trim();
    if (!body || sending.value) return;

    sending.value = true;

    // Optimistic update
    const tempMsg = {
        id:          'temp-' + Date.now(),
        sender_id:   authUser.id,
        receiver_id: props.chatWith.id,
        body,
        is_read:     false,
        time:        new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    };
    messages.value.push(tempMsg);
    messageInput.value = '';
    await nextTick();
    scrollToBottom();

    try {
        const { data } = await axios.post(route('private-chat.send', props.chatWith.id), { body });
        // Ganti temp message dengan yang dari server (ID yang benar)
        const idx = messages.value.findIndex(m => m.id === tempMsg.id);
        if (idx !== -1) messages.value[idx] = data.message;
    } catch (err) {
        messages.value = messages.value.filter(m => m.id !== tempMsg.id);
        console.error('Gagal kirim:', err);
    } finally {
        sending.value = false;
    }
}

// ── WebSocket subscription & connection state ─────────────────────────────────
let echoChannel    = null;

onMounted(() => {
    scrollToBottom();

    echoChannel = window.Echo
        .private(props.channelName)
        .listen('.private.message.sent', (payload) => {
            if (payload.sender_id !== authUser.id) {
                messages.value.push(payload);
                nextTick(() => scrollToBottom());
            }
        });

    // Pantau state koneksi WebSocket
    // - disconnected/unavailable → tampilkan banner peringatan
    // - connected (setelah sebelumnya disconnect) → catch-up pesan yang terlewat
    window.Echo.connector.pusher.connection.bind('state_change', async ({ previous, current }) => {
        if (current === 'connected') {
            wsState.value = 'connected';

            // Reconnect setelah sebelumnya disconnect → ambil pesan yang terlewat
            if (previous === 'connecting' || previous === 'disconnected' || previous === 'unavailable') {
                await catchUpMessages();
            }
        } else if (current === 'connecting') {
            wsState.value = 'connecting';
        } else {
            wsState.value = 'disconnected';
        }
    });
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leave(props.channelName);
    }
});

watch(() => messages.value.length, () => {
    nextTick(() => scrollToBottom());
});
</script>
