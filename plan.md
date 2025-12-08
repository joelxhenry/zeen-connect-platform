# PROJECT ARCHITECTURE & FUNCTIONAL REQUIREMENTS PROMPT
## Project Name: Zeen (Lean Monolith Edition)

You are an expert software architect, product manager, and Laravel engineer.

Your objective is to produce:
1. A complete **System Architecture** for Zeen
2. A well-structured **Functional Requirements Document (FRD)**
3. A practical **MVP implementation roadmap**
4. A modular **Laravel project structure**
5. API & UI flow diagrams (ASCII-style if needed)

This is a **single monolithic application** (not microservices).

---

## 1. PROJECT VISION

Zeen is a service marketplace platform allowing clients to book service providers such as:
- Barbers
- Beauticians
- Nail technicians
- Photographers
- Instructors
- Freelancers

The system must support:
- Provider onboarding
- Provider portfolio pages
- Client bookings
- One payment flow
- Provider dashboard
- Public marketing site
- Public API (for future apps/integrations)

The main goal is:
> Build a lean, scalable monolith that can ship fast, generate revenue early, and evolve without overengineering.

---

## 2. ARCHITECTURE CONSTRAINTS

MANDATORY:
- Must be a **single Laravel monolith**
- Modular internal structure (Domain-based folders)
- One relational database
- No microservices
- No message brokers at MVP stage
- API and web app live in same codebase
- Must allow future refactoring into services if needed

OPTIONAL:
- Redis for cache/queues
- Object storage (S3-compatible)
- Payment gateway abstraction

---

## 3. CORE MODULES (YOU MUST MODEL ALL)

### 3.1 PUBLIC MARKETING MODULE

**Purpose:**
Serve as the public-facing storefront for Zeen, attracting both service providers and clients through optimized landing pages, service discovery, and lead capture mechanisms.

**Actors:**
| Actor | Description |
|-------|-------------|
| Anonymous Visitor | Unauthenticated user browsing the platform |
| Potential Provider | Service provider researching the platform |
| Potential Client | User looking to book services |
| Search Engine Bot | Crawlers indexing content for SEO |

**Functional Use Cases:**

| UC-PM-01 | View Landing Page |
|----------|-------------------|
| Description | User views the main marketing landing page |
| Preconditions | None |
| Flow | 1. User navigates to root URL<br>2. System displays hero section, featured providers, service categories, testimonials, CTA buttons |
| Postconditions | Page analytics event recorded |

| UC-PM-02 | Browse Service Categories |
|----------|--------------------------|
| Description | User explores available service types |
| Preconditions | None |
| Flow | 1. User clicks "Services" or browses categories<br>2. System displays category grid with icons, descriptions, provider counts<br>3. User can filter by location |
| Postconditions | Category view event logged |

| UC-PM-03 | View Provider Recruitment Page |
|----------|-------------------------------|
| Description | Potential provider learns about joining Zeen |
| Preconditions | None |
| Flow | 1. User clicks "Become a Provider"<br>2. System displays benefits, commission structure, onboarding steps, success stories<br>3. User can click CTA to register |
| Postconditions | Recruitment page view tracked |

| UC-PM-04 | Search Providers |
|----------|-----------------|
| Description | User searches for providers by name, service, or location |
| Preconditions | None |
| Flow | 1. User enters search query<br>2. System returns matching providers with thumbnails, ratings, services offered<br>3. Results can be filtered and sorted |
| Postconditions | Search query logged for analytics |

| UC-PM-05 | Submit Lead Capture Form |
|----------|-------------------------|
| Description | User submits contact information for follow-up |
| Preconditions | None |
| Flow | 1. User fills out form (name, email, interest type)<br>2. System validates input<br>3. System stores lead and triggers notification<br>4. User sees confirmation message |
| Postconditions | Lead stored, welcome email queued |

**Validation Rules:**
- Email must be valid format and unique in leads table
- Phone must match E.164 format (optional)
- Search queries sanitized and limited to 100 characters
- Location coordinates validated against Jamaica bounds

**Access Rules:**
- All pages publicly accessible
- No authentication required
- Rate limiting: 100 requests/minute per IP

**Error Handling:**
| Error | Response |
|-------|----------|
| Invalid email format | Display inline validation error |
| Duplicate lead submission | Show "Already registered" message with login link |
| Search returns no results | Display "No providers found" with suggestions |
| Server error | Show friendly error page with retry option |

**Future Extensions:**
- A/B testing framework for landing page variants
- Personalized recommendations based on browsing history
- Multi-language support (Spanish for Caribbean market)
- Blog/content marketing section
- Referral tracking for providers

---

### 3.2 PROVIDER CONSOLE MODULE

**Purpose:**
Empower service providers to manage their business presence, services, bookings, and earnings through a comprehensive dashboard interface.

**Actors:**
| Actor | Description |
|-------|-------------|
| Provider | Registered service provider with approved account |
| Provider (Pending) | Provider awaiting approval |
| System | Automated processes (notifications, reminders) |

**Functional Use Cases:**

| UC-PC-01 | Provider Registration |
|----------|----------------------|
| Description | New provider creates an account |
| Preconditions | Valid email, not already registered |
| Flow | 1. Provider fills registration form<br>2. System validates input<br>3. System creates user with role=provider<br>4. System creates provider profile with status=pending<br>5. Provider receives verification email<br>6. Admin notified of new registration |
| Postconditions | Provider account created, pending approval |

| UC-PC-02 | Complete Provider Profile |
|----------|--------------------------|
| Description | Provider completes their business profile |
| Preconditions | Provider authenticated, account exists |
| Flow | 1. Provider accesses profile settings<br>2. Provider enters: business name, bio, location, contact info, social links<br>3. Provider uploads profile photo<br>4. System validates and saves<br>5. System generates URL slug from business name |
| Postconditions | Profile updated, slug created |

| UC-PC-03 | Manage Services (CRUD) |
|----------|----------------------|
| Description | Provider creates, updates, or removes services |
| Preconditions | Provider authenticated, profile complete |
| Flow | 1. Provider navigates to Services section<br>2. Provider clicks "Add Service"<br>3. Provider enters: name, description, category, duration, price, deposit amount<br>4. Provider can set service as active/inactive<br>5. System validates and saves |
| Postconditions | Service created/updated in database |

| UC-PC-04 | Manage Availability |
|----------|---------------------|
| Description | Provider sets working hours and blocked dates |
| Preconditions | Provider authenticated |
| Flow | 1. Provider accesses Availability section<br>2. Provider sets weekly schedule (day, start_time, end_time)<br>3. Provider can add blocked dates (vacations, holidays)<br>4. Provider sets booking buffer time<br>5. System saves availability rules |
| Postconditions | Availability stored, affects booking slots |

| UC-PC-05 | Manage Portfolio |
|----------|-----------------|
| Description | Provider uploads and organizes work samples |
| Preconditions | Provider authenticated |
| Flow | 1. Provider accesses Portfolio section<br>2. Provider uploads images/videos<br>3. Provider adds captions, tags, service associations<br>4. Provider can reorder items<br>5. Provider can delete items |
| Postconditions | Media stored in S3, records in database |

| UC-PC-06 | View Booking Requests |
|----------|---------------------|
| Description | Provider views and manages incoming bookings |
| Preconditions | Provider authenticated |
| Flow | 1. Provider views booking list with filters (date, status)<br>2. Provider can view booking details<br>3. Provider can confirm pending bookings<br>4. Provider can cancel bookings (with reason)<br>5. Provider can mark bookings complete |
| Postconditions | Booking status updated, notifications sent |

| UC-PC-07 | View Earnings Dashboard |
|----------|------------------------|
| Description | Provider views payment history and earnings |
| Preconditions | Provider authenticated |
| Flow | 1. Provider accesses Earnings section<br>2. System displays: total earnings, pending payouts, completed transactions<br>3. Provider can filter by date range<br>4. Provider can view transaction details |
| Postconditions | None (read-only) |

| UC-PC-08 | Update Account Settings |
|----------|------------------------|
| Description | Provider manages account preferences |
| Preconditions | Provider authenticated |
| Flow | 1. Provider accesses Settings<br>2. Provider can update: email, password, notification preferences<br>3. Provider can enable/disable booking notifications<br>4. System validates and saves |
| Postconditions | Settings updated |

**Validation Rules:**
- Business name: 2-100 characters, unique slug generated
- Bio: max 1000 characters
- Service name: 2-100 characters
- Service price: positive decimal, max 999,999.99
- Service duration: 15-480 minutes (in 15-minute increments)
- Deposit: 0-100% of service price
- Portfolio images: max 10MB, jpg/png/webp
- Portfolio videos: max 100MB, mp4/mov
- Working hours: start_time < end_time
- Phone: E.164 format

**Access Rules:**
- All endpoints require authentication with role=provider
- Providers can only access their own data
- Pending providers have limited access (profile only)
- Suspended providers redirected to support page

**Error Handling:**
| Error | Response |
|-------|----------|
| Duplicate business name | Suggest alternative slug |
| Invalid file type | Display allowed formats |
| File too large | Display size limit |
| Schedule conflict | Highlight conflicting slots |
| Service has active bookings | Prevent deletion, offer deactivation |

**Future Extensions:**
- Team member management (staff accounts)
- Multiple locations support
- Service packages/bundles
- Promotional pricing/discounts
- Analytics dashboard (views, conversion rates)
- Calendar sync (Google Calendar, Apple)
- Automated reminders to clients
- Waitlist management

---

### 3.3 CLIENT SYSTEM MODULE

**Purpose:**
Enable clients to discover providers, view portfolios, book services, and manage their appointments through an intuitive interface.

**Actors:**
| Actor | Description |
|-------|-------------|
| Guest | Unauthenticated user browsing providers |
| Client | Registered and authenticated user |
| System | Automated processes (reminders, confirmations) |

**Functional Use Cases:**

| UC-CS-01 | Client Registration |
|----------|---------------------|
| Description | New client creates an account |
| Preconditions | Valid email, not already registered |
| Flow | 1. Client fills registration form (name, email, password, phone)<br>2. System validates input<br>3. System creates user with role=client<br>4. System creates client profile<br>5. Client receives welcome email<br>6. Client redirected to dashboard |
| Postconditions | Client account active |

| UC-CS-02 | Client Login |
|----------|--------------|
| Description | Client authenticates to access account |
| Preconditions | Client has registered account |
| Flow | 1. Client enters email and password<br>2. System validates credentials<br>3. System creates session<br>4. Client redirected to dashboard |
| Postconditions | Session created, last_login_at updated |

| UC-CS-03 | Browse Providers |
|----------|------------------|
| Description | Client explores available providers |
| Preconditions | None (works for guests) |
| Flow | 1. Client navigates to provider listing<br>2. System displays providers with: photo, name, rating, services, location<br>3. Client can filter by: category, location, price range, rating<br>4. Client can sort by: rating, distance, price<br>5. Results paginated (12 per page) |
| Postconditions | Browse event logged |

| UC-CS-04 | View Provider Profile |
|----------|----------------------|
| Description | Client views detailed provider page |
| Preconditions | Provider exists and is active |
| Flow | 1. Client clicks on provider<br>2. System displays: profile info, services list, portfolio gallery, reviews, availability calendar<br>3. Client can click "Book Now" on any service |
| Postconditions | Profile view logged |

