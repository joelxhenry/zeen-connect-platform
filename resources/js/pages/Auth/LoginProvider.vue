<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import { Message, Lock } from '@element-plus/icons-vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
    login_type: 'provider',
});

const submit = () => {
    form.post('/login/provider', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Provider Login">
        <div class="text-center mb-6">
            <el-icon size="48" class="icon-primary mb-3"><Shop /></el-icon>
            <h2 class="page-title text-2xl font-bold">Provider Sign In</h2>
            <p class="page-subtitle text-sm mt-1">Access your business console</p>
        </div>

        <el-form @submit.prevent="submit" label-position="top">
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
                label="Password"
                :error="form.errors.password"
            >
                <el-input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    size="large"
                    show-password
                    :prefix-icon="Lock"
                />
            </el-form-item>

            <div class="flex items-center justify-between mb-4">
                <el-checkbox v-model="form.remember">
                    Remember me
                </el-checkbox>
                <Link href="/forgot-password" class="forgot-link text-sm">
                    Forgot your password?
                </Link>
            </div>

            <el-button
                type="primary"
                native-type="submit"
                size="large"
                :loading="form.processing"
                class="w-full"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in to Console' }}
            </el-button>
        </el-form>

        <el-divider>
            <span class="divider-text text-sm">New provider?</span>
        </el-divider>

        <Link href="/register/provider">
            <el-button size="large" class="w-full">
                Create Provider Account
            </el-button>
        </Link>

        <div class="mt-6 text-center">
            <p class="switch-text text-sm">
                Looking to book a service?
                <Link href="/login/client" class="switch-link font-medium">
                    Client Sign In
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
.icon-primary {
    color: var(--color-primary);
}

.page-title {
    color: var(--color-text-primary);
}

.page-subtitle {
    color: var(--color-text-secondary);
}

.forgot-link {
    color: var(--color-primary);
}

.forgot-link:hover {
    color: var(--color-primary-dark);
}

.divider-text {
    color: var(--color-text-muted);
}

.switch-text {
    color: var(--color-text-secondary);
}

.switch-link {
    color: var(--color-primary);
}

.switch-link:hover {
    color: var(--color-primary-dark);
}
</style>
