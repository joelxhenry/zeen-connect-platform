# Zeen Connect Platform - Sequential Completion Plan

## Project Overview
**Type:** Service marketplace platform (Laravel 12 + Vue 3 + Inertia.js + PrimeVue + Tailwind CSS v4)
**Purpose:** Connect clients with service providers (barbers, beauticians, photographers, etc.) in Jamaica
**Current State:** ~50% complete (Foundation, Authentication, Services, Availability & Public Discovery done)

---

## Current Implementation Status

### Completed (Phase 1 - Foundation)
- [x] User authentication (client & provider login/registration)
- [x] Role-based access control middleware
- [x] Location system (Country -> Region -> Location hierarchy)
- [x] Jamaica locations seeded (14 parishes, 50+ locations)
- [x] Provider profile management
- [x] Domain-driven architecture structure
- [x] All 4 layouts (Public, Auth, Dashboard, Console)
- [x] Landing page (Welcome.vue)
- [x] Provider dashboard with setup checklist
- [x] Ziggy route helpers

### Completed (Phase 2 - Services)
- [x] Categories migration & model (12 default categories seeded)
- [x] Services migration & model with provider/category relationships
- [x] ServiceController with full CRUD operations
- [x] CreateServiceAction & UpdateServiceAction
- [x] StoreServiceRequest & UpdateServiceRequest validation
- [x] Services Vue pages (Index, Create, Edit) with Profile Edit design patterns
- [x] Service routes added to provider console group
- [x] TypeScript types for Category and Service

### Completed (Phase 3 - Availability)
- [x] ProviderAvailability migration & model (weekly schedule with day_of_week, start/end times)
- [x] BlockedDate migration & model (specific dates unavailable)
- [x] AvailabilityController with edit, updateSchedule, updateBlockedDates
- [x] UpdateAvailabilityAction & UpdateBlockedDatesAction
- [x] UpdateAvailabilityRequest & UpdateBlockedDatesRequest validation
- [x] Availability Edit Vue page with weekly schedule editor and blocked dates manager
- [x] Availability routes added to provider console group
- [x] TypeScript types for ProviderAvailability and BlockedDate

### Completed (Phase 5 - Public Discovery)
- [x] ProviderListingController with search, filtering, and pagination
- [x] Explore page (Index.vue) with provider grid and filtering
- [x] Provider profile page (Provider.vue) with services and availability display
- [x] ProviderCard component for provider listings
- [x] FilterSidebar component with category, region, location filters
- [x] Public routes: /explore, /providers/{slug}, /become-provider

### Not Started
- [ ] Booking engine
- [ ] Payment integration (Power Tranz)
- [ ] Reviews & ratings
- [ ] Portfolio/gallery management
- [ ] Admin panel
- [ ] API layer
- [ ] Comprehensive tests

---

## Sequential Implementation Plan

### Phase 2: Service Catalog System ✅ COMPLETED
**Goal:** Enable providers to define and manage their services

#### 2.1 Database & Models ✅
- [x] Create `categories` migration (id, uuid, name, slug, icon, description, is_active)
- [x] Create `services` migration (id, uuid, provider_id, category_id, name, description, duration_minutes, price, is_active)
- [x] Create Category model with relationships
- [x] Create Service model with provider/category relationships
- [x] Seed default service categories (12 categories: Hair, Beauty, Nails, Barbering, etc.)

**Files created:**
- `database/migrations/2025_12_08_161937_create_categories_table.php`
- `database/migrations/2025_12_08_162120_create_services_table.php`
- `app/Domains/Service/Models/Category.php`
- `app/Domains/Service/Models/Service.php`
- `database/seeders/CategorySeeder.php`

#### 2.2 Provider Service Management ✅
- [x] Create ServiceController (index, create, store, edit, update, destroy)
- [x] Create service management Vue pages with Profile Edit design patterns
- [x] Services link already in provider console sidebar

**Files created:**
- `app/Domains/Provider/Controllers/ServiceController.php`
- `app/Domains/Provider/Actions/CreateServiceAction.php`
- `app/Domains/Provider/Actions/UpdateServiceAction.php`
- `app/Domains/Provider/Requests/StoreServiceRequest.php`
- `app/Domains/Provider/Requests/UpdateServiceRequest.php`
- `resources/js/pages/Provider/Services/Index.vue`
- `resources/js/pages/Provider/Services/Create.vue`
- `resources/js/pages/Provider/Services/Edit.vue`

