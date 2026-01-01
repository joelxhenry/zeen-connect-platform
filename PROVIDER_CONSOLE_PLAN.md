# Zeen Provider Console - Complete Overhaul Plan

## Executive Summary

This plan transforms the Zeen Provider Console into a premium, feature-complete platform based on the PROVIDER_CONSOLE.MD requirements. The overhaul covers:

1. **UI Design System** - "Balanced Professional" with slate neutrals + indigo accents
2. **Events System** - Fixed-occasion bookings with advanced recurrence
3. **Customer CRM** - Customer directory with booking history
4. **Integrations** - WhatsApp, Google Calendar, Google Meet
5. **Navigation Restructure** - Align with product requirements

---

## Current State Analysis

### Existing (28 Pages)
- Dashboard, Profile, Services, Availability, Bookings, Payments (full suite)
- Team Management, Subscription, Branding, Settings, Site Template

### Missing Features
| Feature | Status | Priority |
|---------|--------|----------|
| Events System | Not implemented | P1 |
| Customer CRM | Not implemented | P2 |
| Integrations | Not implemented | P3 |
| Reviews Index Page | Route exists, no UI | P2 |

---

## Phase 1: UI Foundation (Sprint 1-2)
**Status: 60% Complete**

### 1.1 Design Tokens ✅ DONE
- `resources/css/app.css` - CSS variables updated

### 1.2 Core Components ✅ DONE
- ConsoleButton.vue - Indigo variants
- ConsoleFormCard.vue - Hairline borders
- ConsoleStatCard.vue - Neutral icons
- ConsolePageHeader.vue - Slate colors
- ConsoleLayout.vue - Indigo sidebar accents

### 1.3 Remaining UI Work
Files to update:
- `resources/js/pages/Provider/Bookings/Index.vue`
- `resources/js/pages/Provider/Bookings/Show.vue`
- `resources/js/pages/Provider/Payments/Index.vue`
- `resources/js/pages/Provider/Services/Index.vue`
- `resources/js/pages/Provider/Services/Create.vue`
- `resources/js/pages/Provider/Services/Edit.vue`
- `resources/js/pages/Provider/Profile/Edit.vue`
- `resources/js/pages/Provider/Settings/Edit.vue`
- `resources/js/pages/Provider/Availability/Edit.vue`
- `resources/js/pages/Provider/Subscription/Index.vue`
- `resources/js/pages/Provider/Team/Index.vue`
- `resources/js/pages/Provider/Branding/Edit.vue`

---

## Phase 2: Events System (Sprint 3-5)
**Priority: P1 | Effort: Large**

### 2.1 Database Schema
```
events
├── id, uuid, provider_id (FK)
├── name, description, category_id
├── event_type: 'one_time' | 'recurring'
├── capacity, spots_remaining
├── price, deposit_type, deposit_amount
├── location, location_type: 'in_person' | 'virtual'
├── virtual_meeting_url (nullable)
├── duration_minutes
├── status: 'draft' | 'published' | 'cancelled'
├── is_active, timestamps

event_occurrences
├── id, event_id (FK)
├── start_datetime, end_datetime
├── capacity_override (nullable)
├── spots_remaining
├── status: 'scheduled' | 'cancelled' | 'completed'
├── timestamps

event_recurrence_rules
├── id, event_id (FK)
├── frequency: 'weekly' | 'biweekly' | 'monthly'
├── interval (1, 2, etc.)
├── days_of_week (JSON: [0,2,4] for Sun/Tue/Thu)
├── day_of_month (nullable, for monthly)
├── week_of_month (nullable: 'first', 'second', 'last')
├── starts_at, ends_at (nullable for infinite)
├── max_occurrences (nullable)
├── timestamps

event_bookings
├── id, uuid, event_occurrence_id (FK)
├── client_id (FK), guest_name, guest_email, guest_phone
├── spots_booked (default 1)
├── total_amount, deposit_amount, status
├── payment_status, timestamps
```

