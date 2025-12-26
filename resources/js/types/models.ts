export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
    role: 'client' | 'provider' | 'admin';
    is_active: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
}

export interface Provider {
    id: number;
    uuid: string;
    user_id: number;
    business_name: string;
    slug: string;
    bio?: string;
    tagline?: string;
    address?: string;
    website?: string;
    social_links?: Record<string, string>;
    status: 'pending' | 'active' | 'suspended' | 'inactive';
    commission_rate: number;
    rating_avg: number;
    rating_count: number;
    total_bookings: number;
    is_featured: boolean;
    verified_at?: string;
    created_at: string;
    updated_at: string;
    user?: User;
}

export interface Client {
    id: number;
    uuid: string;
    user_id: number;
    preferred_location?: string;
    preferences?: Record<string, unknown>;
    total_bookings: number;
    user?: User;
}

export interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
    description?: string;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface Service {
    id: number;
    uuid: string;
    provider_id: number;
    category_id: number;
    name: string;
    description?: string;
    duration_minutes: number;
    price: number;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
    provider?: Provider;
    category?: Category;
}

export interface ProviderAvailability {
    id?: number;
    provider_id?: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_available: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface BlockedDate {
    id?: number | null;
    provider_id?: number;
    date: string;
    reason?: string | null;
    created_at?: string;
    updated_at?: string;
}

export type TeamMemberStatus = 'pending' | 'active' | 'suspended';

export interface TeamMember {
    id: number;
    uuid: string;
    provider_id: number;
    user_id: number | null;
    email: string;
    name: string | null;
    avatar?: string | null;
    permissions: string[];
    permissions_summary?: string;
    status: TeamMemberStatus;
    status_label?: string;
    status_color?: string;
    invited_at: string | null;
    accepted_at: string | null;
    is_expired?: boolean;
    is_pending?: boolean;
    user?: User;
}

export interface TeamPermission {
    key: string;
    label: string;
    description: string;
    group: string;
}

export interface TeamPreset {
    label: string;
    description: string;
    permissions: string[];
}

export interface TeamInfo {
    tier: string;
    tier_label: string;
    supports_team: boolean;
    free_slots: number;
    active_count: number;
    extra_count: number;
    fee_per_extra: number;
    total_extra_fee: number;
    would_exceed_free_slots: boolean;
}

export interface PageProps {
    auth: {
        user: User | null;
        provider?: Provider | null;
    };
    flash?: {
        success?: string;
        error?: string;
    };
    // Required by Inertia's PageProps interface
    [key: string]: unknown;
}
