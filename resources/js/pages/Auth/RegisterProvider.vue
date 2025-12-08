<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    business_name: '',
    bio: '',
    tagline: '',
    location: '',
});

const submit = () => {
    form.post('/register/provider', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Become a Provider">
        <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Become a Provider
        </h2>
        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6">
            Start offering your services on Zeen
        </p>

        <el-form @submit.prevent="submit" label-position="top">
            <!-- Personal Information -->
            <el-divider content-position="left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                    Personal Information
                </span>
            </el-divider>

            <el-form-item
                label="Full name"
                :error="form.errors.name"
            >
                <el-input
                    v-model="form.name"
                    type="text"
                    placeholder="Enter your full name"
                    size="large"
                    :prefix-icon="User"
                />
            </el-form-item>

            <el-row :gutter="16">
                <el-col :span="12">
                    <el-form-item
                        label="Email"
                        :error="form.errors.email"
                    >
                        <el-input
                            v-model="form.email"
                            type="email"
                            placeholder="Enter your email"
                            size="large"
                            :prefix-icon="Message"
                        />
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="Phone">
                        <el-input
                            v-model="form.phone"
                            type="tel"
                            placeholder="+1876 123 4567"
                            size="large"
                            :prefix-icon="Phone"
                        />
                    </el-form-item>
                </el-col>
            </el-row>

            <el-row :gutter="16">
                <el-col :span="12">
                    <el-form-item
                        label="Password"
                        :error="form.errors.password"
                    >
                        <el-input
                            v-model="form.password"
                            type="password"
                            placeholder="Create a password"
                            size="large"
                            show-password
                            :prefix-icon="Lock"
                        />
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="Confirm Password">
                        <el-input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                            size="large"
                            show-password
                            :prefix-icon="Lock"
                        />
                    </el-form-item>
                </el-col>
            </el-row>

            <!-- Business Information -->
            <el-divider content-position="left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                    Business Information
                </span>
            </el-divider>

            <el-form-item
                label="Business name"
                :error="form.errors.business_name"
            >
                <el-input
                    v-model="form.business_name"
                    type="text"
                    placeholder="e.g., John's Barbershop"
                    size="large"
                    :prefix-icon="OfficeBuilding"
                />
            </el-form-item>

            <el-form-item label="Tagline (optional)">
                <el-input
                    v-model="form.tagline"
                    type="text"
                    placeholder="e.g., Quality cuts, every time"
                    size="large"
                />
            </el-form-item>

            <el-form-item label="Location (optional)">
                <el-input
                    v-model="form.location"
                    type="text"
                    placeholder="e.g., Kingston, Jamaica"
                    size="large"
                    :prefix-icon="Location"
                />
            </el-form-item>

            <el-form-item label="About your business (optional)">
                <el-input
                    v-model="form.bio"
                    type="textarea"
                    :rows="3"
                    placeholder="Tell clients about your experience and services..."
                />
            </el-form-item>

            <el-button
                type="primary"
                native-type="submit"
                size="large"
                :loading="form.processing"
                class="w-full"
            >
                {{ form.processing ? 'Creating your profile...' : 'Create Provider Account' }}
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
                Just looking to book services?
                <Link href="/register" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Register as a Client
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script lang="ts">
import { User, Message, Phone, Lock, OfficeBuilding, Location } from '@element-plus/icons-vue';

export default {
    components: { User, Message, Phone, Lock, OfficeBuilding, Location }
};
</script>
