<script setup lang="ts">
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

interface User {
    name: string;
    email: string;
    phone?: string;
}

interface FormErrors {
    guest_name?: string;
    guest_email?: string;
    guest_phone?: string;
}

interface Props {
    isAuthenticated: boolean;
    user: User | null;
    guestName: string;
    guestEmail: string;
    guestPhone: string;
    notes: string;
    errors?: FormErrors;
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
});

const emit = defineEmits<{
    'update:guestName': [value: string];
    'update:guestEmail': [value: string];
    'update:guestPhone': [value: string];
    'update:notes': [value: string];
}>();
</script>

<template>
    <div class="guest-info-form">
        <!-- Authenticated user display -->
        <div v-if="isAuthenticated && user" class="guest-info-form__authenticated">
            <i class="pi pi-check-circle"></i>
            <div>
                <p class="guest-info-form__name">{{ user.name }}</p>
                <p class="guest-info-form__email">{{ user.email }}</p>
            </div>
        </div>

        <!-- Guest form fields -->
        <template v-else>
            <div class="guest-info-form__row">
                <div class="guest-info-form__field">
                    <label for="guest_name">Name *</label>
                    <InputText
                        id="guest_name"
                        :modelValue="guestName"
                        @update:modelValue="emit('update:guestName', $event)"
                        class="w-full"
                        :class="{ 'p-invalid': errors.guest_name }"
                        placeholder="Your full name"
                    />
                    <small v-if="errors.guest_name" class="guest-info-form__error">
                        {{ errors.guest_name }}
                    </small>
                </div>
                <div class="guest-info-form__field">
                    <label for="guest_phone">Phone *</label>
                    <InputText
                        id="guest_phone"
                        :modelValue="guestPhone"
                        @update:modelValue="emit('update:guestPhone', $event)"
                        class="w-full"
                        :class="{ 'p-invalid': errors.guest_phone }"
                        placeholder="Your phone number"
                    />
                    <small v-if="errors.guest_phone" class="guest-info-form__error">
                        {{ errors.guest_phone }}
                    </small>
                </div>
            </div>
            <div class="guest-info-form__field">
                <label for="guest_email">Email *</label>
                <InputText
                    id="guest_email"
                    :modelValue="guestEmail"
                    @update:modelValue="emit('update:guestEmail', $event)"
                    type="email"
                    class="w-full"
                    :class="{ 'p-invalid': errors.guest_email }"
                    placeholder="your@email.com"
                />
                <small v-if="errors.guest_email" class="guest-info-form__error">
                    {{ errors.guest_email }}
                </small>
                <small class="guest-info-form__hint">
                    We'll send booking updates to this email
                </small>
            </div>
        </template>

        <!-- Notes field (always shown) -->
        <div class="guest-info-form__field">
            <label for="notes">Notes (optional)</label>
            <Textarea
                id="notes"
                :modelValue="notes"
                @update:modelValue="emit('update:notes', $event)"
                rows="3"
                class="w-full"
                placeholder="Any special requests or information for the provider..."
            />
        </div>
    </div>
</template>

<style scoped>
.guest-info-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.guest-info-form__authenticated {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.guest-info-form__authenticated i {
    font-size: 1.25rem;
    color: #106B4F;
}

.guest-info-form__name {
    margin: 0;
    font-weight: 500;
    color: #0D1F1B;
}

.guest-info-form__email {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.guest-info-form__row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .guest-info-form__row {
        grid-template-columns: 1fr;
    }
}

.guest-info-form__field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.guest-info-form__field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.guest-info-form__error {
    color: #ef4444;
    font-size: 0.75rem;
}

.guest-info-form__hint {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}
</style>
