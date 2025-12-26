<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Message from 'primevue/message';

interface Invitation {
    token: string;
    email: string;
    name: string | null;
    provider_name: string;
}

interface CurrentUser {
    name: string;
    email: string;
}

interface Props {
    invitation: Invitation;
    isLoggedIn: boolean;
    currentUser: CurrentUser | null;
    emailMatches: boolean;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.invitation.name || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(`/team/invite/${props.invitation.token}`);
};

const acceptAsLoggedInUser = () => {
    form.post(`/team/invite/${props.invitation.token}`);
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="w-16 h-16 bg-[#106B4F] rounded-2xl flex items-center justify-center">
                    <i class="pi pi-users text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">
                Team Invitation
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                You've been invited to join
            </p>
            <p class="text-center text-lg font-semibold text-[#106B4F]">
                {{ invitation.provider_name }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-lg sm:rounded-xl sm:px-10">
                <!-- Already logged in with matching email -->
                <div v-if="isLoggedIn && emailMatches">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <i class="pi pi-check text-green-600 text-2xl"></i>
                        </div>
                        <p class="text-gray-700">
                            You're logged in as <strong>{{ currentUser?.name }}</strong>
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ currentUser?.email }}
                        </p>
                    </div>

                    <Button
                        label="Accept Invitation"
                        icon="pi pi-check"
                        class="w-full !bg-[#106B4F] !border-[#106B4F]"
                        :loading="form.processing"
                        @click="acceptAsLoggedInUser"
                    />
                </div>

                <!-- Logged in but email doesn't match -->
                <div v-else-if="isLoggedIn && !emailMatches">
                    <Message severity="warn" :closable="false" class="mb-4">
                        <template #default>
                            <div>
                                <p class="font-medium">Email Mismatch</p>
                                <p class="text-sm mt-1">
                                    This invitation was sent to <strong>{{ invitation.email }}</strong>,
                                    but you're logged in as <strong>{{ currentUser?.email }}</strong>.
                                </p>
                            </div>
                        </template>
                    </Message>

                    <p class="text-sm text-gray-600 mb-4">
                        Please log out and sign in with the correct account, or ask for a new invitation to be sent to your current email address.
                    </p>

                    <div class="flex gap-3">
                        <Button
                            label="Log Out"
                            icon="pi pi-sign-out"
                            severity="secondary"
                            outlined
                            class="flex-1"
                            @click="$inertia.post('/logout')"
                        />
                    </div>
                </div>

                <!-- Not logged in - show registration form -->
                <div v-else>
                    <div class="mb-6">
                        <p class="text-sm text-gray-600">
                            Create your account to join the team. Your account will be linked to:
                        </p>
                        <p class="mt-2 font-medium text-gray-900">
                            {{ invitation.email }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Your Name
                            </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                                placeholder="Enter your full name"
                            />
                            <small v-if="form.errors.name" class="text-red-500">
                                {{ form.errors.name }}
                            </small>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <Password
                                id="password"
                                v-model="form.password"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.password }"
                                :feedback="true"
                                toggleMask
                                placeholder="Create a password"
                                :pt="{
                                    root: { class: 'w-full' },
                                    input: { class: 'w-full' }
                                }"
                            />
                            <small v-if="form.errors.password" class="text-red-500">
                                {{ form.errors.password }}
                            </small>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <Password
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                class="w-full"
                                :feedback="false"
                                toggleMask
                                placeholder="Confirm your password"
                                :pt="{
                                    root: { class: 'w-full' },
                                    input: { class: 'w-full' }
                                }"
                            />
                        </div>

                        <Button
                            label="Create Account & Join Team"
                            icon="pi pi-user-plus"
                            type="submit"
                            class="w-full !bg-[#106B4F] !border-[#106B4F]"
                            :loading="form.processing"
                        />
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Already have an account?
                            <a href="/login" class="font-medium text-[#106B4F] hover:text-[#0a4a37]">
                                Sign in
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer info -->
            <p class="mt-6 text-center text-xs text-gray-500">
                By accepting this invitation, you'll be able to help manage
                {{ invitation.provider_name }}'s business on Zeen Connect.
            </p>
        </div>
    </div>
</template>
