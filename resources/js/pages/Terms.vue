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
        id: 'acceptance',
        title: 'Acceptance of Terms',
        content: `By accessing or using the Zeen platform, you agree to be bound by these Terms of Service and our Privacy Policy. If you do not agree to these terms, please do not use our services.

These terms apply to all users of the platform, including service providers and clients.`,
    },
    {
        id: 'description',
        title: 'Description of Services',
        content: `Zeen is a platform that connects service providers with clients in Jamaica. We provide:

• Online booking and scheduling tools
• Client management features
• Payment processing
• Communication tools between providers and clients

We act as an intermediary and are not responsible for the actual services provided by service providers.`,
    },
    {
        id: 'accounts',
        title: 'User Accounts',
        content: `To use certain features, you must create an account. You agree to:

• Provide accurate and complete information
• Keep your login credentials secure
• Notify us immediately of unauthorized access
• Be responsible for all activity under your account

We reserve the right to suspend or terminate accounts that violate these terms.`,
    },
    {
        id: 'provider-terms',
        title: 'Service Provider Terms',
        content: `As a service provider on Zeen, you agree to:

• Provide accurate descriptions of your services
• Honor bookings made through the platform
• Maintain appropriate licenses and insurance for your services
• Respond to client inquiries in a timely manner
• Comply with all applicable laws and regulations

You are solely responsible for the quality and delivery of your services.`,
    },
    {
        id: 'client-terms',
        title: 'Client Terms',
        content: `As a client using Zeen, you agree to:

• Provide accurate booking information
• Arrive on time for scheduled appointments
• Cancel or reschedule with appropriate notice
• Pay for services as agreed
• Treat service providers with respect

Failure to appear for bookings may result in forfeiture of deposits.`,
    },
    {
        id: 'payments',
        title: 'Payments and Fees',
        content: `Payment terms vary by subscription tier:

• Platform fees are charged on transactions as specified in your pricing plan
• Deposits are collected at the time of booking
• Refund policies are set by individual service providers
• Payment processing fees are charged by our payment partners

All prices are in Jamaican Dollars (JMD) unless otherwise specified.`,
    },
    {
        id: 'cancellations',
        title: 'Cancellations and Refunds',
        content: `Cancellation policies are set by individual service providers. Generally:

• Clients should review provider cancellation policies before booking
• Deposits may be forfeited for late cancellations or no-shows
• Providers may refuse future bookings from clients who repeatedly cancel

Disputes should first be resolved directly between the provider and client. Zeen may assist in mediation but is not liable for refunds.`,
    },
    {
        id: 'prohibited',
        title: 'Prohibited Activities',
        content: `You may not use Zeen to:

• Engage in illegal or fraudulent activities
• Harass or discriminate against others
• Spam or send unsolicited communications
• Circumvent platform fees or payment processing
• Scrape or collect data without permission
• Impersonate others or provide false information
• Upload malicious code or interfere with platform operations`,
    },
    {
        id: 'intellectual-property',
        title: 'Intellectual Property',
        content: `The Zeen name, logo, and platform design are our intellectual property. You may not use our branding without permission.

Content you upload remains yours, but you grant us a license to display it on the platform. You are responsible for ensuring you have rights to any content you upload.`,
    },
    {
        id: 'limitation',
        title: 'Limitation of Liability',
        content: `Zeen is provided "as is" without warranties of any kind. We are not liable for:

• Actions or omissions of service providers or clients
• Service quality or outcomes
• Lost profits or indirect damages
• Platform downtime or technical issues
• Unauthorized access to your account

Our total liability is limited to the fees you've paid to Zeen in the past 12 months.`,
    },
    {
        id: 'changes',
        title: 'Changes to Terms',
        content: `We may update these terms from time to time. We will notify you of significant changes via email or platform notification. Continued use after changes constitutes acceptance of the new terms.`,
    },
    {
        id: 'governing-law',
        title: 'Governing Law',
        content: `These terms are governed by the laws of Jamaica. Any disputes shall be resolved in the courts of Jamaica.`,
    },
    {
        id: 'contact',
        title: 'Contact Us',
        content: `For questions about these terms, contact us at:

Email: legal@zeen.com
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
        <Head title="Terms of Service" />

        <div class="legal-page">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h1>Terms of Service</h1>
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
