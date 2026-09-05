<template>
  <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_bottom,_#ecfdf5_0%,_#f8fafc_50%)] px-4 py-10">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <p class="text-sm font-medium uppercase tracking-wide text-teal-700">Payment</p>

      <div v-if="loading" class="mt-6 text-sm text-slate-500">Loading payment…</div>
      <div v-else-if="loadError" class="mt-6 text-sm text-red-700">{{ loadError }}</div>
      <template v-else-if="payment">
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
          {{ formattedAmount }}
        </h1>
        <p class="mt-2 text-slate-600">{{ payment.description }}</p>

        <dl class="mt-6 space-y-2 text-sm">
          <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
            <dt class="text-slate-500">Order</dt>
            <dd class="font-medium text-slate-900">{{ payment.order_id }}</dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
            <dt class="text-slate-500">Status</dt>
            <dd class="font-medium capitalize text-slate-900">{{ payment.status_label }}</dd>
          </div>
          <div class="flex justify-between gap-4 py-2">
            <dt class="text-slate-500">Payment ID</dt>
            <dd class="font-medium text-slate-900">{{ payment.id }}</dd>
          </div>
        </dl>

        <div v-if="payment.status === 0" class="mt-8 grid gap-3 sm:grid-cols-2">
          <button
            type="button"
            class="rounded-md bg-teal-700 px-4 py-3 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-60"
            :disabled="submitting"
            @click="changeStatus(1)"
          >
            Success
          </button>
          <button
            type="button"
            class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 hover:bg-red-100 disabled:opacity-60"
            :disabled="submitting"
            @click="changeStatus(2)"
          >
            Failure
          </button>
        </div>
        <p v-else class="mt-8 rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">
          This payment is already {{ payment.status_label }}.
        </p>
        <p v-if="actionError" class="mt-3 text-sm text-red-700">{{ actionError }}</p>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../api/client';

const route = useRoute();
const payment = ref(null);
const loading = ref(true);
const loadError = ref('');
const submitting = ref(false);
const actionError = ref('');

const formattedAmount = computed(() => {
  if (!payment.value) {
    return '';
  }
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(payment.value.amount / 100);
});

async function loadPayment() {
  loading.value = true;
  loadError.value = '';
  try {
    const { data } = await api.get(`/payments/${route.params.id}`);
    payment.value = data.data;
  } catch (error) {
    loadError.value = errorMessage(error, 'Payment not found');
  } finally {
    loading.value = false;
  }
}

async function changeStatus(status) {
  submitting.value = true;
  actionError.value = '';
  try {
    await api.post(`/payments/${route.params.id}/change-status`, { status });
    await loadPayment();

    const redirectUrl = status === 1 ? payment.value?.success_url : payment.value?.fail_url;
    if (redirectUrl) {
      window.location.href = redirectUrl;
    }
  } catch (error) {
    actionError.value = errorMessage(error, 'Could not update payment');
  } finally {
    submitting.value = false;
  }
}

onMounted(loadPayment);
</script>