#### 2.3 Routes ✅
- [x] Service resource routes added: `Route::resource('services', ServiceController::class)->except(['show'])`

---

### Phase 3: Provider Availability System ✅ COMPLETED
**Goal:** Allow providers to set their working hours and block dates

#### 3.1 Database & Models ✅
- [x] Create `provider_availability` migration (provider_id, day_of_week, start_time, end_time, is_available)
- [x] Create `blocked_dates` migration (provider_id, date, reason)
- [x] Create ProviderAvailability model with scopes and accessors
- [x] Create BlockedDate model with scopes
- [x] Add availability() and blockedDates() relationships to Provider model

**Files created:**
- `database/migrations/2025_12_08_163950_create_provider_availability_table.php`
- `database/migrations/2025_12_08_164023_create_blocked_dates_table.php`
- `app/Domains/Provider/Models/ProviderAvailability.php`
- `app/Domains/Provider/Models/BlockedDate.php`

#### 3.2 Availability Management UI ✅
- [x] Create AvailabilityController (edit, updateSchedule, updateBlockedDates)
- [x] Create UpdateAvailabilityAction & UpdateBlockedDatesAction
- [x] Create UpdateAvailabilityRequest & UpdateBlockedDatesRequest
- [x] Create Availability Edit Vue page with inline weekly schedule editor
- [x] Create blocked dates manager with add/remove dialog

**Files created:**
- `app/Domains/Provider/Controllers/AvailabilityController.php`
- `app/Domains/Provider/Actions/UpdateAvailabilityAction.php`
- `app/Domains/Provider/Actions/UpdateBlockedDatesAction.php`
- `app/Domains/Provider/Requests/UpdateAvailabilityRequest.php`
- `app/Domains/Provider/Requests/UpdateBlockedDatesRequest.php`
- `resources/js/pages/Provider/Availability/Edit.vue`

#### 3.3 Routes ✅
- [x] Availability routes added: `provider.availability.edit`, `provider.availability.schedule`, `provider.availability.blocked-dates`

---

### Phase 4: Portfolio & Media System
**Goal:** Let providers showcase their work

#### 4.1 Database & Models
- [ ] Create `portfolios` migration (provider_id, title, description)
- [ ] Create `media` migration (mediable_type, mediable_id, path, type, order)
- [ ] Create Portfolio model
- [ ] Create Media model with polymorphic relationship

**Files to create:**
- `database/migrations/xxxx_create_portfolios_table.php`
- `database/migrations/xxxx_create_media_table.php`
- `app/Domains/Provider/Models/Portfolio.php`
- `app/Domains/Media/Models/Media.php`

#### 4.2 Portfolio Management UI
- [ ] Create PortfolioController
- [ ] Create portfolio gallery pages with image upload
- [ ] Integrate file storage (local dev, S3 production)

**Files to create:**
- `app/Domains/Provider/Controllers/PortfolioController.php`
- `resources/js/pages/Provider/Portfolios/Index.vue`
- `resources/js/pages/Provider/Portfolios/Create.vue`
- `resources/js/components/media/ImageUploader.vue`

---

### Phase 5: Public Provider Discovery ✅ COMPLETED
**Goal:** Allow clients to browse and search providers

#### 5.1 Public Provider Pages ✅
- [x] Create ProviderListingController with index (listing) and show (profile) methods
- [x] Create public provider listing page with filters (location, category, search, sort)
- [x] Create public provider profile page (services grouped by category, availability display)
- [x] Implement search functionality with debounced input
- [x] Create ProviderCard component with ratings, categories, and service previews
- [x] Create FilterSidebar component with category, region, and location filters

**Files created:**
- `app/Http/Controllers/ProviderListingController.php`
- `resources/js/pages/Explore/Index.vue`
- `resources/js/pages/Explore/Provider.vue`
- `resources/js/components/explore/ProviderCard.vue`
- `resources/js/components/explore/FilterSidebar.vue`

#### 5.2 Routes ✅
- [x] Added public routes: `/explore`, `/providers/{slug}`, `/become-provider`

---

### Phase 6: Booking Engine
**Goal:** Core marketplace functionality - booking services

#### 6.1 Database & Models
- [ ] Create `bookings` migration (id, uuid, client_id, provider_id, service_id, date, start_time, end_time, status, total_amount, notes)
- [ ] Create Booking model with relationships and status enum

