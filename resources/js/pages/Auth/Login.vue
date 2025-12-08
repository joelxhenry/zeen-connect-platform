<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Sign In" />
    <div class="login-selector min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <Link href="/" class="flex justify-center">
                <span class="brand-text text-3xl font-bold">Zeen</span>
            </Link>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
            <el-card shadow="always" class="!rounded-lg">
                <div class="text-center mb-8">
                    <h2 class="page-title text-2xl font-bold">Welcome Back</h2>
                    <p class="page-subtitle mt-2">Choose how you'd like to sign in</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Client Login Option -->
                    <Link href="/login/client" class="block">
                        <el-card shadow="hover" class="login-option h-full cursor-pointer">
                            <div class="text-center py-4">
                                <el-icon size="48" class="icon-primary mb-4"><User /></el-icon>
                                <h3 class="option-title text-lg font-semibold mb-2">I'm a Client</h3>
                                <p class="option-text text-sm">
                                    Book services and manage your appointments
                                </p>
                            </div>
                        </el-card>
                    </Link>

                    <!-- Provider Login Option -->
                    <Link href="/login/provider" class="block">
                        <el-card shadow="hover" class="login-option h-full cursor-pointer">
                            <div class="text-center py-4">
                                <el-icon size="48" class="icon-primary mb-4"><Shop /></el-icon>
                                <h3 class="option-title text-lg font-semibold mb-2">I'm a Provider</h3>
                                <p class="option-text text-sm">
                                    Manage your business and bookings
                                </p>
                            </div>
                        </el-card>
                    </Link>
                </div>

                <el-divider>
                    <span class="divider-text text-sm">New to Zeen?</span>
                </el-divider>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <Link href="/register">
                        <el-button size="large" class="w-full">
                            Sign up as Client
                        </el-button>
                    </Link>
                    <Link href="/register/provider">
                        <el-button size="large" class="w-full">
                            Become a Provider
                        </el-button>
                    </Link>
                </div>
            </el-card>
        </div>
    </div>
</template>

<style scoped>
.login-selector {
    background-color: var(--color-surface-alt);
}

.brand-text {
    color: var(--color-primary);
}

.page-title {
    color: var(--color-text-primary);
}

.page-subtitle {
    color: var(--color-text-secondary);
}

.login-option {
    transition: border-color 0.2s, transform 0.2s;
}

.login-option:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

.icon-primary {
    color: var(--color-primary);
}

.option-title {
    color: var(--color-text-primary);
}

.option-text {
    color: var(--color-text-secondary);
}

.divider-text {
    color: var(--color-text-muted);
}
</style>