| UC-CS-05 | View Portfolio Gallery |
|----------|----------------------|
| Description | Client browses provider's work samples |
| Preconditions | Provider has portfolio items |
| Flow | 1. Client clicks on portfolio section<br>2. System displays gallery grid<br>3. Client can click to enlarge images<br>4. Client can navigate between items<br>5. Client sees associated service for each item |
| Postconditions | None |

| UC-CS-06 | Book a Service |
|----------|----------------|
| Description | Client books an appointment with provider |
| Preconditions | Client authenticated, service available |
| Flow | 1. Client selects service<br>2. System displays available time slots based on provider availability<br>3. Client selects date and time<br>4. Client adds optional notes<br>5. System validates slot still available<br>6. System creates booking with status=pending<br>7. Client redirected to payment (if deposit required) or confirmation |
| Postconditions | Booking created, provider notified |

| UC-CS-07 | View My Bookings |
|----------|------------------|
| Description | Client views their booking history |
| Preconditions | Client authenticated |
| Flow | 1. Client accesses "My Bookings"<br>2. System displays bookings grouped by: upcoming, past, cancelled<br>3. Client can view booking details<br>4. Client can cancel upcoming bookings |
| Postconditions | None |

| UC-CS-08 | Cancel Booking |
|----------|----------------|
| Description | Client cancels an upcoming booking |
| Preconditions | Client authenticated, booking is upcoming |
| Flow | 1. Client clicks "Cancel" on booking<br>2. System displays cancellation policy and refund info<br>3. Client confirms cancellation<br>4. System updates booking status to cancelled<br>5. System processes refund if applicable<br>6. Provider notified |
| Postconditions | Booking cancelled, refund initiated |

| UC-CS-09 | Submit Review |
|----------|---------------|
| Description | Client reviews a completed service |
| Preconditions | Booking completed, no existing review |
| Flow | 1. Client receives review request (email or in-app)<br>2. Client submits: rating (1-5), comment<br>3. System validates input<br>4. System stores review<br>5. System updates provider rating average |
| Postconditions | Review stored, rating updated |

| UC-CS-10 | Update Profile |
|----------|----------------|
| Description | Client updates account information |
| Preconditions | Client authenticated |
| Flow | 1. Client accesses profile settings<br>2. Client updates: name, phone, avatar, preferences<br>3. System validates and saves |
| Postconditions | Profile updated |

**Validation Rules:**
- Name: 2-100 characters
- Email: valid format, unique
- Password: min 8 characters, 1 uppercase, 1 number
- Phone: E.164 format (required for booking)
- Review rating: integer 1-5
- Review comment: 10-1000 characters
- Booking notes: max 500 characters

**Access Rules:**
- Browsing providers: public access
- Booking: requires authentication
- Viewing own bookings: authenticated, own data only
- Submitting reviews: authenticated, must have completed booking

**Error Handling:**
| Error | Response |
|-------|----------|
| Email already registered | Show login link |
| Invalid credentials | Generic "invalid email or password" |
| Time slot unavailable | Show alternative slots |
| Booking already cancelled | Show error message |
| Review already submitted | Show existing review |
| Provider unavailable | Show message, suggest alternatives |

**Future Extensions:**
- Social login (Google, Facebook, Apple)
- Favorite providers list
- Booking reminders (SMS, push notifications)
- Rebooking previous services
- Gift cards/vouchers
- Loyalty points program
- Booking on behalf of others
- Group bookings

---

### 3.4 BOOKING ENGINE MODULE

**Purpose:**
Core business logic for managing the entire booking lifecycle from availability calculation through completion, including conflict resolution and notifications.

**Actors:**
| Actor | Description |
|-------|-------------|
| Client | User making bookings |
| Provider | Service provider managing bookings |
| System | Automated processes (expiration, reminders) |
| Admin | Platform administrator (dispute resolution) |

**Functional Use Cases:**

| UC-BE-01 | Calculate Available Slots |
|----------|--------------------------|
| Description | System determines bookable time slots |
| Preconditions | Provider has set availability |
| Flow | 1. System receives request for provider + date range<br>2. System fetches provider's weekly schedule<br>3. System fetches blocked dates<br>4. System fetches existing bookings<br>5. System calculates open slots based on service duration<br>6. System applies buffer time between bookings<br>7. System returns available slots |
| Postconditions | None |

| UC-BE-02 | Create Booking |
|----------|----------------|
| Description | System creates a new booking |
| Preconditions | Slot available, client authenticated |
| Flow | 1. System receives booking request<br>2. System validates slot availability (race condition check)<br>3. System creates booking record with status=pending<br>4. System calculates amounts (service price, deposit, platform fee)<br>5. If deposit required, system redirects to payment<br>6. If no deposit, system confirms booking<br>7. System sends notifications to client and provider |
| Postconditions | Booking created, notifications sent |

| UC-BE-03 | Confirm Booking |
|----------|-----------------|
| Description | Booking transitions to confirmed status |
| Preconditions | Booking exists, payment complete (if required) |
| Flow | 1. System receives confirmation trigger (payment success or manual)<br>2. System updates booking status to confirmed<br>3. System sends confirmation notifications<br>4. System schedules reminder notifications |
| Postconditions | Booking confirmed, reminders scheduled |

| UC-BE-04 | Cancel Booking (Client) |
|----------|------------------------|
| Description | Client cancels their booking |
| Preconditions | Booking status is pending or confirmed |
| Flow | 1. Client requests cancellation<br>2. System checks cancellation policy<br>3. System calculates refund amount based on policy<br>4. System updates booking status to cancelled<br>5. System initiates refund if applicable<br>6. System sends cancellation notifications<br>7. System frees up the time slot |
| Postconditions | Booking cancelled, slot available |

| UC-BE-05 | Cancel Booking (Provider) |
|----------|--------------------------|
| Description | Provider cancels a booking |
| Preconditions | Booking status is pending or confirmed |
| Flow | 1. Provider requests cancellation with reason<br>2. System updates booking status to cancelled<br>3. System initiates full refund<br>4. System sends notifications with reason<br>5. System logs cancellation for provider metrics |
| Postconditions | Booking cancelled, full refund, metrics updated |

| UC-BE-06 | Complete Booking |
|----------|------------------|
| Description | Booking marked as service delivered |
| Preconditions | Booking confirmed, scheduled time passed |
| Flow | 1. Provider marks booking as complete<br>2. System updates status to completed<br>3. System calculates final amounts<br>4. System triggers review request to client<br>5. System updates provider earnings |
| Postconditions | Booking completed, review requested |

| UC-BE-07 | Handle Booking Expiration |
|----------|--------------------------|
| Description | System auto-cancels stale pending bookings |
| Preconditions | Booking pending, payment not received |
| Flow | 1. Scheduled job runs every 15 minutes<br>2. System finds pending bookings older than 30 minutes<br>3. System cancels expired bookings<br>4. System frees up time slots<br>5. System notifies client of expiration |
| Postconditions | Expired bookings cancelled |

| UC-BE-08 | Send Booking Reminders |
|----------|----------------------|
| Description | System sends automated reminders |
| Preconditions | Booking confirmed, reminder not sent |
| Flow | 1. Scheduled job runs hourly<br>2. System finds bookings within reminder window (24h, 2h before)<br>3. System sends email/SMS reminders to clients<br>4. System marks reminder as sent |
| Postconditions | Reminders sent |

| UC-BE-09 | Handle No-Show |
|----------|----------------|
| Description | Provider marks client as no-show |
| Preconditions | Booking confirmed, scheduled time passed |
| Flow | 1. Provider marks booking as no-show<br>2. System updates status to no_show<br>3. System retains deposit (no refund)<br>4. System logs no-show for client metrics<br>5. System notifies client |
| Postconditions | No-show recorded, deposit retained |

**Booking Status State Machine:**
```
                    ┌──────────────────────────────────────┐
                    │                                      │
                    ▼                                      │
┌─────────┐    ┌─────────┐    ┌───────────┐    ┌──────────┴──┐
│ PENDING │───▶│CONFIRMED│───▶│ COMPLETED │    │  CANCELLED  │
└────┬────┘    └────┬────┘    └───────────┘    └─────────────┘
     │              │                                  ▲
     │              │         ┌───────────┐            │
     │              └────────▶│  NO_SHOW  │            │
     │                        └───────────┘            │
     │                                                 │
     └────────────────────────────────────────────────┘
          (expired or cancelled)
```

**Validation Rules:**
- Booking date must be in the future (min 2 hours ahead)
- Booking date must be within booking window (max 60 days ahead)
- Time slot must align with provider's schedule
- Time slot must not conflict with existing bookings
- Service must be active
- Provider must be active
- Client must have verified phone for booking

**Access Rules:**
- Create booking: authenticated client
- View booking: owner (client or provider)
- Confirm booking: provider or system (after payment)
- Cancel booking: client, provider, or admin
- Complete booking: provider only
- Mark no-show: provider only

**Error Handling:**
| Error | Response |
|-------|----------|
| Slot no longer available | Show alternative slots |
| Provider unavailable | Return to provider selection |
| Payment timeout | Cancel booking, free slot |
| Double booking attempt | Reject second booking |
| Invalid status transition | Log error, notify admin |

**Future Extensions:**
- Recurring bookings (weekly appointments)
- Waitlist for fully booked slots
- Dynamic pricing (peak hours)
- Multi-service bookings in single session
- Travel time calculation between locations
- Overbooking with confirmation
- Deposit-only vs full-payment modes
- Booking modifications (reschedule)

---

### 3.5 PAYMENT PORTAL MODULE

**Purpose:**
Handle all financial transactions including payment processing, refunds, commission calculations, and provider payouts through Power Tranz gateway integration.

**Actors:**
| Actor | Description |
|-------|-------------|
| Client | User making payments |
| Provider | Service provider receiving payments |
| System | Automated payment processes |
| Admin | Platform administrator (refunds, disputes) |
| Power Tranz | Payment gateway (webhooks) |

**Functional Use Cases:**

| UC-PP-01 | Process Booking Payment |
|----------|------------------------|
| Description | Client pays deposit or full amount for booking |
| Preconditions | Booking created, payment required |
| Flow | 1. System calculates payment amount<br>2. System creates payment record with status=pending<br>3. System redirects to Power Tranz hosted page<br>4. Client enters card details on Power Tranz<br>5. Power Tranz processes payment<br>6. Power Tranz redirects to callback URL<br>7. System verifies payment via webhook<br>8. System updates payment and booking status |
| Postconditions | Payment recorded, booking confirmed |

| UC-PP-02 | Handle Payment Webhook |
|----------|----------------------|
| Description | System processes Power Tranz webhook notifications |
| Preconditions | Valid webhook signature |
| Flow | 1. System receives webhook POST<br>2. System validates signature<br>3. System parses payment result<br>4. System updates payment record<br>5. If successful, system confirms booking<br>6. If failed, system marks payment failed<br>7. System sends appropriate notifications |
| Postconditions | Payment status synced |

| UC-PP-03 | Process Refund |
|----------|----------------|
| Description | System refunds payment to client |
| Preconditions | Payment completed, refund applicable |
| Flow | 1. System determines refund amount based on policy<br>2. System creates refund record<br>3. System calls Power Tranz refund API<br>4. System updates payment record<br>5. System notifies client of refund |
| Postconditions | Refund processed |

| UC-PP-04 | Calculate Commission |
|----------|---------------------|
| Description | System calculates platform commission on booking |
| Preconditions | Booking completed |
| Flow | 1. System retrieves booking total<br>2. System retrieves provider's commission rate<br>3. System calculates: platform_fee = total × commission_rate<br>4. System calculates: provider_amount = total - platform_fee<br>5. System records commission in payment |
| Postconditions | Commission calculated |

