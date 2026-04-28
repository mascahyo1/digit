<template>
    <Head title="Private Chat — Pilih User" />
    <AppLayout title="Private Chat">
        <div class="max-w-2xl space-y-4">

            <div class="flex items-center justify-between">
                <p class="text-gray-400 text-sm">Pilih user untuk memulai percakapan private</p>
                <span class="text-xs text-gray-600 bg-gray-800 px-2.5 py-1 rounded-full border border-gray-700">
                    {{ users.length }} user terdaftar
                </span>
            </div>

            <!-- Empty state -->
            <div v-if="users.length === 0" class="bg-gray-900 border border-gray-800 rounded-2xl p-10 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-user-group text-gray-600 text-xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Belum ada user lain yang terdaftar.</p>
                <p class="text-gray-600 text-xs mt-1">Daftarkan akun lain untuk mencoba private chat!</p>
            </div>

            <!-- User list -->
            <div class="space-y-2">
                <Link
                    v-for="user in users"
                    :key="user.id"
                    :href="route('private-chat.show', user.id)"
                    class="flex items-center gap-4 p-4 bg-gray-900 border border-gray-800 rounded-2xl hover:border-gray-700 hover:bg-gray-800/50 transition-all duration-200 group"
                >
                    <!-- Avatar -->
                    <div class="relative flex-shrink-0">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-base"
                            :style="{ backgroundColor: getColor(user.id) }">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <!-- Dot online/offline — berdasarkan heartbeat cache di server -->
                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-gray-900 transition-colors"
                            :class="user.is_online ? 'bg-green-400' : 'bg-gray-600'"
                            :title="user.is_online ? 'Online' : 'Offline'"></span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium text-sm">{{ user.name }}</p>
                        <p class="text-xs" :class="user.is_online ? 'text-green-400' : 'text-gray-500'">
                            <span v-if="user.is_online">
                                <i class="fa-solid fa-circle text-xs mr-0.5"></i>Online
                            </span>
                            <span v-else>Offline</span>
                        </p>
                    </div>

                    <!-- Badge unread -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span v-if="user.unread_count > 0"
                            class="min-w-5 h-5 px-1.5 bg-red-500 text-white rounded-full text-xs font-bold flex items-center justify-center">
                            {{ user.unread_count > 99 ? '99+' : user.unread_count }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-gray-600 group-hover:text-gray-400 text-xs transition-colors"></i>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    users: { type: Array, default: () => [] },
});

const COLORS = [
    '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b',
    '#10b981', '#06b6d4', '#f97316', '#6366f1',
];

function getColor(id) {
    return COLORS[id % COLORS.length];
}
</script>
