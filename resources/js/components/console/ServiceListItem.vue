<script setup lang="ts">
import ToggleSwitch from 'primevue/toggleswitch';

interface Service {
  uuid: string;
  name: string;
  description: string;
  duration_display: string;
  price_display: string;
  is_active: boolean;
  category: { name: string } | null;
  cover_thumbnail?: string;
  total_bookings: number;
}

interface Props {
  service: Service;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  compact: false
});

const emit = defineEmits<{
  edit: [service: Service];
  delete: [service: Service];
  toggle: [service: Service];
}>();
</script>

<template>
  <div class="service-list-item" :class="{ inactive: !service.is_active, compact }">
    <!-- Thumbnail -->
    <div
      v-if="service.cover_thumbnail"
      class="item-thumbnail"
      :style="{ backgroundImage: `url(${service.cover_thumbnail})` }"
    ></div>
    <div v-else class="item-thumbnail placeholder">
      <i class="pi pi-image"></i>
    </div>

    <!-- Info Section -->
    <div class="item-info" @click="emit('edit', service)">
      <div class="item-header">
        <span class="item-name">{{ service.name }}</span>
        <span class="item-price">{{ service.price_display }}</span>
      </div>
      <div class="item-meta">
        <span class="meta-item">
          <i class="pi pi-clock"></i>
          {{ service.duration_display }}
        </span>
        <span class="meta-item">
          <i class="pi pi-calendar"></i>
          {{ service.total_bookings }} bookings
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="item-actions">
      <ToggleSwitch
        :modelValue="service.is_active"
        @update:modelValue="emit('toggle', service)"
        class="action-toggle"
      />
      <button
        class="action-btn edit-btn"
        @click="emit('edit', service)"
        title="Edit service"
      >
        <i class="pi pi-pencil"></i>
      </button>
      <button
        class="action-btn delete-btn"
        @click="emit('delete', service)"
        title="Delete service"
      >
        <i class="pi pi-trash"></i>
      </button>
    </div>
  </div>
</template>

<style scoped>
.service-list-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background-color: white;
  border: 1px solid var(--color-slate-100, #f1f5f9);
  border-radius: 0.75rem;
  transition: all 0.15s ease;
}

.service-list-item:hover {
  border-color: var(--color-slate-200, #e2e8f0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.service-list-item.inactive {
  opacity: 0.6;
}

/* Thumbnail */
.item-thumbnail {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  background-size: cover;
  background-position: center;
  flex-shrink: 0;
}

.item-thumbnail.placeholder {
  background-color: var(--color-slate-100, #f1f5f9);
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-thumbnail.placeholder i {
  font-size: 1rem;
  color: var(--color-slate-400, #94a3b8);
}

.service-list-item.compact .item-thumbnail {
  width: 40px;
  height: 40px;
}

/* Info Section */
.item-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  cursor: pointer;
}

.item-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
}

.item-name {
  font-weight: 500;
  font-size: 0.9375rem;
  color: var(--color-slate-900, #0f172a);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.item-price {
  font-size: 0.875rem;
  font-weight: 600;
  color: #106B4F;
  flex-shrink: 0;
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-slate-500, #64748b);
}

.meta-item i {
  font-size: 0.6875rem;
}

/* Actions */
.item-actions {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  flex-shrink: 0;
}

.action-toggle {
  margin-right: 0.25rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: 1px solid var(--color-slate-200, #e2e8f0);
  border-radius: 0.375rem;
  color: var(--color-slate-500, #64748b);
  cursor: pointer;
  transition: all 0.15s ease;
}

.action-btn:hover {
  background-color: var(--color-slate-50, #f8fafc);
  color: var(--color-slate-700, #334155);
}

.action-btn.edit-btn:hover {
  border-color: #106B4F;
  color: #106B4F;
}

.action-btn.delete-btn:hover {
  border-color: #ef4444;
  color: #ef4444;
}

/* Mobile Compact Mode */
.service-list-item.compact {
  padding: 0.625rem;
}

.service-list-item.compact .item-name {
  font-size: 0.875rem;
}

.service-list-item.compact .item-price {
  font-size: 0.8125rem;
}

.service-list-item.compact .action-btn {
  width: 28px;
  height: 28px;
}

/* Responsive - Stack actions on very small screens */
@media (max-width: 374px) {
  .item-actions {
    flex-wrap: wrap;
  }
}
</style>
