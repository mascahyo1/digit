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
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-gray-900"></span>
                        </div>
                        <div>
                            <p class="text-white text-sm font-semibold">{{ chatWith.name }}</p>
                            <p class="text-gray-500 text-xs flex items-center gap-1">
                                <i class="fa-solid fa-lock text-violet-400 text-xs"></i>
                                Private — channel: {{ channelName }}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs px-2.5 py-1 bg-violet-500/10 text-violet-400 border border-violet-500/20 rounded-full">
                        <i class="fa-solid fa-shield-halved mr-1"></i>End-to-channel encrypted
                    </span>
                </div>

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
                                :class="msg.sender_id === authUser.id
                                    ? 'bg-violet-600 text-white rounded-br-sm'
                                    : 'bg-gray-800 text-gray-100 rounded-bl-sm border border-gray-700'">
                                {{ msg.body }}
                            </div>
                            <p class="text-gray-600 text-xs mt-1"
                                :class="msg.sender_id === authUser.id ? 'text-right mr-1' : 'ml-1'">
                                {{ msg.time }}
                                <i v-if="msg.sender_id === authUser.id" class="fa-solid fa-check-double ml-1"
                                    :class="msg.is_read ? 'text-violet-400' : 'text-gray-600'"></i>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="px-4 py-3 border-t border-gray-800 bg-gray-900/50 flex-shrink-0">
                    <div class="flex items-end gap-2">
                        <div class="flex-1 bg-gray-800 border border-gray-700 rounded-xl focus-within:border-violet-500 focus-within:ring-1 focus-within:ring-violet-500 transition-all">
                            <textarea
                                v-model="messageInput"
                                @keydown.enter.exact.prevent="sendMessage"
                                placeholder="Pesan private... (Enter untuk kirim)"
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
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    chatWith:    { type: Object, required: true },
    messages:    { type: Array, default: () => [] },
    channelName: { type: String, required: true },
});

const page = usePage();
const authUser = page.props.auth.user;

const messages        = ref([...props.messages]);
const messageInput    = ref('');
const sending         = ref(false);
const messagesContainer = ref(null);

const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f97316', '#6366f1'];
function getColor(id) { return COLORS[id % COLORS.length]; }

function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

async function sendMessage() {
    const body = messageInput.value.trim();
    if (!body || sending.value) return;

    sending.value = true;

    // Optimistic update
    const tempMsg = {
        id: 'temp-' + Date.now(),
        sender_id: authUser.id,
        receiver_id: props.chatWith.id,
        body,
        is_read: false,
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    };
    messages.value.push(tempMsg);
    messageInput.value = '';
    await nextTick();
    scrollToBottom();

    try {
        await axios.post(route('private-chat.send', props.chatWith.id), { body });
    } catch (err) {
        messages.value = messages.value.filter(m => m.id !== tempMsg.id);
        console.error('Gagal kirim:', err);
    } finally {
        sending.value = false;
    }
}

// Subscribe ke private channel
let echoChannel = null;

onMounted(() => {
    scrollToBottom();

    // Echo.private() → akan trigger /broadcasting/auth dulu
    echoChannel = window.Echo
        .private(props.channelName)
        .listen('.private.message.sent', (payload) => {
            // Hanya tampilkan pesan dari lawan bicara (pesan kita sudah via optimistic)
            if (payload.sender_id !== authUser.id) {
                messages.value.push(payload);
                nextTick(() => scrollToBottom());
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
