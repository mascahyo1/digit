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
                        <p class="text-gray-500 text-xs">Real-time Download</p>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-1">
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
                <Link
                    :href="route('chat')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url.startsWith('/chat') ? 'bg-violet-600/20 text-violet-400 border border-violet-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                >
                    <i class="fa-solid fa-comments w-4 text-center"></i>
                    Web Chat
                </Link>
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
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});
</script>