| UC-PP-05 | Mark Payment Manual (Offline) |
|----------|------------------------------|
| Description | Admin marks payment as received offline |
| Preconditions | Admin authenticated, booking exists |
| Flow | 1. Admin accesses booking<br>2. Admin clicks "Mark Paid Manually"<br>3. Admin enters payment details (method, reference)<br>4. System creates payment record with gateway=manual<br>5. System confirms booking |
| Postconditions | Payment recorded, booking confirmed |

| UC-PP-06 | View Payment History |
|----------|---------------------|
| Description | User views their payment transactions |
| Preconditions | User authenticated |
| Flow | 1. User accesses payment history<br>2. System displays transactions with: date, amount, status, booking reference<br>3. User can filter by date range, status<br>4. User can view transaction details |
| Postconditions | None |

| UC-PP-07 | Generate Provider Payout Report |
|----------|--------------------------------|
| Description | System generates payout summary for provider |
| Preconditions | Provider authenticated, completed bookings exist |
| Flow | 1. Provider accesses earnings<br>2. System aggregates completed payments<br>3. System calculates: total earnings, commission deducted, net payout<br>4. Provider can export report (CSV/PDF) |
| Postconditions | Report generated |

**Payment Status State Machine:**
```
┌─────────┐    ┌────────────┐    ┌───────────┐
│ PENDING │───▶│ PROCESSING │───▶│ COMPLETED │
└────┬────┘    └─────┬──────┘    └─────┬─────┘
     │               │                 │
     │               ▼                 ▼
     │         ┌──────────┐      ┌──────────┐
     └────────▶│  FAILED  │      │ REFUNDED │
               └──────────┘      └──────────┘
```

**Validation Rules:**
- Payment amount must be positive
- Payment amount must match booking total (or deposit)
- Refund cannot exceed original payment
- Webhook signature must be valid
- Transaction reference must be unique

**Access Rules:**
- Process payment: authenticated client (own booking)
- View payment: owner (client or provider)
- Process refund: system or admin
- Mark manual payment: admin only
- View provider earnings: provider (own data)

**Error Handling:**
| Error | Response |
|-------|----------|
| Payment declined | Show error, retry option |
| Invalid card | Show specific card error |
| Gateway timeout | Retry with exponential backoff |
| Invalid webhook | Log and ignore |
| Duplicate transaction | Return existing result |
| Refund failed | Queue for manual review |

**Commission Structure:**
| Provider Type | Commission Rate |
|---------------|-----------------|
| Standard | 15% |
| Premium | 10% |
| New Provider (first 3 months) | 10% |

**Cancellation & Refund Policy:**
| Cancellation Time | Refund Amount |
|-------------------|---------------|
| > 24 hours before | 100% |
| 12-24 hours before | 50% |
| < 12 hours before | 0% (deposit retained) |
| Provider cancels | 100% always |

**Future Extensions:**
- Multiple payment gateways (fallback)
- Subscription payments for providers
- Split payments (deposit + balance)
- Tipping functionality
- Payment plans/installments
- Automatic provider payouts (weekly/monthly)
- Invoice generation
- Tax calculation and reporting
- Multi-currency support (USD, JMD)

---

### 3.6 PROVIDER PORTFOLIO MODULE

**Purpose:**
Enable providers to showcase their work through rich media galleries, enhancing their public profiles and driving booking conversions.

**Actors:**
| Actor | Description |
|-------|-------------|
| Provider | Creates and manages portfolio |
| Client | Views portfolio content |
| Guest | Views public portfolio |
| System | Processes and optimizes media |

**Functional Use Cases:**

| UC-PF-01 | Upload Portfolio Item |
|----------|----------------------|
| Description | Provider uploads image or video to portfolio |
| Preconditions | Provider authenticated, under storage limit |
| Flow | 1. Provider accesses portfolio manager<br>2. Provider selects file(s) to upload<br>3. System validates file type and size<br>4. System uploads to S3 storage<br>5. System generates thumbnails (multiple sizes)<br>6. System creates portfolio_item record<br>7. Provider adds title, description, tags |
| Postconditions | Media stored, thumbnails generated |

| UC-PF-02 | Edit Portfolio Item |
|----------|---------------------|
| Description | Provider updates portfolio item metadata |
| Preconditions | Provider authenticated, owns item |
| Flow | 1. Provider selects portfolio item<br>2. Provider updates: title, description, tags, service association<br>3. System validates and saves |
| Postconditions | Item metadata updated |

| UC-PF-03 | Delete Portfolio Item |
|----------|----------------------|
| Description | Provider removes item from portfolio |
| Preconditions | Provider authenticated, owns item |
| Flow | 1. Provider clicks delete on item<br>2. System confirms deletion<br>3. System soft-deletes record<br>4. System queues media file deletion |
| Postconditions | Item hidden, media cleanup queued |

| UC-PF-04 | Reorder Portfolio Items |
|----------|------------------------|
| Description | Provider arranges portfolio display order |
| Preconditions | Provider authenticated |
| Flow | 1. Provider accesses portfolio manager<br>2. Provider drags items to reorder<br>3. System updates sort_order values |
| Postconditions | Order saved |

| UC-PF-05 | View Portfolio Gallery (Public) |
|----------|--------------------------------|
| Description | Visitor views provider's portfolio |
| Preconditions | Provider profile is public |
| Flow | 1. Visitor navigates to provider profile<br>2. System displays portfolio grid with thumbnails<br>3. Visitor clicks item for lightbox view<br>4. Visitor can navigate between items<br>5. Visitor sees "Book Now" CTA |
| Postconditions | View logged for analytics |

| UC-PF-06 | Associate Item with Service |
|----------|----------------------------|
| Description | Provider links portfolio item to service |
| Preconditions | Provider authenticated, service exists |
| Flow | 1. Provider selects portfolio item<br>2. Provider chooses associated service<br>3. System creates association |
| Postconditions | Item linked to service |

**Media Processing Pipeline:**
```
┌──────────┐    ┌───────────┐    ┌────────────┐    ┌───────────┐
│  Upload  │───▶│  Validate │───▶│  Store S3  │───▶│ Generate  │
│          │    │           │    │            │    │ Thumbnails│
└──────────┘    └───────────┘    └────────────┘    └─────┬─────┘
                                                        │
                     ┌────────────────────────────────┐
                     │                                │
               ┌─────▼─────┐  ┌──────────┐  ┌────────▼────────┐
               │  thumb_sm │  │ thumb_md │  │    thumb_lg     │
               │  150x150  │  │  400x400 │  │    800x800      │
               └───────────┘  └──────────┘  └─────────────────┘
```

**Validation Rules:**
- Images: jpg, png, webp, gif
- Videos: mp4, mov, webm
- Max image size: 10MB
- Max video size: 100MB
- Max items per provider: 50
- Max total storage: 500MB per provider
- Title: 2-100 characters
- Description: max 500 characters

**Access Rules:**
- Upload/edit/delete: provider only (own portfolio)
- View: public (if provider is active)
- Reorder: provider only

**Error Handling:**
| Error | Response |
|-------|----------|
| Invalid file type | Display allowed formats |
| File too large | Display size limit |
| Storage limit exceeded | Prompt to delete items |
| Upload failed | Retry with resume support |
| Thumbnail generation failed | Use original as fallback |

**Future Extensions:**
- Video transcoding for web optimization
- Before/after comparisons
- Client testimonials with photos
- Instagram/social media import
- Watermarking for protection
- Categories/albums organization
- Featured/pinned items
- Analytics (views, engagement)

---

### 3.7 API LAYER MODULE

**Purpose:**
Provide a secure, versioned REST API for third-party integrations and future mobile applications, maintaining consistency with web application functionality.

**Actors:**
| Actor | Description |
|-------|-------------|
| Mobile App | Future iOS/Android applications |
| Third-party Integration | External systems consuming API |
| Developer | Building on the API |
| System | Internal API consumers |

**Functional Use Cases:**

| UC-API-01 | Authenticate via API |
|-----------|---------------------|
| Description | Client obtains API token |
| Preconditions | Valid credentials |
| Flow | 1. Client POSTs to /api/v1/auth/login<br>2. System validates credentials<br>3. System generates Sanctum token<br>4. System returns token and user data |
| Postconditions | Token issued |

| UC-API-02 | List Providers (Public) |
|-----------|------------------------|
| Description | Retrieve paginated provider list |
| Preconditions | None (public endpoint) |
| Flow | 1. Client GETs /api/v1/providers<br>2. System returns paginated providers with filters<br>3. Response includes: id, name, rating, services, location |
| Postconditions | None |

| UC-API-03 | Get Provider Details (Public) |
|-----------|------------------------------|
| Description | Retrieve single provider with full details |
| Preconditions | Provider exists and is active |
| Flow | 1. Client GETs /api/v1/providers/{uuid}<br>2. System returns provider with: profile, services, portfolio, reviews, availability |
| Postconditions | None |

| UC-API-04 | Get Available Slots |
|-----------|---------------------|
| Description | Retrieve bookable time slots |
| Preconditions | Provider and service exist |
| Flow | 1. Client GETs /api/v1/providers/{uuid}/availability<br>2. Client passes: service_id, date_from, date_to<br>3. System returns available slots |
| Postconditions | None |

| UC-API-05 | Create Booking |
|-----------|----------------|
| Description | Create new booking via API |
| Preconditions | Authenticated, slot available |
| Flow | 1. Client POSTs to /api/v1/bookings<br>2. System validates request<br>3. System creates booking<br>4. System returns booking details with payment URL |
| Postconditions | Booking created |

| UC-API-06 | Get User Bookings |
|-----------|-------------------|
| Description | List authenticated user's bookings |
| Preconditions | Authenticated |
| Flow | 1. Client GETs /api/v1/bookings<br>2. System returns user's bookings with filters<br>3. Response includes: booking details, provider info, status |
| Postconditions | None |

| UC-API-07 | Cancel Booking |
|-----------|----------------|
| Description | Cancel existing booking |
| Preconditions | Authenticated, owns booking |
| Flow | 1. Client POSTs to /api/v1/bookings/{uuid}/cancel<br>2. System validates cancellation eligibility<br>3. System cancels booking<br>4. System returns cancellation details |
| Postconditions | Booking cancelled |

| UC-API-08 | Submit Review |
|-----------|---------------|
| Description | Submit review for completed booking |
| Preconditions | Authenticated, booking completed |
| Flow | 1. Client POSTs to /api/v1/bookings/{uuid}/review<br>2. System validates review<br>3. System creates review<br>4. System updates provider rating |
| Postconditions | Review created |

**API Endpoint Structure:**
```
/api/v1/
├── auth/
│   ├── POST   /login          # Get token
│   ├── POST   /register       # Register user
│   ├── POST   /logout         # Revoke token
│   └── GET    /user           # Get current user
│
├── providers/
│   ├── GET    /               # List providers (public)
│   ├── GET    /{uuid}         # Get provider (public)
│   ├── GET    /{uuid}/services      # Get provider services
│   ├── GET    /{uuid}/portfolio     # Get provider portfolio
│   ├── GET    /{uuid}/reviews       # Get provider reviews
│   └── GET    /{uuid}/availability  # Get available slots
│
├── services/
│   ├── GET    /               # List all services (public)
│   └── GET    /{uuid}         # Get service details
│
├── categories/
│   └── GET    /               # List service categories
│
├── bookings/
│   ├── GET    /               # List user bookings (auth)
│   ├── POST   /               # Create booking (auth)
│   ├── GET    /{uuid}         # Get booking details (auth)
│   ├── POST   /{uuid}/cancel  # Cancel booking (auth)
│   └── POST   /{uuid}/review  # Submit review (auth)
│
└── account/
    ├── GET    /profile        # Get profile (auth)
    ├── PUT    /profile        # Update profile (auth)
    └── GET    /payments       # Payment history (auth)
```