### 2.2 Backend Implementation
```
app/Domains/Event/
├── Models/
│   ├── Event.php
│   ├── EventOccurrence.php
│   ├── EventRecurrenceRule.php
│   └── EventBooking.php
├── Controllers/
│   ├── EventController.php (CRUD)
│   ├── EventOccurrenceController.php
│   └── EventBookingController.php
├── Actions/
│   ├── CreateEventAction.php
│   ├── UpdateEventAction.php
│   ├── GenerateOccurrencesAction.php
│   ├── CancelOccurrenceAction.php
│   └── BookEventAction.php
├── Services/
│   └── RecurrenceService.php (RRule-like logic)
├── Enums/
│   ├── EventType.php
│   ├── EventStatus.php
│   └── RecurrenceFrequency.php
└── Requests/
    ├── StoreEventRequest.php
    └── UpdateEventRequest.php
```

### 2.3 Frontend Pages
```
resources/js/pages/Provider/Events/
├── Index.vue        - List all events with calendar/list toggle
├── Create.vue       - Create event with recurrence builder
├── Edit.vue         - Edit event and manage occurrences
├── Show.vue         - Event details with occurrence list
└── Occurrence.vue   - Single occurrence details + bookings
```

### 2.4 Components
```
resources/js/components/events/
├── EventCard.vue            - Event summary card
├── RecurrenceBuilder.vue    - Advanced recurrence UI
├── OccurrenceCalendar.vue   - Calendar view of occurrences
└── EventBookingList.vue     - Attendees list
```

### 2.5 Routes
```php
// routes/provider.php
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/create', [EventController::class, 'create']);
    Route::post('/', [EventController::class, 'store']);
    Route::get('/{event}', [EventController::class, 'show']);
    Route::get('/{event}/edit', [EventController::class, 'edit']);
    Route::put('/{event}', [EventController::class, 'update']);
    Route::delete('/{event}', [EventController::class, 'destroy']);
    Route::post('/{event}/publish', [EventController::class, 'publish']);
    Route::post('/{event}/cancel', [EventController::class, 'cancel']);

    // Occurrences
    Route::get('/{event}/occurrences', [EventOccurrenceController::class, 'index']);
    Route::post('/{event}/occurrences/generate', [EventOccurrenceController::class, 'generate']);
    Route::post('/occurrences/{occurrence}/cancel', [EventOccurrenceController::class, 'cancel']);
});
```

---

## Phase 3: Customer CRM (Sprint 6-7)
**Priority: P2 | Effort: Medium**

### 3.1 Database Schema
```
clients (existing table - enhance)
├── Add: notes (text), tags (JSON)
├── Add: total_bookings_count, total_spent
├── Add: last_booking_at, first_booking_at
├── Add: source: 'organic' | 'referral' | 'import'

client_notes
├── id, client_id (FK), provider_id (FK)
├── note (text), created_by (user_id)
├── timestamps
```

### 3.2 Backend Implementation
```
app/Domains/CRM/
├── Models/
│   └── ClientNote.php
├── Controllers/
│   └── CustomerController.php
├── Actions/
│   ├── AddClientNoteAction.php
│   └── UpdateClientStatsAction.php (event listener)
└── Requests/
    └── UpdateClientRequest.php
```

### 3.3 Frontend Pages
```
resources/js/pages/Provider/Customers/
├── Index.vue    - Customer directory with search/filter
└── Show.vue     - Customer profile with booking history
```

### 3.4 Components
```
resources/js/components/crm/
├── CustomerCard.vue      - Customer summary card
├── CustomerSearch.vue    - Search + filter bar
├── BookingTimeline.vue   - Customer's booking history
└── ClientNotes.vue       - Notes management
```

### 3.5 Routes
```php
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::get('/{client}', [CustomerController::class, 'show']);
    Route::put('/{client}', [CustomerController::class, 'update']);
    Route::post('/{client}/notes', [CustomerController::class, 'addNote']);
    Route::delete('/{client}/notes/{note}', [CustomerController::class, 'deleteNote']);
});
```

---

## Phase 4: Integrations (Sprint 8-10)
**Priority: P3 | Effort: Large**

### 4.1 Database Schema
```
provider_integrations
├── id, provider_id (FK)
├── integration_type: 'whatsapp' | 'google_calendar' | 'google_meet'
├── status: 'pending' | 'connected' | 'error'
├── credentials (encrypted JSON)
├── settings (JSON)
├── connected_at, last_sync_at
├── timestamps

integration_logs
├── id, integration_id (FK)
├── action, status, message
├── metadata (JSON)
├── timestamps
```

