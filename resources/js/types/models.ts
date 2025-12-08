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
    location?: string;
    website?: string;
    social_links?: Record<string, string>;
    status: 'pending' | 'verified' | 'suspended';
    commission_rate: number;
    average_rating: number;
    total_reviews: number;
    total_bookings: number;
    is_featured: boolean;
    verified_at?: string;
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

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}
