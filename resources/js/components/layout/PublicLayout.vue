<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import MadeInJamaica from '@/components/common/MadeInJamaica.vue';

defineProps<{
    title?: string;
}>();

const page = usePage();
const user = (page.props.auth as { user?: User } | undefined)?.user;
</script>

<template>
    <Head :title="title" />

    <div class="public-layout">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <Link href="/" class="logo">Zeen</Link>

                <nav class="main-nav">
                    <Link href="/explore" class="nav-link">Explore</Link>
                    <Link href="/become-provider" class="nav-link">Become a Provider</Link>
                </nav>

                <div class="auth-nav">
                    <template v-if="user">
                        <Link v-if="user.role === 'provider'" href="/console" class="nav-link">Dashboard</Link>
                        <Link v-else href="/dashboard" class="nav-link">Dashboard</Link>
                        <Link href="/logout" method="post" as="button" class="logout-btn">Logout</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="nav-link">Login</Link>
                        <Link href="/register" class="signup-btn">Sign Up</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; {{ new Date().getFullYear() }} Zeen. All rights reserved.</p>
                <div class="footer-right">
                    <MadeInJamaica />
                    <div class="footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.public-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.header {
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
    text-decoration: none;
}

.main-nav {
    display: flex;
    gap: 1rem;
}

.nav-link {
    padding: 0.5rem 1rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
}

.nav-link:hover {
    color: #0D1F1B;
}

.auth-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logout-btn {
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    color: #6b7280;
    font-size: 0.875rem;
    cursor: pointer;
}

.signup-btn {
    padding: 0.5rem 1rem;
    background-color: #106B4F;
    color: white;
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.signup-btn:hover {
    background-color: #0D5A42;
}

.main-content {
    flex: 1;
}

.footer {
    background-color: white;
    border-top: 1px solid #e5e7eb;
    padding: 2rem 0;
}

.footer-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.footer-links {
    display: flex;
    gap: 1.5rem;
}

.footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
}

.footer-links a:hover {
    color: #0D1F1B;
}
</style>
