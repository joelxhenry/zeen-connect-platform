# Profile vs Brand Separation - Refactor Plan

## Current Problem

The Profile page currently contains **provider/business data** that should be in **My Brand**:
- Business Name (provider data)
- Social Links (provider social links)
- Avatar uploads to provider's `avatar` collection

## Intended Architecture

### My Brand (Provider/Business Data)
Everything related to the business entity:
- Logo (provider logo)
- Business Name
- Tagline, Bio, Address, Website
- Social Links (business social media)
- Brand Colors
- Cover Photo, Gallery
- Site Template
- Domain

### Your Profile (User/Team Member Data)
Everything related to the individual person:
- Personal Name (user's full name)
- Role/Title (e.g., "Senior Stylist", "Owner")
- Personal Avatar (user's photo)
- Calendar Sync (Google/Outlook integration - Coming Soon)
- Availability & Breaks (working hours, time off)

---

## Proposed Changes

### Phase 1: Move Business Data to My Brand

#### 1.1 Update My Brand Content Tab
**File:** `resources/js/pages/Provider/Branding/Edit.vue`

Add to Content tab:
- Business Name field (move from Profile)
- Social Links section (move from Profile)

#### 1.2 Update BrandingController
**File:** `app/Domains/Provider/Controllers/BrandingController.php`

- Add `business_name` to edit() response
- Add `social_links` to edit() response
- Update `updateContent()` to handle business_name and social_links

#### 1.3 Create/Update Branding Validation
**File:** `app/Domains/Provider/Requests/UpdateBrandingContentRequest.php` (new or update existing)

Add validation for:
- `business_name` - required, string, min:2, max:100
- `social_links.*` - nullable, string, max:255

---

### Phase 2: Redesign Profile for User/Team Member

#### 2.1 Rewrite Profile/Edit.vue
**File:** `resources/js/pages/Provider/Profile/Edit.vue`

New structure:
```
<TabView>
  <TabPanel header="Personal Info">
    - User Avatar (uploads to user's media, not provider)
    - Full Name (user.name)
    - Role/Title (new field on user or provider_user pivot)
    - Email (read-only, from user.email)
    - Phone (user.phone)
  </TabPanel>

  <TabPanel header="Calendar Sync">
    - "Coming Soon" placeholder card
    - Description of Google Calendar & Outlook integration
    - Email notification signup (optional)
  </TabPanel>

  <TabPanel header="Availability">
    - Link to /availability page for provider owner
    - OR inline availability editor for team members
    - Working hours schedule
    - Break times
    - Blocked dates / Time off
    - Buffer time settings
  </TabPanel>
</TabView>
```

#### 2.2 Update ProfileController
**File:** `app/Domains/Provider/Controllers/ProfileController.php`

Change from returning provider data to returning user data:
```php
public function edit(): Response
{
    $user = Auth::user();

    return Inertia::render('Provider/Profile/Edit', [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar_url,
            'avatar_media' => $user->getFirstMedia('avatar')?->toMediaArray(),
            'role' => $user->provider_role, // New field needed
        ],
        'isOwner' => $user->isProviderOwner(),
        'availability' => $this->getAvailabilityData($user),
    ]);
}
```

#### 2.3 Update User Model (if needed)
**File:** `app/Models/User.php`

- Ensure User model has HasMedia trait for avatar uploads
- Add `provider_role` accessor or field

#### 2.4 Update UpdateProviderProfileRequest
**File:** `app/Domains/Provider/Requests/UpdateProviderProfileRequest.php`

New validation:
```php
return [
    'name' => ['required', 'string', 'min:2', 'max:100'],
    'phone' => ['nullable', 'string', 'max:20'],
    'role' => ['nullable', 'string', 'max:100'],
];
```

#### 2.5 Update UpdateProviderProfileAction
**File:** `app/Domains/Provider/Actions/UpdateProviderProfileAction.php`

Update to modify user data instead of provider:
```php
public function execute(User $user, array $data): User
{
    $user->update([
        'name' => $data['name'],
        'phone' => $data['phone'] ?? null,
    ]);

    // Update role in pivot or user record
    if (isset($data['role'])) {
        $user->updateProviderRole($data['role']);
    }

    return $user->fresh();
}
```

---

### Phase 3: Database Changes (if needed)

#### 3.1 Add Role Field
Check if `provider_user` pivot table has a `role` or `title` column. If not:

```php
// Migration
Schema::table('provider_user', function (Blueprint $table) {
    $table->string('role')->nullable()->after('permissions');
});
```

Or add to `users` table:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('job_title')->nullable();
});
```

---

## Files to Modify

### Frontend
| File | Action |
|------|--------|
| `resources/js/pages/Provider/Branding/Edit.vue` | Add Business Name & Social Links to Content tab |
| `resources/js/pages/Provider/Profile/Edit.vue` | Complete rewrite for user/team member data |

### Backend
| File | Action |
|------|--------|
| `app/Domains/Provider/Controllers/BrandingController.php` | Add business_name, social_links to edit & update |
| `app/Domains/Provider/Controllers/ProfileController.php` | Return user data instead of provider data |
| `app/Domains/Provider/Requests/UpdateProviderProfileRequest.php` | Update validation for user fields |
| `app/Domains/Provider/Actions/UpdateProviderProfileAction.php` | Update to modify user instead of provider |
| `app/Models/User.php` | Add HasMedia trait if not present, add role accessor |

### Database (potentially)
| File | Action |
|------|--------|
| New migration | Add `role` or `job_title` field |

---

## UI Mockup: New Profile Page

```
┌─────────────────────────────────────────────────────────────┐
│ Your Profile                                                 │
├─────────────────────────────────────────────────────────────┤
│ [Personal Info] [Calendar Sync] [Availability]              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Profile Photo                                         │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ [Avatar Upload]  Your photo appears next to your     │   │
│  │                  bookings and in team views.         │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Personal Information                                  │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ Full Name *        [____________________]            │   │
│  │ Role / Title       [____________________]            │   │
│  │                    e.g., Senior Stylist, Owner       │   │
│  │ Email              john@example.com (read-only)      │   │
│  │ Phone              [____________________]            │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Looking to update business details?                   │   │
│  │ [Go to My Brand →]                                   │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────┐
│ Calendar Sync Tab                                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │          🗓️  Calendar Integration                    │   │
│  │               Coming Soon                             │   │
│  │                                                       │   │
│  │  Connect your Google Calendar or Outlook to:         │   │
│  │  • Automatically block busy times                    │   │
│  │  • Sync bookings to your calendar                    │   │
│  │  • Avoid double-bookings                             │   │
│  │                                                       │   │
│  │  [Notify Me When Available]                          │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────┐
│ Availability Tab                                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Working Hours                                         │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ [Schedule Editor Component]                          │   │
│  │ Monday:    9:00 AM - 5:00 PM  [Edit]                │   │
│  │ Tuesday:   9:00 AM - 5:00 PM  [Edit]                │   │
│  │ ...                                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Breaks                                                │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ Lunch Break: 12:00 PM - 1:00 PM                      │   │
│  │ [+ Add Break]                                        │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Time Off                                              │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ Dec 25-26, 2024 - Christmas                          │   │
│  │ [+ Block Time Off]                                   │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Order

