<script setup lang="ts">
import ToggleSwitch from 'primevue/toggleswitch';

interface Event {
  uuid: string;
  name: string;
  status: 'draft' | 'published' | 'cancelled';
  status_label: string;
  is_active: boolean;
  is_recurring: boolean;
  event_type_label: string;
  location_type: 'physical' | 'virtual';
  location_display: string;
  price_display: string;
  capacity_display: string;
  bookings_count: number;
  cover_thumbnail?: string;
  next_occurrence?: any;
}

interface Props {
  event: Event;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  compact: false
});

const emit = defineEmits<{
  edit: [event: Event];
  publish: [event: Event];
  cancel: [event: Event];
  delete: [event: Event];
  toggle: [event: Event];
}>();

const getStatusClass = (status: string) => {
  return `status-${status}`;
};

const formatNextOccurrence = (event: Event) => {
  if (event.status === 'cancelled') {
    return 'Cancelled';
  }
  if (event.status === 'draft') {
    return 'Not published yet';
  }
  // This would need to be implemented based on actual next_occurrence data
  return event.next_occurrence || 'No upcoming occurrence';
};
</script>

<template>
  <div class="event-list-item" :class="{ inactive: !event.is_active, cancelled: event.status === 'cancelled', compact }">
    <!-- Thumbnail -->
    <div
      v-if="event.cover_thumbnail"
      class="item-thumbnail"
      :style="{ backgroundImage: `url(${event.cover_thumbnail})` }"
    ></div>
    <div v-else class="item-thumbnail placeholder">
      <i class="pi pi-calendar"></i>
    </div>

    <!-- Info Section -->
    <div class="item-info" @click="emit('edit', event)">
      <div class="item-header">
        <span class="item-name">{{ event.name }}</span>
        <span class="event-status" :class="getStatusClass(event.status)">
          {{ event.status_label }}
        </span>
      </div>
      <div class="item-meta">
        <span class="meta-item">
          <i :class="event.is_recurring ? 'pi pi-replay' : 'pi pi-calendar'"></i>
          {{ event.event_type_label }}
        </span>
        <span class="meta-item">
          <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
          {{ event.location_display }}
        </span>
      </div>
      <div class="item-next">
        <i class="pi pi-clock"></i>
        {{ formatNextOccurrence(event) }}
      </div>
    </div>

    <!-- Stats & Actions -->
    <div class="item-right">
      <div class="item-stats">
        <span class="stat-price">{{ event.price_display }}</span>
        <span class="stat-item">
          <i class="pi pi-users"></i>
          {{ event.capacity_display }}
        </span>
        <span class="stat-item">
          {{ event.bookings_count }} registered
        </span>
      </div>
      <div class="item-actions">
        <ToggleSwitch
          :modelValue="event.is_active"
          @update:modelValue="emit('toggle', event)"
          class="action-toggle"
        />
        <button
          v-if="event.status === 'draft'"
          class="action-btn publish-btn"
          @click.stop="emit('publish', event)"
          title="Publish event"
        >
          <i class="pi pi-check-circle"></i>
        </button>
        <button
          class="action-btn edit-btn"
          @click.stop="emit('edit', event)"
          title="Edit event"
        >
          <i class="pi pi-pencil"></i>
        </button>
        <button
          v-if="event.status === 'published'"
          class="action-btn cancel-btn"
          @click.stop="emit('cancel', event)"
          title="Cancel event"
        >
          <i class="pi pi-times-circle"></i>
        </button>
        <button
          v-else
          class="action-btn delete-btn"
          @click.stop="emit('delete', event)"
          title="Delete event"
        >
          <i class="pi pi-trash"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.event-list-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background-color: white;
  border: 1px solid var(--color-slate-100, #f1f5f9);
  border-radius: 0.75rem;
  transition: all 0.15s ease;
}

.event-list-item:hover {
  border-color: var(--color-slate-200, #e2e8f0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.event-list-item.inactive {
  opacity: 0.6;
}

.event-list-item.cancelled {
  opacity: 0.6;
}

.event-list-item.cancelled .item-name {
  text-decoration: line-through;
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

.event-list-item.compact .item-thumbnail {
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
  align-items: center;
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

.event-status {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.125rem 0.5rem;
  border-radius: 0.375rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  flex-shrink: 0;
}

.event-status.status-published {
  background-color: #d1fae5;
  color: #065f46;
}

.event-status.status-draft {
  background-color: #f1f5f9;
  color: #475569;
}

.event-status.status-cancelled {
  background-color: #fee2e2;
  color: #991b1b;
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

.item-next {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-slate-600, #475569);
}

.item-next i {
  font-size: 0.6875rem;
}

/* Right side - Stats & Actions */
.item-right {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-end;
  flex-shrink: 0;
}

.item-stats {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.75rem;
}

.stat-price {
  font-weight: 600;
  color: #106B4F;
  font-size: 0.875rem;
}

.stat-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--color-slate-500, #64748b);
}

.stat-item i {
  font-size: 0.6875rem;
}

/* Actions */
.item-actions {
  display: flex;
  align-items: center;
  gap: 0.375rem;
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

.action-btn.publish-btn:hover {
  border-color: #10b981;
  color: #10b981;
}

.action-btn.cancel-btn:hover {
  border-color: #f59e0b;
  color: #f59e0b;
}

.action-btn.delete-btn:hover {
  border-color: #ef4444;
  color: #ef4444;
}

/* Mobile Compact Mode */
.event-list-item.compact {
  padding: 0.625rem;
  flex-wrap: wrap;
}

.event-list-item.compact .item-name {
  font-size: 0.875rem;
}

.event-list-item.compact .action-btn {
  width: 28px;
  height: 28px;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .item-right {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
  }

  .item-stats {
    order: 2;
  }

  .item-actions {
    order: 1;
  }
}

@media (max-width: 639px) {
  .event-list-item {
    flex-wrap: wrap;
  }

  .item-info {
    flex-basis: calc(100% - 56px);
  }

  .item-right {
    flex-basis: 100%;
  }
}
</style>