**Files to create:**
- `database/migrations/xxxx_create_bookings_table.php`
- `app/Domains/Booking/Models/Booking.php`
- `app/Domains/Booking/Enums/BookingStatus.php`

#### 6.2 Booking Flow (Client Side)
- [ ] Create booking wizard component (select date -> select time -> confirm)
- [ ] Create availability checker service
- [ ] Create client booking history page

**Files to create:**
- `app/Domains/Booking/Services/AvailabilityService.php`
- `app/Domains/Booking/Controllers/ClientBookingController.php`
- `app/Domains/Booking/Actions/CreateBookingAction.php`
- `resources/js/pages/Booking/Create.vue`
- `resources/js/pages/Client/Bookings/Index.vue`
- `resources/js/components/booking/DatePicker.vue`
- `resources/js/components/booking/TimeSlotPicker.vue`

#### 6.3 Booking Management (Provider Side)
- [ ] Create provider booking dashboard
- [ ] Implement booking status updates (confirm, complete, cancel)

**Files to create:**
- `app/Domains/Booking/Controllers/ProviderBookingController.php`
- `app/Domains/Booking/Actions/UpdateBookingStatusAction.php`
- `resources/js/pages/Provider/Bookings/Index.vue`
- `resources/js/pages/Provider/Bookings/Show.vue`

---

### Phase 7: Payment Integration
**Goal:** Process payments via Power Tranz gateway

#### 7.1 Database & Models
- [ ] Create `payments` migration (booking_id, amount, gateway_reference, status, paid_at)
- [ ] Create `payouts` migration (provider_id, amount, status, processed_at)
- [ ] Create Payment and Payout models

**Files to create:**
- `database/migrations/xxxx_create_payments_table.php`
- `database/migrations/xxxx_create_payouts_table.php`
- `app/Domains/Payment/Models/Payment.php`
- `app/Domains/Payment/Models/Payout.php`

#### 7.2 Payment Processing
- [ ] Create PowerTranz gateway service
- [ ] Implement payment processing flow
- [ ] Handle webhooks for payment status
- [ ] Create payment confirmation pages

**Files to create:**
- `app/Domains/Payment/Services/PowerTranzGateway.php`
- `app/Domains/Payment/Controllers/PaymentController.php`
- `app/Domains/Payment/Controllers/WebhookController.php`
- `resources/js/pages/Payment/Checkout.vue`
- `resources/js/pages/Payment/Success.vue`

#### 7.3 Provider Earnings
- [ ] Create earnings dashboard for providers
- [ ] Implement commission calculation (15%)

**Files to create:**
- `app/Domains/Payment/Controllers/ProviderEarningsController.php`
- `resources/js/pages/Provider/Payments/Index.vue`

---

### Phase 8: Reviews & Ratings
**Goal:** Build trust through client feedback

#### 8.1 Database & Models
- [ ] Create `reviews` migration (booking_id, client_id, provider_id, rating, comment, created_at)
- [ ] Create Review model
- [ ] Implement rating aggregation on Provider model

**Files to create:**
- `database/migrations/xxxx_create_reviews_table.php`
- `app/Domains/Review/Models/Review.php`
- `app/Domains/Review/Actions/CreateReviewAction.php`

#### 8.2 Review UI
- [ ] Add review submission after completed bookings
- [ ] Display reviews on provider profile
- [ ] Update provider rating calculation

**Files to create:**
- `app/Domains/Review/Controllers/ReviewController.php`
- `resources/js/pages/Client/Reviews/Create.vue`
- `resources/js/components/reviews/ReviewList.vue`
- `resources/js/components/reviews/StarRating.vue`

---

### Phase 9: Notifications
**Goal:** Keep users informed about booking events

#### 9.1 Email Notifications
- [ ] Create booking confirmation email
- [ ] Create booking reminder email
- [ ] Create booking status change emails
- [ ] Create review request email

**Files to create:**
- `app/Mail/BookingConfirmed.php`
- `app/Mail/BookingReminder.php`
- `app/Mail/BookingStatusChanged.php`
- `app/Mail/ReviewRequest.php`
- `resources/views/emails/` templates

#### 9.2 In-App Notifications
- [ ] Create notifications table migration
- [ ] Create notification bell component
- [ ] Implement notification marking as read

---

### Phase 10: Client Dashboard Enhancement
**Goal:** Complete client-side features

