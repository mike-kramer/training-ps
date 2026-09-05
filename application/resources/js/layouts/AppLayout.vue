<template>
  <div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
        <RouterLink to="/cashboxes" class="text-lg font-semibold tracking-tight text-teal-800">
          Training Payments
        </RouterLink>
        <nav class="flex items-center gap-4 text-sm">
          <RouterLink
            v-if="isAuthenticated"
            to="/cashboxes"
            class="text-slate-600 hover:text-slate-900"
          >
            Cashboxes
          </RouterLink>
          <template v-if="isAuthenticated">
            <span class="hidden text-slate-500 sm:inline">{{ user?.email }}</span>
            <button
              type="button"
              class="rounded-md bg-slate-900 px-3 py-1.5 text-white hover:bg-slate-700"
              @click="onLogout"
            >
              Log out
            </button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="text-slate-600 hover:text-slate-900">Log in</RouterLink>
            <RouterLink
              to="/register"
              class="rounded-md bg-teal-700 px-3 py-1.5 text-white hover:bg-teal-800"
            >
              Register
            </RouterLink>
          </template>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const { user, isAuthenticated, logout } = useAuth();

async function onLogout() {
  await logout();
  router.push({ name: 'login' });
}
</script>
