# Zeen | Configuration Module Specifications

This document outlines the purpose, functional intent, and user experience goals for the core configuration pages of the Zeen booking platform.

---

## 1. My Brand
**Intent:** To serve as the "Identity Hub" where businesses transform a generic booking link into a branded digital storefront.

### 2.1 Visuals
* **Purpose:** Control the aesthetic "first impression" of the booking site.
* **Intent:** Users should be able to upload high-quality assets (cover photos/galleries) and select a primary brand color that will be applied to buttons, links, and highlights. 
* **Key UX:** Provide a template selector that allows businesses to choose a layout that best fits their industry (e.g., a "Service Grid" for barbers vs. a "Featured Image" for photographers).

### 2.2 Content
* **Purpose:** Define the narrative and contact details of the business.
* **Intent:** Establish trust and SEO relevance. The "About" section and Industry tag help categorize the business within the Zeen ecosystem, while clear contact info ensures customers can find the physical location.

### 2.3 Domain
* **Purpose:** Establish a unique web address.
* **Intent:** Simplification of the user's digital footprint. By defining the `[subdomain].zeenconnect.com` prefix, users create a memorable, shareable URL for their Instagram bios and marketing materials.

### 2.4 Live Preview
* **Purpose:** Validation and quality control.
* **Intent:** A split-screen or modal view that toggles between mobile and desktop frames. This allows the user to see exactly how their visual and content changes look to a customer before hitting "Publish."

---

## 2. Your Profile
**Intent:** To manage the individual service provider's identity and schedule within the platform.

* **Personal Info & Role:** Defines the individual’s credentials and what they "do" (e.g., Senior Stylist).
* **Calendar Sync:** Technical integration (Google/Outlook) to prevent double-bookings across personal and professional schedules.
* **Availability & Breaks:** The engine of the booking system. This allows the user to set "hard" working hours and "soft" recurring breaks (lunch) or one-off time off.

---

## 3. Teams
**Intent:** (Tier Dependent) To manage multi-staff operations and scale the business.

* **Purpose:** Centralized management of staff members.
* **Intent:** Business owners can add team members, each with their own mirrored "Profile" (availability, roles, and sync). This allows for "Staff Selection" workflows during the customer booking process.

---

## 4. Service Booking Preferences
**Intent:** To define the "Rules of Engagement" for specific services.

* **Lead Times:** Prevents last-minute surprises. Users can set a minimum notice (e.g., "Must book 2 hours in advance") and an advance window (e.g., "Cannot book more than 30 days out").
* **Deposit Logic:** Financial protection. This allows the business to secure commitment via a fixed fee or percentage, reducing the impact of no-shows.

---

## 5. General Booking Preference
**Intent:** To set global business policies and platform-wide standards.

* **Refund Policies:** Standardized language to manage customer expectations regarding money back.
* **Cancellation/Reschedule Windows:** Defines the cutoff point for customer self-service changes (e.g., "No cancellations within 24 hours of the appointment"). This automates the enforcement of business boundaries.