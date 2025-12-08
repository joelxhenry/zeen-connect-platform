<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import { User, Message, Phone, Lock } from '@element-plus/icons-vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Register">
        <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white mb-6">
            Create your account
        </h2>

        <el-form @submit.prevent="submit" label-position="top">
            <el-form-item
                label="Full name"
                :error="form.errors.name"
            >
                <el-input
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    placeholder="Enter your full name"
                    size="large"
                    :prefix-icon="User"
                />
            </el-form-item>

            <el-form-item
                label="Email address"
                :error="form.errors.email"
            >
                <el-input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="Enter your email"
                    size="large"
                    :prefix-icon="Message"
                />
            </el-form-item>

            <el-form-item
                label="Phone number (optional)"
                :error="form.errors.phone"
            >
                <el-input
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="e.g., +1876 123 4567"
                    size="large"
                    :prefix-icon="Phone"
                />
            </el-form-item>

            <el-form-item
                label="Password"
                :error="form.errors.password"
            >
                <el-input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Create a password"
                    size="large"
                    show-password
                    :prefix-icon="Lock"
                />
            </el-form-item>

            <el-form-item label="Confirm password">
                <el-input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                    size="large"
                    show-password
                    :prefix-icon="Lock"
                />
            </el-form-item>

            <el-button
                type="primary"
                native-type="submit"
                size="large"
                :loading="form.processing"
                class="w-full"
            >
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </el-button>
        </el-form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account?
                <Link href="/login" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Sign in
                </Link>
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Want to offer services?
                <Link href="/register/provider" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Register as a Provider
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>