**Response Format:**
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

**Error Response Format:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

**Validation Rules:**
- All requests must include `Accept: application/json` header
- Authenticated endpoints require `Authorization: Bearer {token}` header
- Request body must be valid JSON
- UUIDs must be valid format
- Pagination: max 100 per page

**Access Rules:**
- Public endpoints: no auth required, rate limited
- Protected endpoints: valid Sanctum token required
- Provider endpoints: requires provider role
- Client endpoints: requires client role

**Rate Limiting:**
| Endpoint Type | Limit |
|---------------|-------|
| Public (unauthenticated) | 60/minute |
| Authenticated | 120/minute |
| Auth endpoints | 10/minute |

**Error Handling:**
| HTTP Code | Meaning |
|-----------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

**Future Extensions:**
- GraphQL endpoint
- WebSocket for real-time updates
- OAuth2 for third-party apps
- API keys for integrations
- Webhook subscriptions
- SDK generation (OpenAPI spec)
- API versioning (v2, v3)
- Field selection (sparse fieldsets)
- Relationship includes

---

## 5. DATABASE DESIGN

### 5.1 Entity Relationship Diagram (ERD)

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     USERS       │       │    PROVIDERS    │       │     CLIENTS     │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │──┐    │ id (PK)         │       │ id (PK)         │
│ uuid            │  │    │ uuid            │       │ uuid            │
│ name            │  │    │ user_id (FK)────│───────│ user_id (FK)────│──┐
│ email           │  │    │ business_name   │       │ preferences     │  │
│ password        │  │    │ slug            │       │ created_at      │  │
│ phone           │  │    │ bio             │       │ updated_at      │  │
│ avatar          │  │    │ location_id(FK) │       └─────────────────┘  │
│ role            │  │    │ address         │                            │
│ is_active       │  └───▶│ status          │                            │
│ last_login_at   │       │ commission_rate │◀───────────────────────────┘
│ created_at      │       │ rating_avg      │
│ updated_at      │       │ rating_count    │
│ deleted_at      │       │ created_at      │
└─────────────────┘       │ updated_at      │
                          └────────┬────────┘
                                   │
                                   │ location_id
                                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           LOCATIONS (Hierarchical)                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐  │
│  │   COUNTRIES     │      │    REGIONS      │      │   LOCATIONS     │  │
│  ├─────────────────┤      ├─────────────────┤      ├─────────────────┤  │
│  │ id (PK)         │◀─────│ id (PK)         │◀─────│ id (PK)         │  │
│  │ uuid            │      │ uuid            │      │ uuid            │  │
│  │ name            │      │ country_id (FK) │      │ region_id (FK)  │  │
│  │ code (ISO 3166) │      │ name            │      │ name            │  │
│  │ currency        │      │ slug            │      │ slug            │  │
│  │ timezone        │      │ is_active       │      │ is_active       │  │
│  │ is_active       │      │ created_at      │      │ created_at      │  │
│  │ created_at      │      │ updated_at      │      │ updated_at      │  │
│  │ updated_at      │      └─────────────────┘      └─────────────────┘  │
│  └─────────────────┘                                                     │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘

          ┌────────────────────────┬────────────────────────┐
          │                        │                        │
          ▼                        ▼                        ▼
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│    SERVICES     │      │   PORTFOLIOS    │      │  AVAILABILITY   │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ id (PK)         │      │ id (PK)         │      │ id (PK)         │
│ uuid            │      │ uuid            │      │ provider_id(FK) │
│ provider_id(FK) │      │ provider_id(FK) │      │ day_of_week     │
│ category_id(FK) │      │ title           │      │ start_time      │
│ name            │      │ description     │      │ end_time        │
│ description     │      │ sort_order      │      │ is_active       │
│ duration_mins   │      │ created_at      │      │ created_at      │
│ price           │      │ updated_at      │      │ updated_at      │
│ deposit_percent │      │ deleted_at      │      └─────────────────┘
│ is_active       │      └────────┬────────┘
│ created_at      │               │              ┌─────────────────┐
│ updated_at      │               ▼              │  BLOCKED_DATES  │
│ deleted_at      │      ┌─────────────────┐     ├─────────────────┤
└────────┬────────┘      │     MEDIA       │     │ id (PK)         │
         │               ├─────────────────┤     │ provider_id(FK) │
         │               │ id (PK)         │     │ date            │
         │               │ uuid            │     │ reason          │
         │               │ portfolio_id(FK)│     │ created_at      │
         │               │ type            │     └─────────────────┘
         │               │ file_path       │
         │               │ thumbnail_sm    │
         │               │ thumbnail_md    │
         │               │ thumbnail_lg    │
         │               │ mime_type       │
         │               │ file_size       │
         │               │ sort_order      │
         │               │ created_at      │
         │               └─────────────────┘
         │
         ▼
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│    BOOKINGS     │      │    PAYMENTS     │      │    REVIEWS      │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ id (PK)         │      │ id (PK)         │      │ id (PK)         │
│ uuid            │──────│ uuid            │      │ uuid            │
│ client_id (FK)  │      │ booking_id (FK) │◀─────│ booking_id (FK) │
│ provider_id(FK) │      │ amount          │      │ client_id (FK)  │
│ service_id (FK) │      │ currency        │      │ provider_id(FK) │
│ scheduled_at    │      │ gateway         │      │ rating          │
│ duration_mins   │      │ gateway_ref     │      │ comment         │
│ status          │      │ status          │      │ is_visible      │
│ total_amount    │      │ paid_at         │      │ created_at      │
│ deposit_amount  │      │ refund_amount   │      │ updated_at      │
│ platform_fee    │      │ refunded_at     │      └─────────────────┘
│ provider_amount │      │ metadata        │
│ notes           │      │ created_at      │
│ cancelled_by    │      │ updated_at      │
│ cancel_reason   │      └─────────────────┘
│ reminder_sent   │
│ created_at      │      ┌─────────────────┐
│ updated_at      │      │   CATEGORIES    │
└─────────────────┘      ├─────────────────┤
                         │ id (PK)         │
                         │ uuid            │
                         │ name            │
                         │ slug            │
                         │ icon            │
                         │ description     │
                         │ sort_order      │
                         │ is_active       │
                         │ created_at      │
                         │ updated_at      │
                         └─────────────────┘

┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│     LEADS       │      │  AUDIT_LOGS     │      │ SYSTEM_SETTINGS │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ id (PK)         │      │ id (PK)         │      │ id (PK)         │
│ name            │      │ user_id (FK)    │      │ key             │
│ email           │      │ action          │      │ value           │
│ phone           │      │ model_type      │      │ type            │
│ interest_type   │      │ model_id        │      │ group           │
│ source          │      │ old_values      │      │ description     │
│ utm_source      │      │ new_values      │      │ created_at      │
│ utm_medium      │      │ ip_address      │      │ updated_at      │
│ utm_campaign    │      │ user_agent      │      └─────────────────┘
│ converted_at    │      │ created_at      │
│ created_at      │      └─────────────────┘
│ updated_at      │
└─────────────────┘

┌─────────────────┐
│  NOTIFICATIONS  │
├─────────────────┤
│ id (PK)         │
│ uuid            │
│ user_id (FK)    │
│ type            │
│ title           │
│ message         │
│ data            │
│ read_at         │
│ created_at      │
└─────────────────┘
```

### 5.2 Table Definitions

#### users
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| name | VARCHAR(100) | NOT NULL | Full name |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email address |
| email_verified_at | TIMESTAMP | NULLABLE | Verification timestamp |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| phone | VARCHAR(20) | NULLABLE | E.164 format |
| avatar | VARCHAR(255) | NULLABLE | Avatar path |
| role | ENUM('client','provider','admin') | NOT NULL, DEFAULT 'client' | User role |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Account status |
| last_login_at | TIMESTAMP | NULLABLE | Last login |
| remember_token | VARCHAR(100) | NULLABLE | Session token |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete |

**Indexes:** `idx_users_email`, `idx_users_role_active`, `idx_users_uuid`

#### countries
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| name | VARCHAR(100) | NOT NULL | Country name |
| code | CHAR(2) | UNIQUE, NOT NULL | ISO 3166-1 alpha-2 code |
| currency_code | CHAR(3) | NOT NULL | ISO 4217 currency code |
| timezone | VARCHAR(50) | NOT NULL | Default timezone |
| phone_code | VARCHAR(10) | NULLABLE | International dialing code |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Availability status |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_countries_code`, `idx_countries_active`

#### regions
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| country_id | BIGINT UNSIGNED | FK, NOT NULL | References countries.id |
| name | VARCHAR(100) | NOT NULL | Region/parish/state name |
| slug | VARCHAR(120) | UNIQUE, NOT NULL | URL-friendly identifier |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Availability status |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_regions_country`, `idx_regions_slug`, `idx_regions_active`

#### locations
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| region_id | BIGINT UNSIGNED | FK, NOT NULL | References regions.id |
| name | VARCHAR(100) | NOT NULL | City/town/area name |
| slug | VARCHAR(120) | NOT NULL | URL-friendly identifier |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Availability status |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_locations_region`, `idx_locations_slug`, `idx_locations_active`
**Constraints:** UNIQUE(region_id, slug)

#### providers
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| user_id | BIGINT UNSIGNED | FK, UNIQUE, NOT NULL | References users.id |
| primary_location_id | BIGINT UNSIGNED | FK, NULLABLE | References locations.id (main location) |
| business_name | VARCHAR(100) | NOT NULL | Business display name |
| slug | VARCHAR(120) | UNIQUE, NOT NULL | URL-friendly identifier |
| bio | TEXT | NULLABLE | Business description |
| address | VARCHAR(255) | NULLABLE | Street address details |
| social_links | JSON | NULLABLE | Social media URLs |
| status | ENUM('pending','active','suspended','inactive') | NOT NULL, DEFAULT 'pending' | Account status |
| commission_rate | DECIMAL(5,2) | NOT NULL, DEFAULT 15.00 | Platform fee % |
| rating_avg | DECIMAL(3,2) | NOT NULL, DEFAULT 0.00 | Average rating |
| rating_count | INT UNSIGNED | NOT NULL, DEFAULT 0 | Total reviews |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_providers_slug`, `idx_providers_status`, `idx_providers_primary_location`

#### location_provider (pivot)
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| location_id | BIGINT UNSIGNED | FK, NOT NULL | References locations.id |
| is_primary | BOOLEAN | NOT NULL, DEFAULT FALSE | Is this the primary location |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_location_provider_location`
**Constraints:** UNIQUE(provider_id, location_id)

#### clients
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| user_id | BIGINT UNSIGNED | FK, UNIQUE, NOT NULL | References users.id |
| preferences | JSON | NULLABLE | Client preferences |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

#### categories
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| name | VARCHAR(50) | NOT NULL | Category name |
| slug | VARCHAR(60) | UNIQUE, NOT NULL | URL slug |
| icon | VARCHAR(50) | NULLABLE | Icon class/name |
| description | VARCHAR(255) | NULLABLE | Category description |
| sort_order | INT | NOT NULL, DEFAULT 0 | Display order |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Visibility |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

