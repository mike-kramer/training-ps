<template>
  <GuestLayout subtitle="Create an account">
    <form class="space-y-4" @submit.prevent="onSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="email">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
        >
        <p v-if="errors.email" class="mt-1 text-sm text-red-700">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="new-password"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
        >
        <p v-if="errors.password" class="mt-1 text-sm text-red-700">{{ errors.password[0] }}</p>
      </div>
      <p v-if="formError" class="text-sm text-red-700">{{ formError }}</p>
      <button
        type="submit"
        class="w-full rounded-md bg-teal-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Creating…' : 'Create account' }}
      </button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-500">
      Already registered?
      <RouterLink to="/login" class="font-medium text-teal-700 hover:underline">Sign in</RouterLink>
    </p>
  </GuestLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import GuestLayout from '../layouts/GuestLayout.vue';
import { useAuth } from '../composables/useAuth';
import { errorMessage, validationErrors } from '../api/client';

const router = useRouter();
const { register } = useAuth();

const form = reactive({
  email: '',
  password: '',
});
const errors = ref({});
const formError = ref('');
const loading = ref(false);

async function onSubmit() {
  loading.value = true;
  errors.value = {};
  formError.value = '';
  try {
    await register({ ...form });
    router.push({ name: 'login' });
  } catch (error) {
    errors.value = validationErrors(error) || {};
    formError.value = errorMessage(error, 'Registration failed');
  } finally {
    loading.value = false;
  }
}
</script>
