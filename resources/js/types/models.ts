import type { Provider, MediaItem, VideoEmbed } from './models/index';

// Re-export centralized types for backward compatibility
export type { Provider, MediaItem, VideoEmbed };

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
    gallery?: MediaItem[];
    videos?: VideoEmbed[];
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
