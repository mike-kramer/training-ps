<template>
  <AppLayout>
    <div class="flex flex-col gap-8">
      <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Cashboxes</h1>
            <p class="mt-1 text-sm text-slate-500">Create and manage cashboxes. Reveal the secret key for merchant API calls.</p>
          </div>
        </div>

        <form
          class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2"
          @submit.prevent="createCashbox"
        >
          <h2 class="sm:col-span-2 text-sm font-semibold uppercase tracking-wide text-slate-500">New cashbox</h2>
          <div>
            <label class="mb-1 block text-sm text-slate-700" for="name">Name</label>
            <input
              id="name"
              v-model="createForm.name"
              required
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            >
            <p v-if="createErrors.name" class="mt-1 text-sm text-red-700">{{ createErrors.name[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm text-slate-700" for="webhook_url">Webhook URL</label>
            <input
              id="webhook_url"
              v-model="createForm.webhook_url"
              type="url"
              required
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            >
            <p v-if="createErrors.webhook_url" class="mt-1 text-sm text-red-700">{{ createErrors.webhook_url[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm text-slate-700" for="success_url">Success URL</label>
            <input
              id="success_url"
              v-model="createForm.success_url"
              type="url"
              required
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            >
            <p v-if="createErrors.success_url" class="mt-1 text-sm text-red-700">{{ createErrors.success_url[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm text-slate-700" for="fail_url">Fail URL</label>
            <input
              id="fail_url"
              v-model="createForm.fail_url"
              type="url"
              required
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            >
            <p v-if="createErrors.fail_url" class="mt-1 text-sm text-red-700">{{ createErrors.fail_url[0] }}</p>
          </div>
          <div class="sm:col-span-2">
            <button
              type="submit"
              class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
              :disabled="creating"
            >
              {{ creating ? 'Creating…' : 'Create cashbox' }}
            </button>
            <p v-if="createError" class="mt-2 text-sm text-red-700">{{ createError }}</p>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-3">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Your cashboxes</h2>
        </div>
        <p v-if="loadingList" class="px-5 py-8 text-sm text-slate-500">Loading…</p>
        <p v-else-if="!cashboxes.length" class="px-5 py-8 text-sm text-slate-500">No cashboxes yet.</p>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="box in cashboxes" :key="box.id" class="px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h3 class="font-medium text-slate-900">{{ box.name }}</h3>
                <p class="mt-1 text-xs text-slate-500">ID {{ box.id }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                  @click="openEdit(box)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                  @click="openReveal(box)"
                >
                  Show secret
                </button>
              </div>
            </div>
            <dl class="mt-3 grid gap-1 text-xs text-slate-500 sm:grid-cols-3">
              <div><dt class="inline font-medium text-slate-600">Success:</dt> {{ box.success_url }}</div>
              <div><dt class="inline font-medium text-slate-600">Fail:</dt> {{ box.fail_url }}</div>
              <div><dt class="inline font-medium text-slate-600">Webhook:</dt> {{ box.webhook_url }}</div>
            </dl>
            <p
              v-if="revealedSecrets[box.id]"
              class="mt-3 rounded-md bg-amber-50 px-3 py-2 font-mono text-sm text-amber-900"
            >
              Secret: {{ revealedSecrets[box.id] }}
            </p>
          </li>
        </ul>
      </section>
    </div>

    <div
      v-if="editTarget"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="editTarget = null"
    >
      <form
        class="w-full max-w-lg space-y-3 rounded-xl bg-white p-5 shadow-lg"
        @submit.prevent="updateCashbox"
      >
        <h3 class="text-lg font-semibold text-slate-900">Edit cashbox</h3>
        <p class="text-sm text-slate-500">Update settings for «{{ editTarget.name }}».</p>
        <div>
          <label class="mb-1 block text-sm text-slate-700" for="edit_name">Name</label>
          <input
            id="edit_name"
            v-model="editForm.name"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          >
          <p v-if="editErrors.name" class="mt-1 text-sm text-red-700">{{ editErrors.name[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-700" for="edit_webhook_url">Webhook URL</label>
          <input
            id="edit_webhook_url"
            v-model="editForm.webhook_url"
            type="url"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          >
          <p v-if="editErrors.webhook_url" class="mt-1 text-sm text-red-700">{{ editErrors.webhook_url[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-700" for="edit_success_url">Success URL</label>
          <input
            id="edit_success_url"
            v-model="editForm.success_url"
            type="url"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          >
          <p v-if="editErrors.success_url" class="mt-1 text-sm text-red-700">{{ editErrors.success_url[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-700" for="edit_fail_url">Fail URL</label>
          <input
            id="edit_fail_url"
            v-model="editForm.fail_url"
            type="url"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          >
          <p v-if="editErrors.fail_url" class="mt-1 text-sm text-red-700">{{ editErrors.fail_url[0] }}</p>
        </div>
        <p v-if="editError" class="text-sm text-red-700">{{ editError }}</p>
        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
            @click="editTarget = null"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
            :disabled="updating"
          >
            {{ updating ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </form>
    </div>

    <div
      v-if="revealTarget"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="revealTarget = null"
    >
      <form class="w-full max-w-sm rounded-xl bg-white p-5 shadow-lg" @submit.prevent="revealSecret">
        <h3 class="text-lg font-semibold text-slate-900">Reveal secret key</h3>
        <p class="mt-1 text-sm text-slate-500">Re-enter your password for «{{ revealTarget.name }}».</p>
        <input
          v-model="revealPassword"
          type="password"
          required
          autocomplete="current-password"
          class="mt-4 w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          placeholder="Password"
        >
        <p v-if="revealError" class="mt-2 text-sm text-red-700">{{ revealError }}</p>
        <div class="mt-4 flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
            @click="revealTarget = null"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
            :disabled="revealing"
          >
            {{ revealing ? 'Checking…' : 'Reveal' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import api, { errorMessage, validationErrors } from '../api/client';

const cashboxes = ref([]);
const loadingList = ref(true);
const creating = ref(false);
const createError = ref('');
const createErrors = ref({});
const createForm = reactive({
  name: '',
  success_url: 'https://example.com/success',
  fail_url: 'https://example.com/fail',
  webhook_url: 'https://example.com/webhook',
});

const editTarget = ref(null);
const updating = ref(false);
const editError = ref('');
const editErrors = ref({});
const editForm = reactive({
  name: '',
  success_url: '',
  fail_url: '',
  webhook_url: '',
});

const revealTarget = ref(null);
const revealPassword = ref('');
const revealError = ref('');
const revealing = ref(false);
const revealedSecrets = reactive({});

async function loadCashboxes() {
  loadingList.value = true;
  try {
    const { data } = await api.get('/cashboxes');
    cashboxes.value = data.data ?? [];
  } finally {
    loadingList.value = false;
  }
}

async function createCashbox() {
  creating.value = true;
  createError.value = '';
  createErrors.value = {};
  try {
    await api.post('/cashboxes', { ...createForm });
    createForm.name = '';
    await loadCashboxes();
  } catch (error) {
    createErrors.value = validationErrors(error) || {};
    createError.value = errorMessage(error, 'Could not create cashbox');
  } finally {
    creating.value = false;
  }
}

function openEdit(box) {
  editTarget.value = box;
  editForm.name = box.name;
  editForm.success_url = box.success_url;
  editForm.fail_url = box.fail_url;
  editForm.webhook_url = box.webhook_url;
  editError.value = '';
  editErrors.value = {};
}

async function updateCashbox() {
  updating.value = true;
  editError.value = '';
  editErrors.value = {};
  try {
    await api.put(`/cashboxes/${editTarget.value.id}`, { ...editForm });
    editTarget.value = null;
    await loadCashboxes();
  } catch (error) {
    editErrors.value = validationErrors(error) || {};
    editError.value = errorMessage(error, 'Could not update cashbox');
  } finally {
    updating.value = false;
  }
}

function openReveal(box) {
  revealTarget.value = box;
  revealPassword.value = '';
  revealError.value = '';
}

async function revealSecret() {
  revealing.value = true;
  revealError.value = '';
  try {
    const { data } = await api.post(`/cashboxes/${revealTarget.value.id}/reveal-secret`, {
      password: revealPassword.value,
    });
    revealedSecrets[revealTarget.value.id] = data.secret_key;
    revealTarget.value = null;
  } catch (error) {
    const errs = validationErrors(error);
    revealError.value = errs?.password?.[0] || errorMessage(error, 'Could not reveal secret');
  } finally {
    revealing.value = false;
  }
}

onMounted(loadCashboxes);
</script>