1. **Step 1:** Add Business Name & Social Links to My Brand Content tab
2. **Step 2:** Update BrandingController to handle new fields
3. **Step 3:** Check User model for HasMedia trait and role support
4. **Step 4:** Create migration for role field if needed
5. **Step 5:** Update ProfileController to return user data
6. **Step 6:** Rewrite Profile/Edit.vue with new structure
7. **Step 7:** Update profile validation and action
8. **Step 8:** Test both pages thoroughly

---

## Clarified Decisions

1. **User Avatar Storage:** Use Spatie Media Library (HasMedia trait) on User model, same pattern as Service/Provider models.

2. **Role/Title Location:** Store on TeamMember model. Every user related to a provider has a TeamMember instance (including the owner). Need to add `title` field via migration.

3. **Availability Scope:** Show inline availability on Profile page for all users (owners and team members).

4. **Calendar Sync:** "Coming Soon" placeholder is sufficient.

---

## Data Structure Findings

### Current TeamMember Model
- Has: `provider_id`, `user_id`, `email`, `name`, `permissions` (JSON), `status`
- Missing: `title` field for role/job title
- Has availability via: `TeamMemberAvailability`, `TeamMemberBlockedDate`, `AvailabilityBreak` (morphMany)

### Current User Model
- Has simple `avatar` string field (NOT HasMedia)
- Needs: Add HasMedia trait for proper media upload support

### Relationship Chain
```
User (owner) --hasOne--> Provider --hasMany--> TeamMember
User (team member) <--belongsTo-- TeamMember --belongsTo--> Provider
```

---

## Summary

This refactor properly separates:
- **My Brand** = Provider/business identity and settings
- **Your Profile** = Individual user/team member personal data

The key insight is that Profile should be about the **logged-in person**, not the business they work for.
