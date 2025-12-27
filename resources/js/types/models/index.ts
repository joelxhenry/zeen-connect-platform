/**
 * Centralized model type exports.
 *
 * New types should be added to individual files in this directory.
 * Legacy types from ../models.ts are re-exported here for compatibility.
 */

// New centralized types
export * from './booking';
export * from './review';
export * from './subscription';
export * from './payment';
export * from './media';
export * from './provider';
export * from './service';

// Re-export legacy types from models.ts (will be migrated incrementally)
export type {
    User,
    Client,
    ProviderAvailability,
    BlockedDate,
    PageProps,
} from '../models';