#### 10.1 Client Profile
- [ ] Create client profile edit page
- [ ] Add preferred location and settings

**Files to create:**
- `app/Domains/Client/Controllers/ProfileController.php`
- `resources/js/pages/Client/Profile/Edit.vue`

#### 10.2 Client Dashboard
- [ ] Enhance dashboard with upcoming bookings
- [ ] Add recent activity feed
- [ ] Show favorite providers

**Files to modify:**
- `resources/js/pages/Client/Dashboard.vue`

---

### Phase 11: Admin Panel
**Goal:** Platform management and oversight

#### 11.1 Admin Dashboard
- [ ] Create admin dashboard with key metrics
- [ ] User management (view, activate, deactivate)
- [ ] Provider verification workflow
- [ ] Booking oversight
- [ ] Payment/payout management

**Files to create:**
- `app/Domains/Admin/Controllers/DashboardController.php`
- `app/Domains/Admin/Controllers/UserController.php`
- `app/Domains/Admin/Controllers/ProviderController.php`
- `resources/js/pages/Admin/Dashboard.vue`
- `resources/js/pages/Admin/Users/Index.vue`
- `resources/js/pages/Admin/Providers/Index.vue`
- `resources/js/components/layout/AdminLayout.vue`

---

### Phase 12: API Layer
**Goal:** Enable mobile app and third-party integrations

#### 12.1 API Authentication
- [ ] Configure Sanctum for API tokens
- [ ] Create API authentication endpoints

#### 12.2 API Endpoints (v1)
- [ ] Providers endpoints (list, show, services)
- [ ] Bookings endpoints (create, list, show, update)
- [ ] Client endpoints (profile, bookings, reviews)

**Files to create:**
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/V1/ProviderController.php`
- `app/Http/Controllers/Api/V1/BookingController.php`
- `app/Http/Controllers/Api/V1/ClientController.php`

---

### Phase 13: Testing & Quality
**Goal:** Ensure reliability and maintainability

#### 13.1 Unit Tests
- [ ] Model tests (relationships, scopes, methods)
- [ ] Action tests (business logic)
- [ ] Service tests (availability, payment gateway)

#### 13.2 Feature Tests
- [ ] Authentication flow tests
- [ ] Booking flow tests
- [ ] Payment flow tests
- [ ] API endpoint tests

#### 13.3 Browser Tests (Optional)
- [ ] Critical user journeys with Laravel Dusk

**Target:** 80%+ code coverage

---

### Phase 14: Performance & Polish
**Goal:** Production readiness

#### 14.1 Performance
- [ ] Add database indexes where needed
- [ ] Implement query optimization
- [ ] Add caching (Redis) for frequent queries
- [ ] Optimize image loading

#### 14.2 UI Polish
- [ ] Loading states for all async operations
- [ ] Error handling and user feedback
- [ ] Mobile responsiveness audit
- [ ] Accessibility improvements

#### 14.3 Security
- [ ] Input validation audit
- [ ] CSRF protection verification
- [ ] Rate limiting on sensitive endpoints
- [ ] Security headers configuration

---

## Recommended Execution Order

1. **Phase 2** (Services) - Core feature, enables provider value
2. **Phase 3** (Availability) - Required for booking
3. **Phase 5** (Discovery) - Enables client browsing
4. **Phase 6** (Booking) - Core marketplace function
5. **Phase 7** (Payments) - Monetization
6. **Phase 8** (Reviews) - Trust building
7. **Phase 4** (Portfolio) - Enhancement
8. **Phase 9** (Notifications) - User engagement
9. **Phase 10** (Client Dashboard) - Client experience
10. **Phase 11** (Admin) - Platform management
11. **Phase 12** (API) - Mobile/integrations
12. **Phase 13** (Testing) - Quality assurance
13. **Phase 14** (Polish) - Production prep

---

## Key Files Reference

### Existing Structure
- Routes: `routes/web.php`
- Auth Controllers: `app/Domains/Auth/Controllers/`
- Provider Controllers: `app/Domains/Provider/Controllers/`
- Models: `app/Domains/*/Models/`
- Vue Pages: `resources/js/pages/`
- Layouts: `resources/js/components/layout/`
- Types: `resources/js/types/`

### Configuration
- Main config: `config/app.php`
- Database: `config/database.php`
- Existing architecture doc: `plan.md`
