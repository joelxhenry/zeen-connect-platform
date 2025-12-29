<script setup lang="ts">
import Avatar from 'primevue/avatar';

export interface TeamMemberForBooking {
    id: number;
    uuid: string;
    name: string;
    avatar: string | null;
}

interface Props {
    teamMembers: TeamMemberForBooking[];
    modelValue: TeamMemberForBooking | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: TeamMemberForBooking | null];
}>();

const selectMember = (member: TeamMemberForBooking | null) => {
    emit('update:modelValue', member);
};

const isSelected = (member: TeamMemberForBooking | null) => {
    if (member === null && props.modelValue === null) return true;
    if (member === null || props.modelValue === null) return false;
    return props.modelValue.id === member.id;
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <div class="team-member-selector">
        <!-- Any Available Option -->
        <div
            class="team-member-card"
            :class="{ 'team-member-card--selected': isSelected(null) }"
            @click="selectMember(null)"
        >
            <div class="team-member-card__avatar team-member-card__avatar--any">
                <i class="pi pi-users"></i>
            </div>
            <div class="team-member-card__info">
                <span class="team-member-card__name">Any Available</span>
                <span class="team-member-card__subtitle">First available team member</span>
            </div>
        </div>

        <!-- Team Member Cards -->
        <div
            v-for="member in teamMembers"
            :key="member.id"
            class="team-member-card"
            :class="{ 'team-member-card--selected': isSelected(member) }"
            @click="selectMember(member)"
        >
            <Avatar
                v-if="member.avatar"
                :image="member.avatar"
                shape="circle"
                size="large"
                class="team-member-card__avatar"
            />
            <div v-else class="team-member-card__avatar team-member-card__avatar--initials">
                {{ getInitials(member.name) }}
            </div>
            <div class="team-member-card__info">
                <span class="team-member-card__name">{{ member.name }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.team-member-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
}

.team-member-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
}

.team-member-card:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.team-member-card--selected {
    border-color: #106B4F;
    background-color: rgba(16, 107, 79, 0.05);
}

.team-member-card__avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    flex-shrink: 0;
}

.team-member-card__avatar--any {
    background-color: #f3f4f6;
    color: #6b7280;
    font-size: 1.25rem;
}

.team-member-card--selected .team-member-card__avatar--any {
    background-color: rgba(16, 107, 79, 0.1);
    color: #106B4F;
}

.team-member-card__avatar--initials {
    background-color: #0D1F1B;
    color: white;
    font-size: 1rem;
    font-weight: 600;
}

.team-member-card__info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
}

.team-member-card__name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.team-member-card__subtitle {
    font-size: 0.75rem;
    color: #6b7280;
}

@media (max-width: 480px) {
    .team-member-selector {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
