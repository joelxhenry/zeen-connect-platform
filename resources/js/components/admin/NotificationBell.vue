<script setup lang="ts">
import { ref, computed } from 'vue';
import Badge from 'primevue/badge';
import Popover from 'primevue/popover';
import Button from 'primevue/button';

interface Alert {
    id: string;
    type: 'dispute' | 'payout_failed' | 'provider_pending' | 'system';
    title: string;
    message: string;
    timestamp: string;
    read: boolean;
    link?: string;
}

const props = defineProps<{
    alerts?: Alert[];
}>();

const popover = ref();

const unreadCount = computed(() => {
    return props.alerts?.filter(a => !a.read).length || 0;
});

const sortedAlerts = computed(() => {
    return [...(props.alerts || [])].sort((a, b) =>
        new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
    );
});

const getAlertIcon = (type: Alert['type']) => {
    switch (type) {
        case 'dispute': return 'pi pi-exclamation-triangle';
        case 'payout_failed': return 'pi pi-times-circle';
        case 'provider_pending': return 'pi pi-user-plus';
        case 'system': return 'pi pi-info-circle';
        default: return 'pi pi-bell';
    }
};

const getAlertColor = (type: Alert['type']) => {
    switch (type) {
        case 'dispute': return 'text-amber-500';
        case 'payout_failed': return 'text-red-500';
        case 'provider_pending': return 'text-blue-500';
        case 'system': return 'text-gray-500';
        default: return 'text-gray-400';
    }
};

const toggle = (event: Event) => {
    popover.value.toggle(event);
};
</script>

<template>
    <div class="notification-bell">
        <Button
            type="button"
            text
            rounded
            @click="toggle"
            class="notification-btn"
            aria-label="Notifications"
        >
            <i class="pi pi-bell text-xl"></i>
            <Badge
                v-if="unreadCount > 0"
                :value="unreadCount > 9 ? '9+' : unreadCount"
                severity="danger"
                class="notification-badge"
            />
        </Button>

        <Popover ref="popover" class="notification-popover">
            <div class="notification-panel">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <span v-if="unreadCount > 0" class="unread-count">
                        {{ unreadCount }} unread
                    </span>
                </div>

                <div v-if="sortedAlerts.length === 0" class="notification-empty">
                    <i class="pi pi-check-circle text-3xl text-green-500"></i>
                    <p>All caught up!</p>
                </div>

                <div v-else class="notification-list">
                    <AppLink
                        v-for="alert in sortedAlerts"
                        :key="alert.id"
                        :href="alert.link || '#'"
                        class="notification-item"
                        :class="{ unread: !alert.read }"
                    >
                        <div class="notification-icon" :class="getAlertColor(alert.type)">
                            <i :class="getAlertIcon(alert.type)"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-title">{{ alert.title }}</p>
                            <p class="notification-message">{{ alert.message }}</p>
                            <span class="notification-time">{{ alert.timestamp }}</span>
                        </div>
                    </AppLink>
                </div>

                <div class="notification-footer">
                    <AppLink href="/admin/notifications" class="view-all">
                        View all notifications
                    </AppLink>
                </div>
            </div>
        </Popover>
    </div>
</template>

<style scoped>
.notification-bell {
    position: relative;
}

.notification-btn {
    position: relative;
    color: #64748b;
}

.notification-btn:hover {
    color: #0D1F1B;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(25%, -25%);
}

.notification-panel {
    width: 360px;
    max-height: 480px;
    display: flex;
    flex-direction: column;
}

.notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.notification-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
}

.unread-count {
    font-size: 0.75rem;
    color: #EF4444;
    font-weight: 500;
}

.notification-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #64748b;
}

.notification-empty p {
    margin: 0.5rem 0 0;
}

.notification-list {
    flex: 1;
    overflow-y: auto;
    max-height: 320px;
}

.notification-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.15s;
}

.notification-item:hover {
    background-color: #f9fafb;
}

.notification-item.unread {
    background-color: #f0fdf4;
}

.notification-icon {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f3f4f6;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
}

.notification-message {
    margin: 0.25rem 0 0;
    font-size: 0.75rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-time {
    font-size: 0.75rem;
    color: #9ca3af;
}

.notification-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.view-all {
    font-size: 0.875rem;
    color: #106B4F;
    text-decoration: none;
    font-weight: 500;
}

.view-all:hover {
    text-decoration: underline;
}
</style>
