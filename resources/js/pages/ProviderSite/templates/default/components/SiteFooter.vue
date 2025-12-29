<script setup lang="ts">
interface FooterLink {
    label: string;
    href: string;
}

interface BusinessHour {
    day: string;
    start_time: string;
    end_time: string;
    is_closed?: boolean;
}

interface Provider {
    business_name: string;
    logo?: string;
    address?: string;
    email?: string;
    phone?: string;
    social_links?: {
        instagram?: string;
        twitter?: string;
        facebook?: string;
        whatsapp?: string;
        youtube?: string;
    };
}

interface Props {
    provider: Provider;
    links?: FooterLink[];
    hours?: BusinessHour[];
    variant?: 'light' | 'dark';
    showSocials?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'dark',
    showSocials: true,
    links: () => [
        { label: 'About Us', href: '/' },
        { label: 'Services', href: '/services' },
        { label: 'Reviews', href: '/reviews' },
        { label: 'Book Now', href: '/book' },
    ],
});

const currentYear = new Date().getFullYear();

const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}${ampm}`;
};

const getSocialIcon = (platform: string): string => {
    const icons: Record<string, string> = {
        instagram: 'pi pi-instagram',
        twitter: 'pi pi-twitter',
        facebook: 'pi pi-facebook',
        whatsapp: 'pi pi-whatsapp',
        youtube: 'pi pi-youtube',
    };
    return icons[platform] || 'pi pi-link';
};

// Get simplified hours display
const simplifiedHours = (() => {
    if (!props.hours || props.hours.length === 0) return [];

    // Group by time slots
    const weekdays = props.hours.filter(h =>
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].includes(h.day.toLowerCase())
    );

    const saturday = props.hours.find(h => h.day.toLowerCase() === 'saturday');
    const sunday = props.hours.find(h => h.day.toLowerCase() === 'sunday');

    const result = [];

    // Check if all weekdays have same hours
    if (weekdays.length > 0) {
        const firstWeekday = weekdays[0];
        const allSame = weekdays.every(
            h => h.start_time === firstWeekday.start_time && h.end_time === firstWeekday.end_time
        );

        if (allSame && !firstWeekday.is_closed) {
            result.push({
                label: 'Working Days',
                hours: `${formatTime(firstWeekday.start_time)} - ${formatTime(firstWeekday.end_time)}`,
            });
        }
    }

    if (saturday && !saturday.is_closed) {
        result.push({
            label: 'Saturday',
            hours: `${formatTime(saturday.start_time)} - ${formatTime(saturday.end_time)}`,
        });
    }

    if (sunday) {
        result.push({
            label: 'Sunday',
            hours: sunday.is_closed ? 'Closed' : `${formatTime(sunday.start_time)} - ${formatTime(sunday.end_time)}`,
        });
    }

    return result;
})();
</script>

<template>
    <footer class="site-footer" :class="`site-footer--${variant}`">
        <div class="site-footer__container">
            <div class="site-footer__grid">
                <!-- Useful Links -->
                <div class="site-footer__column">
                    <h3 class="site-footer__heading">Useful Links</h3>
                    <nav class="site-footer__nav">
                        <AppLink
                            v-for="link in links"
                            :key="link.href"
                            :href="link.href"
                            class="site-footer__link"
                        >
                            {{ link.label }}
                        </AppLink>
                    </nav>
                </div>

                <!-- Logo / Brand -->
                <div class="site-footer__column site-footer__column--brand">
                    <img
                        v-if="provider.logo"
                        :src="provider.logo"
                        :alt="provider.business_name"
                        class="site-footer__logo"
                    />
                    <span v-else class="site-footer__brand-name">{{ provider.business_name }}</span>

                    <!-- Social Links -->
                    <div v-if="showSocials && provider.social_links" class="site-footer__socials">
                        <a
                            v-for="(url, platform) in provider.social_links"
                            :key="platform"
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="site-footer__social-link"
                            :title="platform"
                        >
                            <i :class="getSocialIcon(platform)"></i>
                        </a>
                    </div>
                </div>

                <!-- Location & Hours -->
                <div class="site-footer__column">
                    <h3 class="site-footer__heading">Our Location</h3>
                    <address v-if="provider.address" class="site-footer__address">
                        {{ provider.address }}
                    </address>

                    <div v-if="simplifiedHours.length > 0" class="site-footer__hours">
                        <div
                            v-for="item in simplifiedHours"
                            :key="item.label"
                            class="site-footer__hours-row"
                        >
                            <span class="site-footer__hours-day">{{ item.label }}</span>
                            <span class="site-footer__hours-time">{{ item.hours }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="site-footer__bottom">
                <p class="site-footer__copyright">
                    Copyright &copy; {{ currentYear }} {{ provider.business_name }}
                </p>
                <div class="site-footer__legal">
                    <a href="/privacy">Privacy Policy</a>
                    <span>|</span>
                    <a href="/terms">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.site-footer {
    padding: 4rem 1.5rem 2rem;
}

.site-footer--light {
    background: #f9fafb;
}

.site-footer--dark {
    background: var(--barber-dark, #2A2018);
}

.site-footer__container {
    max-width: 1200px;
    margin: 0 auto;
}

.site-footer__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 3rem;
    padding-bottom: 3rem;
    border-bottom: 1px solid;
}

.site-footer--light .site-footer__grid {
    border-color: #e5e7eb;
}

.site-footer--dark .site-footer__grid {
    border-color: var(--barber-dark-secondary, #3D3024);
}

.site-footer__heading {
    margin: 0 0 1.25rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.site-footer--light .site-footer__heading {
    color: var(--provider-text, #1f2937);
}

.site-footer--dark .site-footer__heading {
    color: #fff;
}

.site-footer__nav {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.site-footer__link {
    font-size: 0.875rem;
    text-decoration: none;
    transition: color 0.2s;
}

.site-footer--light .site-footer__link {
    color: #6b7280;
}

.site-footer--dark .site-footer__link {
    color: var(--barber-text-muted, #A69F94);
}

.site-footer__link:hover {
    color: var(--provider-primary, #C4A962);
}

.site-footer__column--brand {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.site-footer__logo {
    max-height: 48px;
    width: auto;
    margin-bottom: 1.5rem;
}

.site-footer__brand-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.site-footer--light .site-footer__brand-name {
    color: var(--provider-text, #1f2937);
}

.site-footer--dark .site-footer__brand-name {
    color: #fff;
}

.site-footer__socials {
    display: flex;
    gap: 0.75rem;
}

.site-footer__social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.2s;
}

.site-footer--light .site-footer__social-link {
    background: #e5e7eb;
    color: #6b7280;
}

.site-footer--dark .site-footer__social-link {
    background: var(--barber-dark-secondary, #3D3024);
    color: var(--barber-text-muted, #A69F94);
}

.site-footer__social-link:hover {
    background: var(--provider-primary, #C4A962);
    color: var(--barber-dark, #2A2018);
}

.site-footer__address {
    font-style: normal;
    font-size: 0.875rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.site-footer--light .site-footer__address {
    color: #6b7280;
}

.site-footer--dark .site-footer__address {
    color: var(--barber-text-muted, #A69F94);
}

.site-footer__hours {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.site-footer__hours-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
}

.site-footer--light .site-footer__hours-day {
    color: var(--provider-text, #1f2937);
}

.site-footer--dark .site-footer__hours-day {
    color: #fff;
}

.site-footer--light .site-footer__hours-time {
    color: #6b7280;
}

.site-footer--dark .site-footer__hours-time {
    color: var(--provider-primary, #C4A962);
}

.site-footer__bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
}

.site-footer__copyright {
    margin: 0;
    font-size: 0.875rem;
}

.site-footer--light .site-footer__copyright {
    color: #6b7280;
}

.site-footer--dark .site-footer__copyright {
    color: var(--barber-text-muted, #A69F94);
}

.site-footer__legal {
    display: flex;
    gap: 0.75rem;
    font-size: 0.75rem;
}

.site-footer--light .site-footer__legal {
    color: #9ca3af;
}

.site-footer--dark .site-footer__legal {
    color: var(--barber-text-muted, #A69F94);
}

.site-footer__legal a {
    text-decoration: none;
    color: inherit;
}

.site-footer__legal a:hover {
    color: var(--provider-primary, #C4A962);
}

@media (max-width: 768px) {
    .site-footer__grid {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }

    .site-footer__nav {
        align-items: center;
    }

    .site-footer__address {
        text-align: center;
    }

    .site-footer__hours-row {
        justify-content: center;
        gap: 1rem;
    }

    .site-footer__bottom {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>