#### services
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| category_id | BIGINT UNSIGNED | FK, NULLABLE | References categories.id |
| name | VARCHAR(100) | NOT NULL | Service name |
| description | TEXT | NULLABLE | Service description |
| duration_minutes | SMALLINT UNSIGNED | NOT NULL | Duration in minutes |
| price | DECIMAL(10,2) | NOT NULL | Service price |
| deposit_percent | TINYINT UNSIGNED | NOT NULL, DEFAULT 0 | Deposit % (0-100) |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Availability |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete |

**Indexes:** `idx_services_provider`, `idx_services_category`, `idx_services_active`

#### provider_availability
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| day_of_week | TINYINT UNSIGNED | NOT NULL | 0=Sunday, 6=Saturday |
| start_time | TIME | NOT NULL | Start time |
| end_time | TIME | NOT NULL | End time |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Slot enabled |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_availability_provider_day`
**Constraints:** CHECK (start_time < end_time)

#### blocked_dates
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| blocked_date | DATE | NOT NULL | Blocked date |
| reason | VARCHAR(255) | NULLABLE | Reason for block |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |

**Indexes:** `idx_blocked_provider_date` (composite unique)

#### bookings
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| client_id | BIGINT UNSIGNED | FK, NOT NULL | References clients.id |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| service_id | BIGINT UNSIGNED | FK, NOT NULL | References services.id |
| scheduled_at | DATETIME | NOT NULL | Appointment datetime |
| duration_minutes | SMALLINT UNSIGNED | NOT NULL | Booked duration |
| status | ENUM('pending','confirmed','completed','cancelled','no_show') | NOT NULL, DEFAULT 'pending' | Booking status |
| total_amount | DECIMAL(10,2) | NOT NULL | Total price |
| deposit_amount | DECIMAL(10,2) | NOT NULL, DEFAULT 0 | Deposit required |
| platform_fee | DECIMAL(10,2) | NOT NULL, DEFAULT 0 | Platform commission |
| provider_amount | DECIMAL(10,2) | NOT NULL, DEFAULT 0 | Provider payout |
| notes | TEXT | NULLABLE | Client notes |
| cancelled_by | ENUM('client','provider','system','admin') | NULLABLE | Who cancelled |
| cancel_reason | VARCHAR(255) | NULLABLE | Cancellation reason |
| reminder_24h_sent | BOOLEAN | NOT NULL, DEFAULT FALSE | 24h reminder sent |
| reminder_2h_sent | BOOLEAN | NOT NULL, DEFAULT FALSE | 2h reminder sent |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_bookings_client`, `idx_bookings_provider`, `idx_bookings_scheduled`, `idx_bookings_status`

#### payments
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| booking_id | BIGINT UNSIGNED | FK, NOT NULL | References bookings.id |
| amount | DECIMAL(10,2) | NOT NULL | Payment amount |
| currency | CHAR(3) | NOT NULL, DEFAULT 'JMD' | ISO currency code |
| gateway | VARCHAR(50) | NOT NULL | Gateway name |
| gateway_transaction_id | VARCHAR(100) | NULLABLE | Gateway reference |
| status | ENUM('pending','processing','completed','failed','refunded','partially_refunded') | NOT NULL, DEFAULT 'pending' | Payment status |
| paid_at | TIMESTAMP | NULLABLE | Payment timestamp |
| refund_amount | DECIMAL(10,2) | NULLABLE | Amount refunded |
| refunded_at | TIMESTAMP | NULLABLE | Refund timestamp |
| metadata | JSON | NULLABLE | Gateway response data |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_payments_booking`, `idx_payments_status`, `idx_payments_gateway_ref`

#### portfolios
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| service_id | BIGINT UNSIGNED | FK, NULLABLE | References services.id |
| title | VARCHAR(100) | NULLABLE | Item title |
| description | VARCHAR(500) | NULLABLE | Item description |
| sort_order | INT | NOT NULL, DEFAULT 0 | Display order |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete |

**Indexes:** `idx_portfolios_provider`

#### media
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| mediable_type | VARCHAR(100) | NOT NULL | Polymorphic type |
| mediable_id | BIGINT UNSIGNED | NOT NULL | Polymorphic ID |
| collection | VARCHAR(50) | NOT NULL, DEFAULT 'default' | Media collection |
| type | ENUM('image','video') | NOT NULL | Media type |
| file_path | VARCHAR(500) | NOT NULL | S3 path |
| file_name | VARCHAR(255) | NOT NULL | Original filename |
| mime_type | VARCHAR(100) | NOT NULL | MIME type |
| file_size | INT UNSIGNED | NOT NULL | Size in bytes |
| thumbnail_sm | VARCHAR(500) | NULLABLE | Small thumbnail path |
| thumbnail_md | VARCHAR(500) | NULLABLE | Medium thumbnail path |
| thumbnail_lg | VARCHAR(500) | NULLABLE | Large thumbnail path |
| sort_order | INT | NOT NULL, DEFAULT 0 | Display order |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_media_mediable` (type + id)

#### reviews
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| uuid | CHAR(36) | UNIQUE, NOT NULL | Public identifier |
| booking_id | BIGINT UNSIGNED | FK, UNIQUE, NOT NULL | References bookings.id |
| client_id | BIGINT UNSIGNED | FK, NOT NULL | References clients.id |
| provider_id | BIGINT UNSIGNED | FK, NOT NULL | References providers.id |
| rating | TINYINT UNSIGNED | NOT NULL | Rating 1-5 |
| comment | TEXT | NULLABLE | Review text |
| is_visible | BOOLEAN | NOT NULL, DEFAULT TRUE | Public visibility |
| provider_response | TEXT | NULLABLE | Provider reply |
| responded_at | TIMESTAMP | NULLABLE | Response timestamp |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_reviews_provider`, `idx_reviews_client`
**Constraints:** CHECK (rating BETWEEN 1 AND 5)

#### leads
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NOT NULL | Contact name |
| email | VARCHAR(255) | NOT NULL | Email address |
| phone | VARCHAR(20) | NULLABLE | Phone number |
| interest_type | ENUM('client','provider') | NOT NULL | Interest type |
| source | VARCHAR(100) | NULLABLE | Lead source |
| utm_source | VARCHAR(100) | NULLABLE | UTM source |
| utm_medium | VARCHAR(100) | NULLABLE | UTM medium |
| utm_campaign | VARCHAR(100) | NULLABLE | UTM campaign |
| converted_at | TIMESTAMP | NULLABLE | Conversion timestamp |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_leads_email`, `idx_leads_interest`

#### audit_logs
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| user_id | BIGINT UNSIGNED | FK, NULLABLE | References users.id |
| action | VARCHAR(50) | NOT NULL | Action type |
| auditable_type | VARCHAR(100) | NOT NULL | Model class |
| auditable_id | BIGINT UNSIGNED | NOT NULL | Model ID |
| old_values | JSON | NULLABLE | Previous values |
| new_values | JSON | NULLABLE | New values |
| ip_address | VARCHAR(45) | NULLABLE | Client IP |
| user_agent | VARCHAR(500) | NULLABLE | Browser/client info |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |

**Indexes:** `idx_audit_user`, `idx_audit_auditable`, `idx_audit_action`

#### system_settings
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| key | VARCHAR(100) | UNIQUE, NOT NULL | Setting key |
| value | TEXT | NULLABLE | Setting value |
| type | VARCHAR(20) | NOT NULL, DEFAULT 'string' | Value type |
| group | VARCHAR(50) | NOT NULL, DEFAULT 'general' | Setting group |
| description | VARCHAR(255) | NULLABLE | Description |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_settings_group`

#### notifications
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | CHAR(36) | PK | UUID primary key |
| type | VARCHAR(255) | NOT NULL | Notification class |
| notifiable_type | VARCHAR(255) | NOT NULL | User type |
| notifiable_id | BIGINT UNSIGNED | NOT NULL | User ID |
| data | JSON | NOT NULL | Notification data |
| read_at | TIMESTAMP | NULLABLE | Read timestamp |
| created_at | TIMESTAMP | NOT NULL | Created timestamp |
| updated_at | TIMESTAMP | NOT NULL | Updated timestamp |

**Indexes:** `idx_notifications_notifiable`

### 5.3 Relationship Summary

| Relationship | Type | Description |
|--------------|------|-------------|
| User → Provider | 1:1 | User with provider role has one provider profile |
| User → Client | 1:1 | User with client role has one client profile |
| Country → Regions | 1:N | Country contains multiple regions/parishes |
| Region → Locations | 1:N | Region contains multiple cities/towns |
| Provider ↔ Locations | N:M | Provider can serve multiple locations (via pivot table) |
| Provider → Primary Location | N:1 | Provider has one primary/main location |
| Provider → Services | 1:N | Provider offers multiple services |
| Provider → Portfolios | 1:N | Provider has multiple portfolio items |
| Provider → Availability | 1:N | Provider has weekly availability slots |
| Provider → BlockedDates | 1:N | Provider can block specific dates |
| Provider → Bookings | 1:N | Provider receives bookings |
| Client → Bookings | 1:N | Client makes bookings |
| Service → Category | N:1 | Service belongs to one category |
| Service → Bookings | 1:N | Service can have multiple bookings |
| Booking → Payment | 1:1 | Each booking has one payment |
| Booking → Review | 1:1 | Completed booking can have one review |
| Portfolio → Media | 1:N | Portfolio item has multiple media files |
| User → AuditLogs | 1:N | User actions are logged |
| User → Notifications | 1:N | User receives notifications |

**Location Hierarchy:**
```
Country (e.g., Jamaica)
  └── Region (e.g., Kingston Parish, St. Andrew, St. James)
        └── Location (e.g., New Kingston, Half Way Tree, Montego Bay)
              └── Providers (many-to-many via location_provider pivot)
```

**Provider-Location Relationship:**
```
Provider (e.g., "Mobile Barber Joe")
  ├── primary_location_id → New Kingston (main base)
  └── locations (pivot) → [New Kingston, Half Way Tree, Portmore, Spanish Town]
