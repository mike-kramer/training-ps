<template>
  <AppLayout>
    <div class="mx-auto max-w-md">
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Verify email</h1>
      <p class="mt-2 text-sm text-slate-500">
        Optional. Enter the code from your email if you want to confirm the address.
        You can use the app without verifying.
      </p>

      <form class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="onSubmit">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700" for="code">Verification code</label>
          <input
            id="code"
            v-model="code"
            type="text"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          >
          <p v-if="errors.code" class="mt-1 text-sm text-red-700">{{ errors.code[0] }}</p>
        </div>
        <p v-if="success" class="text-sm text-green-700">Email verified.</p>
        <p v-if="formError" class="text-sm text-red-700">{{ formError }}</p>
        <button
          type="submit"
          class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
          :disabled="loading"
        >
          {{ loading ? 'Checking…' : 'Verify' }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import { useAuth } from '../composables/useAuth';
import { errorMessage, validationErrors } from '../api/client';

const { verifyEmail } = useAuth();
const code = ref('');
const errors = ref({});
const formError = ref('');
const success = ref(false);
const loading = ref(false);

async function onSubmit() {
  loading.value = true;
  errors.value = {};
  formError.value = '';
  success.value = false;
  try {
    await verifyEmail(code.value);
    success.value = true;
  } catch (error) {
    errors.value = validationErrors(error) || {};
    formError.value = errorMessage(error, 'Invalid code');
  } finally {
    loading.value = false;
  }
}
</script>
