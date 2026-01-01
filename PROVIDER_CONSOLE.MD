# Zeen Provider Console: Product Requirements & Design Documentation

## 1. Core Design System: "Balanced Professional"
The console is designed to be a high-trust, minimalist, and mobile-first platform tailored for the Caribbean market.

* **Color Palette:**
    * **Primary:** Business Dark Green (used for primary action buttons and active brand states).
    * **Neutrals:** Monochromatic scale (Slate-50 for backgrounds, Slate-100 for borders).
* **Visual Hierarchy:** * **Surface-on-Base:** Pages use `bg-slate-50` backgrounds with white (`bg-white`) content surfaces.
    * **Borders vs. Shadows:** Use subtle hair-line borders (`border-slate-100`) instead of heavy shadows to maintain a clean, structured feel.
* **Form Behavior:** * **Floating Action Button:** A "Save" button must float at the bottom of the viewport when any form field is "dirty."
    * **Exit Safeguard:** If a user attempts to navigate away from an unsaved form, a system-level warning must trigger.

---

## 2. Product Architecture: Services vs. Events
Zeen supports two distinct booking models. Providers can define custom categories for each.

| Feature | Services (Flexible Slot) | Events (Fixed Occasion) |
| :--- | :--- | :--- |
| **Availability** | Driven by provider working hours. | Driven by specific date/time. |
| **Capacity** | Usually 1-on-1 (Private). | 1-to-many (Group). |
| **Selection** | Client picks a slot within provider hours. | Client joins a time set by provider. |
| **Recurrence** | Continuous (e.g., Mon–Fri). | Specific (e.g., Every Saturday). |

---

## 3. Sidebar Navigation Structure
The sidebar is ordered by operational priority:
1.  **Home Page** (Dashboard)
2.  **Management** (Services & Events)
3.  **Customers** (CRM)
4.  **Payments** (Placeholder)
5.  **Integrations**
6.  **More (Settings)**
* **Footer:** "Copy Site URL" share button, Logout, and Profile.

---

## 4. Page Functional Requirements

### 4.1 Home Page (Dashboard)
* **Confirmed Bookings:** Display all upcoming confirmed bookings.
* **Filters:** Filter by status, service, category, and assigned team member (permission-dependent).
* **View Toggles:** Default to **Listing view** with an optional **Calendar view**.
* **Event Feed:** A dedicated section showing upcoming Events with direct navigation to the Event page.

### 4.2 Management (Services & Events)
* **Assignment:** Ability to assign services or events to specific team members (tier-dependent).
* **Preview:** Include an "Open in new tab" button to preview the public-facing service/event page.
* **Team Permissions:** If teams are enabled, allow providers to manage which members can access specific offerings.

### 4.3 Customer Page
* **CRM Data:** Displays client information and their full booking history.
* **UX:** Optimized for fast searching and filtering.

### 4.4 Integrations
Categorized directory of supported third-party tools:
* **Video:** Google Meet.
* **Social:** Instagram Booking.
* **Payments:** PayPal, PowerTranz, WiPay, Cash Payments.
* **Calendar:** Google Calendar.
* **Communication:** WhatsApp, Email, SMS.

---

## 5. More (Settings) Grid
A grid-based menu navigating to specific configuration pages:

* **My Brand:** * **Visuals:** Site cover photo, template selection, gallery (images/video), and brand primary color.
    * **Content:** Industry, "About" section, tagline, and contact info/address.
    * **Domain:** Define the `[subdomain].zeenconnect.com` prefix.
    * **Live Preview:** Real-time mobile/desktop preview of the user's booking site.
* **Your Profile:** Tabular view for personal info, role, calendar sync, and personal availability/breaks/time off.
* **Teams:** (Tier dependent) List of members with profiles mirroring the "Your Profile" structure.
* **Service Booking Preferences:** * Lead times (min/hours/days) and advance booking windows.
    * Deposit logic (percentage or fixed) and full-payment requirements.
* **General Booking Preference:** Platform-defined refund policies and global cancellation/reschedule windows.