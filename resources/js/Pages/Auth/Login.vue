<template>
    <Head title="Login" />
    <div class="min-h-screen bg-gray-950 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 flex items-center justify-center shadow-lg shadow-blue-500/30 mx-auto mb-4">
                    <i class="fa-solid fa-bolt text-white"></i>
                </div>
                <h1 class="text-white font-bold text-xl">ReverbApp</h1>
                <p class="text-gray-500 text-sm mt-1">Masuk ke akun kamu</p>
            </div>

            <!-- Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Error -->
                    <div v-if="form.errors.email" class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 text-red-400 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                        {{ form.errors.email }}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="kamu@email.com"
                            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="••••••"
                            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-violet-600 hover:from-blue-500 hover:to-violet-500 text-white font-semibold rounded-xl text-sm transition-all duration-200 shadow-lg shadow-blue-600/20 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ form.processing ? 'Masuk...' : 'Masuk' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-gray-500 text-sm mt-4">
                Belum punya akun?
                <Link :href="route('register')" class="text-blue-400 hover:text-blue-300 font-medium transition-colors">Daftar</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post(route('login.post'));
}
</script>
