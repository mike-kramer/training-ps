<template>
  <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_top,_#ecfdf5_0%,_#f8fafc_55%)] px-4 py-10">
    <div class="w-full max-w-md">
      <div class="mb-8 text-center">
        <RouterLink to="/login" class="text-2xl font-semibold tracking-tight text-teal-800">
          Training Payments
        </RouterLink>
        <p class="mt-2 text-sm text-slate-500">{{ subtitle }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  subtitle: {
    type: String,
    default: '',
  },
});
</script>
