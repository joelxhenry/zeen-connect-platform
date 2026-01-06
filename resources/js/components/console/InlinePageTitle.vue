<script setup lang="ts">
interface Props {
  title: string;
  count?: number;
  countLabel?: string;
}

defineProps<Props>();
</script>

<template>
  <div class="inline-page-title">
    <h1 class="title">{{ title }}</h1>
    <p v-if="count !== undefined" class="count">
      {{ count }} {{ countLabel || 'items' }}
    </p>
  </div>
</template>

<style scoped>
.inline-page-title {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-slate-900, #0f172a);
}

@media (min-width: 768px) {
  .title {
    font-size: 1.75rem;
  }
}

.count {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-slate-500, #64748b);
}
</style>