```

This structure allows:
- **Multi-location providers** - Mobile barbers, photographers can serve multiple areas
- **Primary location** - For display and default sorting
- **Easy expansion** - Add new countries/regions without schema changes
- **Flexible filtering** - Find providers serving specific locations
- **Country-specific settings** - Currency (JMD), timezone per country
- **SEO-friendly URLs** - `/providers/jamaica/st-andrew/new-kingston`

---

## 6. SECURITY & MONITORING

### 6.1 Role-Based Access Control (RBAC)

**User Roles:**
| Role | Description | Permissions |
|------|-------------|-------------|
| client | Service consumer | Browse, book, review, manage own profile |
| provider | Service provider | Manage services, bookings, portfolio, earnings |
| admin | Platform administrator | Full system access, user management, reports |

**Permission Matrix:**
| Resource | Client | Provider | Admin |
|----------|--------|----------|-------|
| View providers | ✓ | ✓ | ✓ |
| Book services | ✓ | ✗ | ✓ |
| Manage own bookings | ✓ | ✓ | ✓ |
| Submit reviews | ✓ | ✗ | ✓ |
| Manage services | ✗ | Own only | ✓ |
| Manage portfolio | ✗ | Own only | ✓ |
| View earnings | ✗ | Own only | ✓ |
| Manage users | ✗ | ✗ | ✓ |
| View all bookings | ✗ | ✗ | ✓ |
| Process refunds | ✗ | ✗ | ✓ |
| System settings | ✗ | ✗ | ✓ |

**Middleware Implementation:**
```php
// Route protection examples
Route::middleware(['auth', 'role:client'])->group(...);
Route::middleware(['auth', 'role:provider'])->group(...);
Route::middleware(['auth', 'role:admin'])->group(...);
```

### 6.2 API Authentication

**Laravel Sanctum Configuration:**
- Token-based authentication for API
- Session-based for SPA (same domain)
- Token abilities for fine-grained permissions

**Token Abilities:**
| Ability | Description |
|---------|-------------|
| read:profile | Read user profile |
| write:profile | Update user profile |
| read:bookings | View bookings |
| write:bookings | Create/cancel bookings |
| read:services | View services (provider) |
| write:services | Manage services (provider) |
| read:earnings | View earnings (provider) |

**Security Headers:**
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

### 6.3 Input Validation & Sanitization

**Validation Strategy:**
- Form Request classes for all inputs
- Server-side validation always (never trust client)
- Sanitize HTML in text fields (strip_tags or HTMLPurifier)
- Prepared statements for all database queries (Eloquent default)
- UUID validation for public IDs

**XSS Prevention:**
- Blade escaping by default (`{{ }}`)
- CSP headers
- Sanitize user-generated content

**SQL Injection Prevention:**
- Eloquent ORM (parameterized queries)
- Never raw queries with user input
- Validate/whitelist column names for sorting

### 6.4 Audit Logging

**Logged Events:**
| Event | Data Captured |
|-------|---------------|
| User login | user_id, ip, user_agent, timestamp |
| User logout | user_id, timestamp |
| Profile update | user_id, old_values, new_values |
| Booking created | booking details, client_id, provider_id |
| Booking cancelled | booking_id, cancelled_by, reason |
| Payment processed | payment_id, amount, status |
| Service CRUD | service details, provider_id |
| Admin actions | all admin operations |

**Implementation:**
```php
// Using Laravel model events or Spatie Activity Log
activity()
    ->performedOn($booking)
    ->causedBy($user)
    ->withProperties(['status' => 'confirmed'])
    ->log('Booking confirmed');
```

### 6.5 Error Tracking

**Sentry Integration:**
- Capture PHP exceptions
- Capture JavaScript errors
- Performance monitoring
- Release tracking
- User context for errors

**Configuration:**
```php
// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'traces_sample_rate' => 0.2, // 20% of requests
'send_default_pii' => false,
```

**Error Levels:**
| Level | Action |
|-------|--------|
| Critical | Immediate alert (PagerDuty/SMS) |
| Error | Log + Sentry + Email |
| Warning | Log + Sentry |
| Info | Log only |
| Debug | Log only (dev environment) |

### 6.6 Performance Monitoring

**Metrics Collected:**
| Metric | Tool | Threshold |
|--------|------|-----------|
| Response time (p95) | Laravel Telescope / New Relic | < 500ms |
| Database query count | Telescope | < 20 per request |
| Memory usage | Server monitoring | < 128MB per request |
| Queue job duration | Horizon | < 30s |
| Failed jobs | Horizon | Alert on > 5/hour |
| Cache hit rate | Redis metrics | > 80% |

**Database Monitoring:**
- Slow query log (> 1s)
- Query count per request
- N+1 query detection (via Telescope)
- Connection pool monitoring

### 6.7 Alerting

**Alert Channels:**
- Email: Non-urgent notifications
- Slack: Development team alerts
- PagerDuty/SMS: Critical production issues

**Alert Conditions:**
| Condition | Severity | Channel |
|-----------|----------|---------|
| Server down | Critical | PagerDuty + Slack |
| Error rate > 5% | High | Slack + Email |
| Response time p95 > 2s | Medium | Slack |
| Failed payment > 3/hour | High | Slack + Email |
| Queue backing up > 1000 | Medium | Slack |
| Disk usage > 80% | Medium | Email |
| SSL cert expiring < 14 days | Low | Email |

---

## 7. DEPLOYMENT ARCHITECTURE

### 7.1 Infrastructure Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLOUDFLARE                               │
│                    (DNS + CDN + DDoS Protection)                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      LOAD BALANCER                               │
│                   (DigitalOcean / AWS ALB)                       │
└────────────────────────────┬────────────────────────────────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
              ▼                             ▼
┌─────────────────────────┐   ┌─────────────────────────┐
│      APP SERVER 1       │   │      APP SERVER 2       │
│   (Laravel + PHP-FPM)   │   │   (Laravel + PHP-FPM)   │
│   Ubuntu 22.04 LTS      │   │   Ubuntu 22.04 LTS      │
│   4GB RAM / 2 vCPU      │   │   4GB RAM / 2 vCPU      │
└────────────┬────────────┘   └────────────┬────────────┘
             │                             │
             └──────────────┬──────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│    MySQL      │   │    Redis      │   │      S3       │
│  (Primary)    │   │  (Cache +     │   │   (Media      │
│  8GB RAM      │   │   Sessions)   │   │   Storage)    │
│               │   │   2GB RAM     │   │               │
└───────┬───────┘   └───────────────┘   └───────────────┘
        │
        ▼
┌───────────────┐
│    MySQL      │
│  (Replica)    │
│  Read-only    │
└───────────────┘
```

### 7.2 Server Specifications

**Production Environment:**
| Component | Specification | Provider |
|-----------|---------------|----------|
| App Servers (x2) | 4GB RAM, 2 vCPU, 80GB SSD | DigitalOcean |
| Database (Primary) | 8GB RAM, 4 vCPU, 200GB SSD | DigitalOcean Managed MySQL |
| Database (Replica) | 4GB RAM, 2 vCPU, 200GB SSD | DigitalOcean Managed MySQL |
| Redis | 2GB RAM | DigitalOcean Managed Redis |
| Object Storage | Pay-per-use | DigitalOcean Spaces (S3-compatible) |
| CDN | Cloudflare Pro | Cloudflare |

**Staging Environment:**
| Component | Specification |
|-----------|---------------|
| App Server | 2GB RAM, 1 vCPU |
| Database | 2GB RAM, shared |
| Redis | 1GB RAM |

### 7.3 CI/CD Pipeline

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  GitHub  │───▶│  GitHub  │───▶│  Build   │───▶│  Test    │
│  Push    │    │ Actions  │    │  Assets  │    │  Suite   │
└──────────┘    └──────────┘    └──────────┘    └────┬─────┘
                                                     │
                     ┌───────────────────────────────┘
                     │
                     ▼
              ┌──────────────┐
              │   Deploy     │
              │  (Envoy /    │
              │  Deployer)   │
              └──────┬───────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼                         ▼
┌───────────────┐         ┌───────────────┐
│   Staging     │         │  Production   │
│  (auto)       │         │  (manual      │
│               │         │   approval)   │
└───────────────┘         └───────────────┘
```

**Pipeline Steps:**
1. **Trigger:** Push to `main` (staging) or tag (production)
2. **Build:**
   - Install Composer dependencies
   - Install npm dependencies
   - Build frontend assets (Vite)
   - Generate optimized autoloader
3. **Test:**
   - Run PHPUnit tests
   - Run Pest tests
   - Run PHPStan static analysis
   - Run Laravel Pint (code style)
4. **Deploy:**
   - Upload to server (rsync)
   - Run migrations
   - Clear/warm caches
   - Restart queue workers
   - Notify team

### 7.4 Deployment Process (Zero-Downtime)

**Directory Structure:**
```
/var/www/zeen/
├── releases/
│   ├── 20240115_120000/
│   ├── 20240116_150000/
│   └── 20240117_090000/  (current)
├── current -> releases/20240117_090000/
├── shared/
│   ├── .env
│   ├── storage/
│   └── node_modules/
└── repo.git/
```

**Deployment Script (Envoy):**
```bash
# 1. Clone release to new directory
git clone --depth 1 repo releases/{timestamp}

# 2. Link shared resources
ln -s shared/.env releases/{timestamp}/.env
ln -s shared/storage releases/{timestamp}/storage

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Atomic symlink switch
ln -sfn releases/{timestamp} current

# 7. Reload PHP-FPM (graceful)
sudo systemctl reload php8.3-fpm

# 8. Restart queue workers
php artisan queue:restart

# 9. Cleanup old releases (keep 5)
```

### 7.5 Environment Configuration

**Environment Variables (.env):**
```bash
# Application
APP_NAME=Zeen
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://zeen.app

# Database
DB_CONNECTION=mysql
DB_HOST=mysql-primary.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=zeen_production
DB_USERNAME=zeen_app
DB_PASSWORD=<secure_password>

# Redis
REDIS_HOST=redis.db.ondigitalocean.com
REDIS_PASSWORD=<secure_password>
REDIS_PORT=25061

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<spaces_key>
AWS_SECRET_ACCESS_KEY=<spaces_secret>
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=zeen-media
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com

# Payment (Power Tranz)
POWERTRANZ_MERCHANT_ID=<merchant_id>
POWERTRANZ_PASSWORD=<password>
POWERTRANZ_ENDPOINT=https://gateway.powertranz.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=<postmark_token>

# Monitoring
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

### 7.6 Backup Strategy

**Database Backups:**
| Type | Frequency | Retention | Storage |
|------|-----------|-----------|---------|
| Full backup | Daily (2 AM) | 30 days | DigitalOcean Spaces |
| Point-in-time | Continuous | 7 days | Managed MySQL feature |
| Weekly archive | Sunday | 1 year | Glacier-equivalent |

**Media Backups:**
- S3 versioning enabled
- Cross-region replication (future)
- 30-day retention for deleted files

**Backup Verification:**
- Monthly restore test to staging
- Automated backup integrity checks

**Recovery Procedures:**
```bash
# Database restore
mysql -h host -u user -p database < backup.sql

# Point-in-time recovery
# Use DigitalOcean managed MySQL console
# Select timestamp and restore to new cluster
```

### 7.7 Scaling Strategy

**Horizontal Scaling:**
- Add app servers behind load balancer
- Stateless app design (sessions in Redis)
- Database read replicas for read-heavy operations

**Vertical Scaling:**
- Upgrade server specs during low-traffic windows
- Zero-downtime with load balancer

**Future Considerations:**
- Kubernetes migration when traffic warrants
- Database sharding by provider region
- CDN for API responses (Cloudflare Workers)

---

## 8. MVP DELIVERY PLAN

### 8.1 Phase 1: Foundation (Core Platform)

**Deliverables:**
- [ ] User authentication (register, login, logout)
- [ ] Role-based access control (client, provider)
- [ ] Provider registration and profile setup
- [ ] Client registration and profile
- [ ] Basic UI layouts (public, auth, dashboards)
- [ ] Database migrations
- [ ] Domain folder structure

**Technical Tasks:**
1. Set up domain-based folder structure
2. Create User, Provider, Client models and migrations
3. Implement authentication controllers and actions
4. Create Vue layouts and auth pages
5. Configure middleware for role protection
6. Set up route structure

**Risks:**
- Scope creep on authentication features
- Over-engineering domain structure early

**Tradeoffs:**
- Simple email/password auth first, social login later
- Basic profile fields only, expand as needed

**Definition of Done:**
- Users can register as client or provider
- Users can log in and access role-appropriate dashboards
- All tests passing

---

### 8.2 Phase 2: Provider Console

