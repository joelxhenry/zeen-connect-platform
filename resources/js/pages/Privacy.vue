<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';

// ============================================
// CONFIGURABLE CONTENT - Edit sections below
// ============================================

const lastUpdated = 'December 25, 2025';

const sections = [
    {
        id: 'information-collected',
        title: 'Information We Collect',
        content: `We collect information you provide directly to us, such as when you create an account, make a booking, or contact us for support. This includes:

• Name and contact information (email, phone number)
• Account credentials
• Payment information (processed securely through our payment partners)
• Service preferences and booking history
• Communications with service providers`,
    },
    {
        id: 'how-we-use',
        title: 'How We Use Your Information',
        content: `We use the information we collect to:

• Facilitate bookings between clients and service providers
• Process payments and send transaction confirmations
• Send appointment reminders and notifications
• Improve our platform and develop new features
• Respond to your inquiries and provide customer support
• Protect against fraud and unauthorized access`,
    },
    {
        id: 'information-sharing',
        title: 'Information Sharing',
        content: `We share your information only in the following circumstances:

• With service providers you book with (name, contact info, booking details)
• With payment processors to complete transactions
• When required by law or to protect our rights
• With your consent or at your direction

We never sell your personal information to third parties.`,
    },
    {
        id: 'data-security',
        title: 'Data Security',
        content: `We implement appropriate security measures to protect your personal information, including:

• Encryption of sensitive data in transit and at rest
• Regular security assessments and updates
• Access controls limiting who can view your data
• Secure payment processing through PCI-compliant partners

While we strive to protect your information, no method of transmission over the Internet is 100% secure.`,
    },
    {
        id: 'your-rights',
        title: 'Your Rights',
        content: `You have the right to:

• Access and receive a copy of your personal data
• Correct inaccurate information in your account
• Delete your account and associated data
• Opt out of marketing communications
• Request data portability

To exercise these rights, contact us at privacy@zeen.com.`,
    },
    {
        id: 'cookies',
        title: 'Cookies and Tracking',
        content: `We use cookies and similar technologies to:

• Keep you logged in to your account
• Remember your preferences
• Analyze how our platform is used
• Improve our services

You can control cookies through your browser settings, but some features may not work properly if cookies are disabled.`,
    },
    {
        id: 'changes',
        title: 'Changes to This Policy',
        content: `We may update this privacy policy from time to time. We will notify you of significant changes by posting a notice on our platform or sending you an email. Your continued use of Zeen after changes are made constitutes acceptance of the updated policy.`,
    },
    {
        id: 'contact',
        title: 'Contact Us',
        content: `If you have questions about this privacy policy or our practices, please contact us at:

Email: privacy@zeen.com
Address: Kingston, Jamaica`,
    },
];

const activeSection = ref(sections[0]?.id || '');

const handleScroll = () => {
    const scrollPosition = window.scrollY + 150;

    for (let i = sections.length - 1; i >= 0; i--) {
        const element = document.getElementById(sections[i].id);
        if (element && element.offsetTop <= scrollPosition) {
            activeSection.value = sections[i].id;
            break;
        }
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <PublicLayout>
        <Head title="Privacy Policy" />

        <div class="legal-page">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h1>Privacy Policy</h1>
                    <p class="last-updated">Updated {{ lastUpdated }}</p>
                </div>
                <nav class="sidebar-nav">
                    <a
                        v-for="section in sections"
                        :key="section.id"
                        :href="`#${section.id}`"
                        class="nav-item"
                        :class="{ active: activeSection === section.id }"
                    >
                        {{ section.title }}
                    </a>
                </nav>
            </aside>

            <!-- Content -->
            <main class="content">
                <section
                    v-for="section in sections"
                    :key="section.id"
                    :id="section.id"
                    class="content-section"
                >
                    <h2>{{ section.title }}</h2>
                    <div class="section-text">{{ section.content }}</div>
                </section>
            </main>
        </div>
    </PublicLayout>
</template>

<style scoped>
.legal-page {
    display: flex;
    min-height: calc(100vh - 64px);
    background: #fafbfc;
}

/* Sidebar */
.sidebar {
    position: sticky;
    top: 64px;
    width: 280px;
    height: calc(100vh - 64px);
    padding: 2.5rem 1.5rem;
    background: white;
    border-right: 1px solid #e5e7eb;
    flex-shrink: 0;
    overflow-y: auto;
}

.sidebar-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.sidebar-header h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 0.25rem;
}

.last-updated {
    font-size: 0.75rem;
    color: #9ca3af;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.nav-item {
    display: block;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.15s;
}

.nav-item:hover {
    color: #0D1F1B;
    background: #f3f4f6;
}

.nav-item.active {
    color: #106B4F;
    background: #106B4F10;
    font-weight: 500;
}

/* Content */
.content {
    flex: 1;
    padding: 3rem 4rem;
    max-width: 800px;
}

.content-section {
    margin-bottom: 3rem;
    scroll-margin-top: 100px;
}

.content-section h2 {
    font-size: 1.375rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 1rem;
}

.section-text {
    font-size: 0.9375rem;
    color: #4b5563;
    line-height: 1.8;
    white-space: pre-line;
}

/* Responsive */
@media (max-width: 1024px) {
    .content {
        padding: 2.5rem 2rem;
    }
}

@media (max-width: 768px) {
    .legal-page {
        flex-direction: column;
    }

    .sidebar {
        position: relative;
        top: 0;
        width: 100%;
        height: auto;
        padding: 1.5rem;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }

    .sidebar-header {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }

    .sidebar-nav {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .nav-item {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
    }

    .content {
        padding: 2rem 1rem;
    }

    .content-section {
        scroll-margin-top: 80px;
    }
}
</style>
