// Media item from MediaResource
export interface MediaItem {
    id: number;
    uuid: string;
    collection: string;
    filename: string;
    mime_type: string;
    size: number;
    human_size: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    is_image: boolean;
    order: number;
}

// Video embed platforms
export type VideoPlatform = 'youtube' | 'vimeo';

// Video embed from VideoEmbedResource
export interface VideoEmbed {
    id: number;
    uuid: string;
    platform: VideoPlatform;
    video_id: string;
    url: string;
    embed_url: string;
    embed_code: string;
    watch_url: string;
    title: string | null;
    thumbnail_url: string | null;
    duration: number | null;
    human_duration: string | null;
    order: number;
}

// Gallery with media items
export interface Gallery {
    items: MediaItem[];
    total: number;
}

// Upload progress tracking
export interface MediaUploadProgress {
    uuid: string;
    filename: string;
    progress: number;
    status: 'pending' | 'uploading' | 'processing' | 'complete' | 'error';
    error?: string;
}
