<script setup lang="ts">
interface Props {
  icon: string;
  label?: string;
  position?: 'bottom-right' | 'bottom-left' | 'bottom-center';
  offset?: {
    bottom?: string;
    right?: string;
    left?: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  position: 'bottom-right',
  offset: () => ({ bottom: '1.5rem', right: '1.5rem' })
});

const emit = defineEmits<{
  click: [event: MouseEvent];
}>();

const handleClick = (event: MouseEvent) => {
  emit('click', event);
};
</script>

<template>
  <button
    class="fab"
    :class="`fab-${position}`"
    :style="{
      bottom: offset.bottom,
      right: offset.right,
      left: offset.left
    }"
    :aria-label="label"
    @click="handleClick"
  >
    <i :class="icon" class="fab-icon"></i>
  </button>
</template>

<style scoped>
.fab {
  position: fixed;
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1ABC9C 0%, #106B4F 100%);
  border: none;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(16, 107, 79, 0.3),
              0 2px 4px rgba(16, 107, 79, 0.2);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 40;
  color: white;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(16, 107, 79, 0.4),
              0 3px 6px rgba(16, 107, 79, 0.25);
}

.fab:active {
  transform: scale(1.05);
}

/* Position variants */
.fab-bottom-right {
  bottom: 1.5rem;
  right: 1.5rem;
}

.fab-bottom-left {
  bottom: 1.5rem;
  left: 1.5rem;
}

.fab-bottom-center {
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
}

.fab-bottom-center:hover {
  transform: translateX(-50%) scale(1.1);
}

.fab-bottom-center:active {
  transform: translateX(-50%) scale(1.05);
}

.fab-icon {
  font-size: 1.25rem;
}

/* Responsive sizing */
@media (max-width: 639px) {
  .fab {
    width: 52px;
    height: 52px;
  }

  .fab-bottom-right {
    bottom: 1rem;
    right: 1rem;
  }

  .fab-bottom-left {
    bottom: 1rem;
    left: 1rem;
  }

  .fab-bottom-center {
    bottom: 1rem;
  }

  .fab-icon {
    font-size: 1.125rem;
  }
}
</style>
