<script setup lang="ts">
import Avatar from 'primevue/avatar';

interface TeamMember {
    id: number;
    uuid?: string;
    name: string;
    role?: string;
    avatar?: string;
    social_links?: {
        instagram?: string;
        twitter?: string;
        facebook?: string;
        whatsapp?: string;
        youtube?: string;
    };
}

interface Props {
    teamMembers: TeamMember[];
    variant?: 'light' | 'dark';
    title?: string;
    showSocials?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'light',
    showSocials: true,
});

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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
</script>

<template>
    <section class="team-showcase" :class="`team-showcase--${variant}`">
        <div v-if="title" class="team-showcase__header">
            <h2 class="team-showcase__title">{{ title }}</h2>
        </div>

        <div class="team-showcase__grid">
            <div
                v-for="member in teamMembers"
                :key="member.id"
                class="team-member"
            >
                <div class="team-member__avatar-wrapper">
                    <Avatar
                        v-if="member.avatar"
                        :image="member.avatar"
                        shape="circle"
                        class="team-member__avatar"
                    />
                    <Avatar
                        v-else
                        :label="getInitials(member.name)"
                        shape="circle"
                        class="team-member__avatar team-member__avatar--fallback"
                    />
                </div>

                <h3 class="team-member__name">{{ member.name }}</h3>
                <p v-if="member.role" class="team-member__role">{{ member.role }}</p>

                <div
                    v-if="showSocials && member.social_links"
                    class="team-member__socials"
                >
                    <a
                        v-for="(url, platform) in member.social_links"
                        :key="platform"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="team-member__social-link"
                        :title="platform"
                    >
                        <i :class="getSocialIcon(platform)"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.team-showcase {
    padding: 3rem 0;
}

.team-showcase--light {
    background: #fff;
}

.team-showcase--dark {
    background: var(--barber-dark, #2A2018);
}

.team-showcase__header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.team-showcase__title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.team-showcase--light .team-showcase__title {
    color: var(--provider-text, #1f2937);
}

.team-showcase--dark .team-showcase__title {
    color: #fff;
}

.team-showcase__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.team-member {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.team-member__avatar-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

:deep(.team-member__avatar) {
    width: 140px !important;
    height: 140px !important;
    font-size: 2rem !important;
    border: 3px solid var(--provider-primary, #C4A962);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.team-member__avatar--fallback) {
    background: var(--provider-primary, #C4A962) !important;
    color: #fff !important;
}

.team-member__name {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.team-showcase--light .team-member__name {
    color: var(--provider-text, #1f2937);
}

.team-showcase--dark .team-member__name {
    color: #fff;
}

.team-member__role {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
}

.team-showcase--light .team-member__role {
    color: #6b7280;
}

.team-showcase--dark .team-member__role {
    color: var(--barber-text-muted, #A69F94);
}

.team-member__socials {
    display: flex;
    gap: 0.5rem;
}

.team-member__social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.2s;
}

.team-showcase--light .team-member__social-link {
    background: #f3f4f6;
    color: #6b7280;
}

.team-showcase--light .team-member__social-link:hover {
    background: var(--provider-primary, #C4A962);
    color: #fff;
}

.team-showcase--dark .team-member__social-link {
    background: var(--barber-dark-secondary, #3D3024);
    color: var(--barber-text-muted, #A69F94);
}

.team-showcase--dark .team-member__social-link:hover {
    background: var(--provider-primary, #C4A962);
    color: #fff;
}

@media (max-width: 640px) {
    .team-showcase__grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    :deep(.team-member__avatar) {
        width: 100px !important;
        height: 100px !important;
    }
}
</style>