**Deliverables:**
- [ ] Provider profile management (edit, avatar upload)
- [ ] Service CRUD (create, read, update, delete)
- [ ] Service categories
- [ ] Availability management (weekly schedule)
- [ ] Blocked dates management
- [ ] Portfolio management (upload, reorder, delete)
- [ ] Media upload system (S3 integration)
- [ ] Public provider profile page

**Technical Tasks:**
1. Create Service model and migration
2. Create Category model with seeder
3. Build provider profile edit page
4. Build services management interface
5. Create availability calendar component
6. Implement media upload with thumbnails
7. Build portfolio management interface
8. Create public provider profile page

**Risks:**
- Media upload complexity (file size, formats)
- Calendar UI complexity

**Tradeoffs:**
- Simple thumbnail generation (single size first)
- Basic calendar (no drag-drop initially)

**Definition of Done:**
- Providers can fully set up their profile
- Providers can add/edit/delete services
- Providers can set availability schedule
- Public can view provider profiles

---

### 8.3 Phase 3: Booking Engine

**Deliverables:**
- [ ] Browse providers page (with filters)
- [ ] Provider search functionality
- [ ] Availability calculation logic
- [ ] Booking creation flow
- [ ] Booking management (client view)
- [ ] Booking management (provider view)
- [ ] Booking status transitions
- [ ] Email notifications (booking created, confirmed, cancelled)
- [ ] Client dashboard with upcoming bookings

**Technical Tasks:**
1. Create Booking model and migration
2. Build provider listing with filters
3. Implement availability slot calculator
4. Create booking wizard (select service → pick time → confirm)
5. Build booking management pages
6. Implement booking status state machine
7. Set up email notifications (Mailables)
8. Create scheduled jobs (reminders, expiration)

**Risks:**
- Race conditions on slot booking
- Complex availability logic
- Time zone handling

**Tradeoffs:**
- Optimistic locking for bookings
- Server timezone (Jamaica) for MVP
- Email-only notifications (no SMS initially)

**Definition of Done:**
- Clients can browse and book providers
- Bookings flow through complete lifecycle
- Both parties receive email notifications

---

### 8.4 Phase 4: Payment Integration

**Deliverables:**
- [ ] Power Tranz gateway integration
- [ ] Payment processing for deposits
- [ ] Payment status tracking
- [ ] Webhook handling
- [ ] Refund processing
- [ ] Commission calculation
- [ ] Provider earnings dashboard
- [ ] Payment history (client and provider)
- [ ] Manual payment marking (admin)

**Technical Tasks:**
1. Create Payment model and migration
2. Build Power Tranz service class
3. Implement hosted payment page redirect
4. Handle payment webhooks
5. Build refund logic based on policy
6. Calculate and record commissions
7. Create earnings dashboard for providers
8. Build payment history views

**Risks:**
- Gateway integration complexity
- Webhook reliability
- Refund edge cases

**Tradeoffs:**
- Hosted payment page (PCI compliance)
- Manual payouts initially (no auto-transfer)
- JMD only for MVP

**Definition of Done:**
- Clients can pay for bookings
- Payments are tracked and verified
- Providers see their earnings
- Refunds work per cancellation policy

---

### 8.5 Phase 5: Reviews & Polish

**Deliverables:**
- [ ] Review submission (after completed booking)
- [ ] Review display on provider profiles
- [ ] Provider rating calculation
- [ ] Provider response to reviews
- [ ] Client booking history with reviews
- [ ] UI polish and responsive design
- [ ] Error handling improvements
- [ ] Loading states and feedback

**Technical Tasks:**
1. Create Review model and migration
2. Build review submission form
3. Display reviews on provider profile
4. Implement rating average calculation
5. Allow provider responses
6. Polish all UI components
7. Add loading skeletons
8. Improve form validation feedback

**Risks:**
- Fake review prevention
- Rating manipulation

**Tradeoffs:**
- Only verified bookings can review
- No review editing after 24 hours
- Simple 5-star rating (no sub-categories)

**Definition of Done:**
- Clients can review completed bookings
- Reviews appear on provider profiles
- Ratings are accurately calculated

---

### 8.6 Phase 6: API Layer

**Deliverables:**
- [ ] API authentication (Sanctum tokens)
- [ ] Public endpoints (providers, services)
- [ ] Protected endpoints (bookings, profile)
- [ ] API documentation
- [ ] Rate limiting
- [ ] Standardized responses

**Technical Tasks:**
1. Configure Sanctum for API tokens
2. Create API controllers (v1)
3. Build API resources (transformers)
4. Implement rate limiting
5. Standardize error responses
6. Generate OpenAPI documentation

**Risks:**
- Duplicating business logic
- API versioning strategy

**Tradeoffs:**
- Reuse Actions from web controllers
- v1 prefix, breaking changes require v2

**Definition of Done:**
- Mobile app can authenticate
- Mobile app can list providers
- Mobile app can create bookings

---

### 8.7 Phase 7: Admin & Testing

**Deliverables:**
- [ ] Admin dashboard
- [ ] User management (view, suspend)
- [ ] Booking oversight
- [ ] Payment management
- [ ] System settings
- [ ] Comprehensive test suite
- [ ] Performance optimization
- [ ] Security audit

**Technical Tasks:**
1. Build admin layout and navigation
2. Create user management pages
3. Create booking management pages
4. Create payment/refund management
5. Build system settings interface
6. Write feature tests for all flows
7. Write unit tests for Actions
8. Performance profiling and optimization
9. Security review and penetration testing

**Risks:**
- Admin scope creep
- Test coverage gaps

**Tradeoffs:**
- Essential admin features only
- Focus tests on critical paths

**Definition of Done:**
- Admins can manage platform
- 80%+ test coverage on critical paths
- Security audit passed

---

## 9. BOUNDARIES & NON-GOALS

### 9.1 What is NOT Included in MVP

| Feature | Reason | Future Phase |
|---------|--------|--------------|
| Mobile apps | Focus on web first | Post-MVP |
| SMS notifications | Cost, email sufficient | Phase 8 |
| Social login | Complexity, email works | Phase 8 |
| Multi-language | English first for Jamaica | Phase 9 |
| Real-time chat | Complexity | Phase 9 |
| Video calls | Out of scope | Phase 10+ |
| Multi-currency | JMD only initially | Phase 8 |
| Provider subscriptions | Revenue model TBD | Phase 9 |
| Advanced analytics | Basic metrics first | Phase 8 |
| Loyalty/rewards | Complexity | Phase 10+ |
| Gift cards | Payment complexity | Phase 9 |
| Group bookings | Booking complexity | Phase 9 |
| Recurring bookings | Booking complexity | Phase 8 |
| Provider teams | Single provider MVP | Phase 9 |
| Multi-location | Single location MVP | Phase 9 |

### 9.2 Deferred Features

**Authentication:**
- Social login (Google, Facebook, Apple)
- Two-factor authentication
- Password-less login (magic links)

**Booking:**
- Recurring appointments
- Waitlist functionality
- Booking modifications (reschedule)
- Group/party bookings
- Service packages

**Payment:**
- Multiple payment gateways
- Subscription billing
- Tipping
- Split payments
- Auto provider payouts
- Multi-currency

**Communication:**
- SMS notifications
- Push notifications
- In-app messaging
- Video consultations

**Discovery:**
- AI-powered recommendations
- Advanced search filters
- Geolocation-based sorting
- Provider promotions

**Provider:**
- Team member accounts
- Multiple locations
- Inventory management
- POS integration

### 9.3 Out-of-Scope Components

- Native mobile applications
- Offline mode support
- White-label solutions
- B2B/enterprise features
- Marketplace commission negotiation
- Provider loan/financing
- Insurance integration
- Background checks integration
- Accounting software integration
- Complex reporting/BI

---

## 10. ARCHITECTURAL DIAGRAMS

### 10.1 Application Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                            │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │   Vue SPA    │  │  Public Web  │  │  Provider    │  │    Admin    │ │
│  │  (Inertia)   │  │    Pages     │  │   Console    │  │   Console   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └─────────────┘ │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                         REST API (v1)                             │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           APPLICATION LAYER                             │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    Laravel HTTP Kernel                            │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │  │
│  │  │ Routing  │─▶│Middleware│─▶│Controller│─▶│ Form Requests    │  │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                    │                                    │
│                                    ▼                                    │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                      DOMAIN LAYER                                 │  │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐   │  │
│  │  │ app/Domains/    │  │ app/Domains/    │  │ app/Domains/    │   │  │
│  │  │     Auth        │  │    Provider     │  │    Booking      │   │  │
│  │  │  ├── Actions    │  │  ├── Actions    │  │  ├── Actions    │   │  │
│  │  │  ├── Models     │  │  ├── Models     │  │  ├── Models     │   │  │
│  │  │  ├── Requests   │  │  ├── Services   │  │  ├── Services   │   │  │
│  │  │  └── Events     │  │  └── Events     │  │  └── Events     │   │  │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘   │  │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐   │  │
│  │  │ app/Domains/    │  │ app/Domains/    │  │ app/Domains/    │   │  │
│  │  │    Payment      │  │     Client      │  │     Admin       │   │  │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘   │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         INFRASTRUCTURE LAYER                            │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │   Eloquent   │  │    Redis     │  │     S3       │  │   Queues    │ │
│  │     ORM      │  │   (Cache)    │  │   (Media)    │  │  (Horizon)  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └─────────────┘ │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │    MySQL     │  │  Power Tranz │  │    Mail      │  │   Sentry    │ │
│  │  (Database)  │  │  (Payments)  │  │ (Postmark)   │  │ (Monitoring)│ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.2 Request Flow

```
┌──────────┐     ┌───────────┐     ┌────────────┐     ┌────────────┐
│  Client  │────▶│ Cloudflare│────▶│   Nginx    │────▶│  PHP-FPM   │
│ (Browser)│     │   (CDN)   │     │            │     │            │
└──────────┘     └───────────┘     └────────────┘     └─────┬──────┘
                                                            │
    ┌───────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         LARAVEL APPLICATION                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐                                                   │
│  │   Routing    │  Match URL to route definition                    │
│  └──────┬───────┘                                                   │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────┐                                                   │
│  │  Middleware  │  Auth, CORS, Rate Limit, Role Check               │
│  │    Stack     │                                                   │
│  └──────┬───────┘                                                   │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────┐                                                   │
│  │    Form      │  Validate request data                            │
│  │   Request    │                                                   │
│  └──────┬───────┘                                                   │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐        │
│  │  Controller  │────▶│    Action    │────▶│    Model     │        │
│  │              │     │              │     │  (Eloquent)  │        │
│  └──────────────┘     └──────────────┘     └──────┬───────┘        │
│                                                    │                 │
│         ┌──────────────────────────────────────────┘                │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────┐     ┌──────────────┐                              │
│  │   Database   │     │    Cache     │                              │
│  │   (MySQL)    │     │   (Redis)    │                              │
│  └──────────────┘     └──────────────┘                              │
│                                                                      │
│         ┌─────────────────────────────────────────┐                 │
│         │                                         │                 │
│         ▼                                         ▼                 │
│  ┌──────────────┐                         ┌──────────────┐         │
│  │   Inertia    │  (Web Response)         │     JSON     │ (API)   │
│  │   Response   │                         │   Response   │         │
│  └──────────────┘                         └──────────────┘         │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 10.3 Booking Lifecycle

```
                              CLIENT                    PROVIDER
                                │                          │
                                ▼                          │
                    ┌───────────────────────┐             │
                    │   Browse Providers    │             │
                    └───────────┬───────────┘             │
                                │                          │
                                ▼                          │
                    ┌───────────────────────┐             │
                    │   Select Service      │             │
                    └───────────┬───────────┘             │
                                │                          │
                                ▼                          │
                    ┌───────────────────────┐             │
                    │   Check Availability  │◀────────────┤ Sets Schedule
                    └───────────┬───────────┘             │
                                │                          │
                                ▼                          │
                    ┌───────────────────────┐             │
                    │   Select Time Slot    │             │
                    └───────────┬───────────┘             │
                                │                          │
                                ▼                          │
┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   ┌─────────────┐         BOOKING CREATED                           │
│   │   PENDING   │◀──────────────────────────────────────────────────│
│   └──────┬──────┘              │                                    │
│          │                     │                                    │
│    ┌─────┴─────┐              │                                    │
│    │           │              │                                    │
│    ▼           ▼              ▼                                    │
│ ┌──────┐  ┌─────────┐  ┌───────────┐                               │
│ │30 min│  │ Payment │  │  Email    │                               │
│ │expiry│  │Required │  │  Sent     │                               │
│ └──┬───┘  └────┬────┘  └───────────┘                               │
│    │           │                                                    │
│    │           ▼                                                    │
│    │    ┌───────────────┐                                          │
│    │    │ Process       │                                          │
│    │    │ Payment       │                                          │
│    │    └───────┬───────┘                                          │
│    │            │                                                   │
│    │      ┌─────┴─────┐                                            │
│    │      │           │                                            │
│    │      ▼           ▼                                            │
│    │  ┌───────┐  ┌────────┐                                        │
│    │  │Success│  │ Failed │                                        │
│    │  └───┬───┘  └────┬───┘                                        │
│    │      │           │                                            │
│    │      │           ▼                                            │
│    │      │    ┌────────────┐                                      │
│    └──────┼───▶│ CANCELLED  │                                      │
│           │    └────────────┘                                      │
│           │           ▲                                            │
│           ▼           │                                            │
│   ┌─────────────┐     │                                            │
│   │  CONFIRMED  │─────┼────────────────────────────────────────────│
│   └──────┬──────┘     │                          │                 │
│          │            │                          │                 │
│    ┌─────┴─────────┐  │                          │                 │
│    │               │  │                          │                 │
│    ▼               ▼  │                          ▼                 │
│ ┌──────┐      ┌───────┴──┐              ┌─────────────┐            │
│ │24h   │      │ Client/  │              │  Provider   │            │
│ │Email │      │ Provider │              │  Confirms   │            │
│ └──────┘      │ Cancels  │              └─────────────┘            │
│               └──────────┘                                         │
│                                                                     │
│           │                                                        │
│           ▼                                                        │
│   ┌─────────────────┐                                              │
│   │  APPOINTMENT    │                                              │
│   │    TIME         │                                              │
│   └────────┬────────┘                                              │
│            │                                                        │
│      ┌─────┴─────┐                                                 │
│      │           │                                                 │
│      ▼           ▼                                                 │
│  ┌────────┐  ┌─────────┐                                           │
│  │ Client │  │ Service │                                           │
│  │No-Show │  │Delivered│                                           │
│  └───┬────┘  └────┬────┘                                           │
│      │            │                                                 │
│      ▼            ▼                                                 │
│  ┌────────┐  ┌───────────┐                                         │
│  │NO_SHOW │  │ COMPLETED │                                         │
│  └────────┘  └─────┬─────┘                                         │
│                    │                                                │
│                    ▼                                                │
│              ┌───────────┐                                         │
│              │  Review   │                                         │
│              │ Requested │                                         │
│              └───────────┘                                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 10.4 Payment Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PAYMENT FLOW                                  │
└─────────────────────────────────────────────────────────────────────┘

CLIENT                    ZEEN                     POWER TRANZ
  │                        │                           │
  │  1. Confirm Booking    │                           │
  │───────────────────────▶│                           │
  │                        │                           │
  │                        │  2. Create Payment Record │
  │                        │  (status: pending)        │
  │                        │                           │
  │                        │  3. Request Payment URL   │
  │                        │──────────────────────────▶│
  │                        │                           │
  │                        │  4. Return Hosted Page URL│
  │                        │◀──────────────────────────│
  │                        │                           │
  │  5. Redirect to Gateway│                           │
  │◀───────────────────────│                           │
  │                        │                           │
  │  6. Enter Card Details │                           │
  │───────────────────────────────────────────────────▶│
  │                        │                           │
  │                        │                           │  7. Process
  │                        │                           │  Payment
  │                        │                           │
  │                        │  8. Webhook: Success/Fail │
  │                        │◀──────────────────────────│
  │                        │                           │
  │                        │  9. Update Payment Status │
  │                        │  10. Confirm/Cancel       │
  │                        │      Booking              │
  │                        │                           │
  │  11. Redirect Back     │                           │
  │◀───────────────────────────────────────────────────│
  │                        │                           │
  │  12. Show Result       │                           │
  │◀───────────────────────│                           │
  │                        │                           │


┌─────────────────────────────────────────────────────────────────────┐
│                        REFUND FLOW                                   │
└─────────────────────────────────────────────────────────────────────┘

TRIGGER                   ZEEN                     POWER TRANZ
  │                        │                           │
  │  1. Cancellation       │                           │
  │───────────────────────▶│                           │
  │  (Client/Provider)     │                           │
  │                        │                           │
  │                        │  2. Calculate Refund      │
  │                        │  Based on Policy          │
  │                        │                           │
  │                        │  3. Request Refund        │
  │                        │──────────────────────────▶│
  │                        │                           │
  │                        │  4. Process Refund        │
  │                        │◀──────────────────────────│
  │                        │                           │
  │                        │  5. Update Payment        │
  │                        │  (status: refunded)       │
  │                        │                           │
  │                        │  6. Send Notifications    │
  │                        │                           │
  │  7. Confirmation       │                           │
  │◀───────────────────────│                           │
```

### 10.5 User Roles & Access Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER ROLES                                   │
└─────────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │     USER      │
                        │               │
                        │ - id          │
                        │ - email       │
                        │ - password    │
                        │ - role ───────┼────────────────┐
                        └───────────────┘                │
                                │                        │
           ┌────────────────────┼────────────────────┐   │
           │                    │                    │   │
           ▼                    ▼                    ▼   │
    ┌─────────────┐     ┌─────────────┐      ┌─────────────┐
    │   CLIENT    │     │  PROVIDER   │      │    ADMIN    │
    │             │     │             │      │             │
    │ role=client │     │role=provider│      │ role=admin  │
    └──────┬──────┘     └──────┬──────┘      └──────┬──────┘
           │                   │                    │
           │                   │                    │
           ▼                   ▼                    ▼
    ┌─────────────┐     ┌─────────────┐      ┌─────────────┐
    │   CLIENT    │     │  PROVIDER   │      │    ALL      │
    │   PROFILE   │     │   PROFILE   │      │   ACCESS    │
    └─────────────┘     └─────────────┘      └─────────────┘


┌─────────────────────────────────────────────────────────────────────┐
│                      ACCESS MATRIX                                   │
└─────────────────────────────────────────────────────────────────────┘

┌────────────────────┬──────────┬──────────┬──────────┬──────────────┐
│     RESOURCE       │  PUBLIC  │  CLIENT  │ PROVIDER │    ADMIN     │
├────────────────────┼──────────┼──────────┼──────────┼──────────────┤
│ Landing Page       │    ✓     │    ✓     │    ✓     │      ✓       │
│ Provider Listing   │    ✓     │    ✓     │    ✓     │      ✓       │
│ Provider Profile   │    ✓     │    ✓     │    ✓     │      ✓       │
├────────────────────┼──────────┼──────────┼──────────┼──────────────┤
│ Login/Register     │    ✓     │    ✗     │    ✗     │      ✗       │
├────────────────────┼──────────┼──────────┼──────────┼──────────────┤
│ Client Dashboard   │    ✗     │    ✓     │    ✗     │      ✓       │
│ Make Booking       │    ✗     │    ✓     │    ✗     │      ✓       │
│ My Bookings        │    ✗     │   Own    │    ✗     │     All      │
│ Submit Review      │    ✗     │    ✓     │    ✗     │      ✓       │
├────────────────────┼──────────┼──────────┼──────────┼──────────────┤
│ Provider Console   │    ✗     │    ✗     │    ✓     │      ✓       │
│ Manage Services    │    ✗     │    ✗     │   Own    │     All      │
│ Manage Portfolio   │    ✗     │    ✗     │   Own    │     All      │
│ View Earnings      │    ✗     │    ✗     │   Own    │     All      │
│ Manage Bookings    │    ✗     │    ✗     │   Own    │     All      │
├────────────────────┼──────────┼──────────┼──────────┼──────────────┤
│ Admin Console      │    ✗     │    ✗     │    ✗     │      ✓       │
│ Manage Users       │    ✗     │    ✗     │    ✗     │      ✓       │
│ Process Refunds    │    ✗     │    ✗     │    ✗     │      ✓       │
│ System Settings    │    ✗     │    ✗     │    ✗     │      ✓       │
│ View All Data      │    ✗     │    ✗     │    ✗     │      ✓       │
└────────────────────┴──────────┴──────────┴──────────┴──────────────┘


┌─────────────────────────────────────────────────────────────────────┐
│                    ROUTE PROTECTION                                  │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│   PUBLIC ROUTES                                                      │
│   ─────────────                                                      │
│   /                          Landing page                            │
│   /providers                 Provider listing                        │
│   /providers/{slug}          Provider profile                        │
│   /services                  Service categories                      │
│   /login                     Login page                              │
│   /register                  Client registration                     │
│   /register/provider         Provider registration                   │
│                                                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   CLIENT ROUTES (middleware: auth, role:client)                      │
│   ─────────────                                                      │
│   /dashboard                 Client dashboard                        │
│   /dashboard/bookings        My bookings                             │
│   /dashboard/bookings/{id}   Booking detail                          │
│   /dashboard/profile         Profile settings                        │
│   /book/{provider}/{service} Booking flow                            │
│                                                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   PROVIDER ROUTES (middleware: auth, role:provider)                  │
│   ───────────────                                                    │
│   /console                   Provider dashboard                      │
│   /console/profile           Profile management                      │
│   /console/services          Services CRUD                           │
│   /console/portfolio         Portfolio management                    │
│   /console/availability      Schedule management                     │
│   /console/bookings          Booking management                      │
│   /console/earnings          Earnings dashboard                      │
│                                                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   ADMIN ROUTES (middleware: auth, role:admin)                        │
│   ────────────                                                       │
│   /admin                     Admin dashboard                         │
│   /admin/users               User management                         │
│   /admin/providers           Provider management                     │
│   /admin/bookings            All bookings                            │
│   /admin/payments            Payment management                      │
│   /admin/settings            System settings                         │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 11. OUTPUT QUALITY STANDARD

Documentation must be:
- Developer friendly
- Business understandable
- Production realistic
- Optimized for fast implementation
- Minimal fluff
- Clear naming conventions
- Laravel best practices

---

## FINAL INSTRUCTION

Generate the full architecture and FRD now with:
- Section headings
- Bullet points
- Diagrams
- Tables
- Explanations
- Justifications

DO NOT SIMPLIFY.
DO NOT OMIT.
DO NOT MICRO-SERVICE.

Produce enterprise-grade output for a lean monolith startup system.

END OF PROMPT
