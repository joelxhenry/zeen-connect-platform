<script setup lang="ts">
import Avatar from 'primevue/avatar';

interface TeamMember {
    id: number;
    uuid?: string;
    name: string;
    role?: string;
    avatar?: string;
    social_links?: Record<string, string>;
}

interface Props {
    teamMembers: TeamMember[];
    title?: string;
    subtitle?: string;
    showSocials?: boolean;
}

withDefaults(defineProps<Props>(), {
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
        linkedin: 'pi pi-linkedin',
    };
    return icons[platform.toLowerCase()] || 'pi pi-link';
};
</script>

<template>
    <section class="team-showcase">
        <div class="team-showcase__container">
            <div v-if="title || subtitle" class="team-showcase__header">
                <h2 v-if="title" class="team-showcase__title">{{ title }}</h2>
                <p v-if="subtitle" class="team-showcase__subtitle">{{ subtitle }}</p>
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
                        v-if="showSocials && member.social_links && Object.keys(member.social_links).length > 0"
                        class="team-member__socials"
                    >
                        <a
                            v-for="(url, platform) in member.social_links"
                            :key="platform"
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="team-member__social-link"
                            :title="String(platform)"
                        >
                            <i :class="getSocialIcon(String(platform))"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.team-showcase {
    padding: 4rem 1.5rem;
    background: var(--provider-background, #f9fafb);
}

.team-showcase__container {
    max-width: 1200px;
    margin: 0 auto;
}

.team-showcase__header {
    text-align: center;
    margin-bottom: 3rem;
}

.team-showcase__title {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.team-showcase__subtitle {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-text-muted, #6b7280);
}

.team-showcase__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.team-member {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    transition: box-shadow 0.2s;
}

.team-member:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.team-member__avatar-wrapper {
    margin-bottom: 1rem;
}

:deep(.team-member__avatar) {
    width: 120px !important;
    height: 120px !important;
    font-size: 1.75rem !important;
    border: 3px solid var(--provider-primary, #3b82f6);
}

:deep(.team-member__avatar--fallback) {
    background: var(--provider-primary, #3b82f6) !important;
    color: #fff !important;
}

.team-member__name {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.team-member__role {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
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
    background: var(--provider-background, #f3f4f6);
    color: var(--provider-text-muted, #6b7280);
    text-decoration: none;
    transition: all 0.2s;
}

.team-member__social-link:hover {
    background: var(--provider-primary, #3b82f6);
    color: #fff;
}

@media (max-width: 640px) {
    .team-showcase__grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    :deep(.team-member__avatar) {
        width: 80px !important;
        height: 80px !important;
    }

    .team-member {
        padding: 1rem;
    }
}
</style>