### 4.2 WhatsApp Integration
```
app/Domains/Integration/WhatsApp/
├── WhatsAppService.php      - Send messages via API
├── WhatsAppTemplates.php    - Message templates
└── WhatsAppNotification.php - Notification class

Templates:
- booking_confirmation
- booking_reminder (24h before)
- booking_cancelled
- payment_received
```

### 4.3 Google Calendar Integration
```
app/Domains/Integration/GoogleCalendar/
├── GoogleCalendarService.php   - OAuth + API wrapper
├── CalendarSyncAction.php      - Sync bookings to calendar
└── GoogleCalendarController.php - OAuth callback

Features:
- OAuth2 authentication
- Two-way sync (bookings ↔ calendar events)
- Busy time blocking
```

### 4.4 Google Meet Integration
```
app/Domains/Integration/GoogleMeet/
├── GoogleMeetService.php    - Create meeting links
└── AttachMeetingAction.php  - Auto-attach to virtual bookings

Features:
- Auto-generate meeting links for virtual services/events
- Include link in booking confirmation
- Integrate with Google Calendar events
```

### 4.5 Frontend Pages
```
resources/js/pages/Provider/Integrations/
├── Index.vue              - Integration directory grid
├── WhatsApp/Setup.vue     - WhatsApp configuration
├── GoogleCalendar/Setup.vue
└── GoogleMeet/Setup.vue
```

### 4.6 Routes
```php
Route::prefix('integrations')->group(function () {
    Route::get('/', [IntegrationController::class, 'index']);

    // WhatsApp
    Route::get('/whatsapp', [WhatsAppController::class, 'show']);
    Route::post('/whatsapp', [WhatsAppController::class, 'configure']);
    Route::delete('/whatsapp', [WhatsAppController::class, 'disconnect']);

    // Google Calendar
    Route::get('/google-calendar/auth', [GoogleCalendarController::class, 'redirect']);
    Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'callback']);
    Route::post('/google-calendar/sync', [GoogleCalendarController::class, 'sync']);
    Route::delete('/google-calendar', [GoogleCalendarController::class, 'disconnect']);

    // Google Meet
    Route::get('/google-meet', [GoogleMeetController::class, 'show']);
    Route::post('/google-meet', [GoogleMeetController::class, 'enable']);
});
```

---

## Phase 5: Navigation Restructure (Sprint 11)
**Priority: P2 | Effort: Small**

### 5.1 New Sidebar Structure
Per PROVIDER_CONSOLE.MD requirements:

```
1. Dashboard (Home)
2. Management
   └── Services
   └── Events (NEW)
3. Customers (NEW)
4. Bookings
5. Payments
6. Integrations (NEW)
7. More (Settings Grid)
   └── My Brand (Branding + Site Template)
   └── Your Profile
   └── Teams
   └── Booking Preferences
   └── General Settings
```

### 5.2 Files to Update
- `resources/js/components/layout/ConsoleLayout.vue` - Sidebar nav items
- `resources/js/routes/provider.ts` - Frontend routes
- Create `resources/js/pages/Provider/More/Index.vue` - Settings grid

### 5.3 Sidebar Footer
- Copy Site URL button
- Logout
- Profile link

---

## Phase 6: Reviews Page (Sprint 11)
**Priority: P2 | Effort: Small**

Create missing Reviews/Index.vue page:

```
resources/js/pages/Provider/Reviews/
└── Index.vue
    - List all reviews with filtering
    - Respond to reviews inline
    - Rating summary stats
    - Filter by responded/unresponded
```

---

## Phase 7: Form Behavior (Sprint 12)
**Priority: P2 | Effort: Medium**

### 7.1 Floating Save Button
Create reusable component for all forms:

```
resources/js/components/forms/
├── FloatingSaveButton.vue  - Sticky save button when form dirty
└── UnsavedChangesGuard.vue - Navigation warning when dirty
```

### 7.2 Implementation
- Track form dirty state via `useForm` or custom composable
- Show floating save button when changes detected
- Warn on navigation away with unsaved changes

