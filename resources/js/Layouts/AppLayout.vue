<template>
    <div class="min-h-screen bg-gray-950 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col fixed h-full z-40">
            <!-- Logo -->
            <div class="px-6 py-5 border-b border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <i class="fa-solid fa-bolt text-white text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-sm leading-tight">ReverbApp</h1>
                        <p class="text-gray-500 text-xs">Real-time Learning</p>
                    </div>
                </div>
            </div>

            <!-- User Info (jika sudah login) -->
            <div v-if="authUser" class="px-4 py-3 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                    :style="{ backgroundColor: userColor }">
                    {{ authUser.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-xs font-semibold truncate">{{ authUser.name }}</p>
                    <p class="text-gray-500 text-xs">Online</p>
                </div>
                <button @click="logout" title="Logout"
                    class="text-gray-600 hover:text-red-400 transition-colors text-xs p-1 rounded-lg hover:bg-red-500/10">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    :href="route('home')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                >
                    <i class="fa-solid fa-house w-4 text-center"></i>
                    Beranda
                </Link>
                <Link
                    :href="route('download')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url.startsWith('/download') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                >
                    <i class="fa-solid fa-download w-4 text-center"></i>
                    Download
                </Link>

                <!-- Divider auth-required -->
                <div class="pt-2 pb-1">
                    <p class="text-xs text-gray-700 font-medium uppercase tracking-wider px-3">
                        <i class="fa-solid fa-lock mr-1 text-xs"></i>Perlu Login
                    </p>
                </div>

                <!-- Public Chat -->
                <Link v-if="authUser"
                    :href="route('chat')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url === '/chat' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                >
                    <i class="fa-solid fa-comments w-4 text-center"></i>
                    Public Chat
                </Link>
                <Link v-else :href="route('login')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-400 transition-colors">
                    <i class="fa-solid fa-comments w-4 text-center"></i>
                    Public Chat
                    <span class="ml-auto text-xs bg-gray-800 text-gray-600 px-1.5 py-0.5 rounded">Login</span>
                </Link>

                <!-- Private Chat -->
                <Link v-if="authUser"
                    :href="route('private-chat.users')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url.startsWith('/private-chat') ? 'bg-violet-600/20 text-violet-400 border border-violet-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                >
                    <i class="fa-solid fa-lock w-4 text-center"></i>
                    Private Chat
                    <!-- Notif badge unread count -->
                    <span v-if="totalUnread > 0"
                        class="ml-auto min-w-5 h-5 px-1.5 bg-red-500 text-white rounded-full text-xs font-bold flex items-center justify-center">
                        {{ totalUnread > 99 ? '99+' : totalUnread }}
                    </span>
                </Link>
                <Link v-else :href="route('login')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-400 transition-colors">
                    <i class="fa-solid fa-lock w-4 text-center"></i>
                    Private Chat
                    <span class="ml-auto text-xs bg-gray-800 text-gray-600 px-1.5 py-0.5 rounded">Login</span>
                </Link>

                <!-- Login/Register link jika belum login -->
                <div v-if="!authUser" class="pt-2 space-y-1">
                    <Link :href="route('login')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-blue-400 hover:bg-blue-500/10 transition-colors border border-blue-500/20">
                        <i class="fa-solid fa-right-to-bracket w-4 text-center"></i>
                        Masuk
                    </Link>
                    <Link :href="route('register')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                        <i class="fa-solid fa-user-plus w-4 text-center"></i>
                        Daftar
                    </Link>
                </div>
            </nav>

            <!-- Tech Stack Badge -->
            <div class="px-4 py-4 border-t border-gray-800">
                <p class="text-xs text-gray-600 mb-2 font-medium uppercase tracking-wider">Tech Stack</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-red-500/10 text-red-400 rounded text-xs border border-red-500/20">Laravel</span>
                    <span class="px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-xs border border-green-500/20">Vue 3</span>
                    <span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 rounded text-xs border border-purple-500/20">Inertia</span>
                    <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 rounded text-xs border border-blue-500/20">Reverb</span>
                    <span class="px-2 py-0.5 bg-cyan-500/10 text-cyan-400 rounded text-xs border border-cyan-500/20">Tailwind</span>
                    <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-400 rounded text-xs border border-indigo-500/20">Flowbite</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 min-h-screen">
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-sm border-b border-gray-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-white font-semibold text-base">{{ title }}</h2>
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 text-xs text-green-400 bg-green-500/10 border border-green-500/20 px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                            Reverb Connected
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Dashboard' },
});

const page     = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

// Unread count — mulai dari shared prop, update via WebSocket
const totalUnread = ref(page.props.unreadCount ?? 0);

// Warna avatar deterministik berdasarkan user ID
const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f97316', '#6366f1'];
const userColor = computed(() => authUser.value ? COLORS[authUser.value.id % COLORS.length] : '#6b7280');

// Logout
function logout() {
    router.post(route('logout'));
}

// Subscribe ke private notification channel untuk real-time badge update
let notifChannel = null;

onMounted(() => {
    if (authUser.value) {
        notifChannel = window.Echo
            .private(`notifications.${authUser.value.id}`)
            .listen('.private.message.sent', () => {
                // Jika tidak sedang di halaman private chat dengan pengirim tersebut, tambah badge
                if (!page.url.startsWith('/private-chat/')) {
                    totalUnread.value++;
                }
            });
    }
});

onUnmounted(() => {
    if (notifChannel && authUser.value) {
        window.Echo.leave(`notifications.${authUser.value.id}`);
    }
});

// Reset unread saat navigasi ke private chat
router.on('navigate', () => {
    if (page.url.startsWith('/private-chat/')) {
        // Saat masuk ke private chat, update dari server via shared props
        totalUnread.value = page.props.unreadCount ?? 0;
    }
});
</script>