---

## Implementation Timeline

| Phase | Sprint | Duration | Deliverables |
|-------|--------|----------|--------------|
| 1 | 1-2 | 1 week | Complete UI overhaul |
| 2 | 3-5 | 2 weeks | Events system MVP |
| 3 | 6-7 | 1 week | Customer CRM |
| 4 | 8-10 | 2 weeks | Integrations |
| 5 | 11 | 3 days | Navigation + Reviews |
| 6 | 12 | 3 days | Form behaviors |

**Total: ~8 weeks**

---

## File Creation Summary

### New Files (Events)
- `database/migrations/xxxx_create_events_table.php`
- `database/migrations/xxxx_create_event_occurrences_table.php`
- `database/migrations/xxxx_create_event_recurrence_rules_table.php`
- `database/migrations/xxxx_create_event_bookings_table.php`
- `app/Domains/Event/Models/Event.php`
- `app/Domains/Event/Models/EventOccurrence.php`
- `app/Domains/Event/Models/EventRecurrenceRule.php`
- `app/Domains/Event/Models/EventBooking.php`
- `app/Domains/Event/Controllers/EventController.php`
- `app/Domains/Event/Controllers/EventOccurrenceController.php`
- `app/Domains/Event/Actions/CreateEventAction.php`
- `app/Domains/Event/Actions/GenerateOccurrencesAction.php`
- `app/Domains/Event/Services/RecurrenceService.php`
- `resources/js/pages/Provider/Events/Index.vue`
- `resources/js/pages/Provider/Events/Create.vue`
- `resources/js/pages/Provider/Events/Edit.vue`
- `resources/js/pages/Provider/Events/Show.vue`
- `resources/js/components/events/RecurrenceBuilder.vue`

### New Files (CRM)
- `database/migrations/xxxx_add_crm_fields_to_clients.php`
- `database/migrations/xxxx_create_client_notes_table.php`
- `app/Domains/CRM/Controllers/CustomerController.php`
- `app/Domains/CRM/Models/ClientNote.php`
- `resources/js/pages/Provider/Customers/Index.vue`
- `resources/js/pages/Provider/Customers/Show.vue`

### New Files (Integrations)
- `database/migrations/xxxx_create_provider_integrations_table.php`
- `app/Domains/Integration/WhatsApp/WhatsAppService.php`
- `app/Domains/Integration/GoogleCalendar/GoogleCalendarService.php`
- `app/Domains/Integration/GoogleMeet/GoogleMeetService.php`
- `resources/js/pages/Provider/Integrations/Index.vue`

### New Files (Other)
- `resources/js/pages/Provider/Reviews/Index.vue`
- `resources/js/pages/Provider/More/Index.vue`
- `resources/js/components/forms/FloatingSaveButton.vue`
- `resources/js/components/forms/UnsavedChangesGuard.vue`

---

## Quality Checklist

### UI Consistency
- [ ] All pages use `bg-slate-50` background
- [ ] All cards use `border border-slate-100`
- [ ] All primary actions use `indigo-600`
- [ ] No hardcoded hex colors
- [ ] Mobile-first responsive design

### Events System
- [ ] Recurrence builder handles all patterns
- [ ] Occurrences generate correctly
- [ ] Capacity tracking accurate
- [ ] Virtual meeting links work

### CRM
- [ ] Search is fast and accurate
- [ ] Booking history complete
- [ ] Notes save correctly

### Integrations
- [ ] OAuth flows work
- [ ] WhatsApp messages send
- [ ] Calendar sync is bidirectional
- [ ] Meeting links generate

---

## Dependencies

### External Services
- WhatsApp Business API (or Twilio)
- Google OAuth 2.0
- Google Calendar API
- Google Meet API

### Packages (Potential)
- `spatie/laravel-google-calendar` - Calendar integration
- `google/apiclient` - Google API client
- `twilio/sdk` - WhatsApp via Twilio (alternative)

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Events complexity | Start with simple recurrence, iterate |
| Google OAuth issues | Use well-tested packages |
| WhatsApp API costs | Make integration tier-locked |
| Scope creep | Stick to Basic CRM scope |
