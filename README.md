# Hinaguan Nature Park Management & Reservation System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Vite](https://img.shields.io/badge/Vite-Tailwind_CSS-646CFF?style=for-the-badge&logo=vite)](https://vitejs.dev)
[![Tests](https://img.shields.io/badge/Automated_Tests-48_Passed-success?style=for-the-badge&logo=githubactions)](https://phpunit.de)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

An intelligent, web-based resort and eco-park management system engineered for **Hinaguan Nature Park** (owned by celebrity host Brenda Mage). The system automates public guest bookings, front-desk point-of-sale operations, live park carrying-capacity monitoring, weather forecasting, dynamic pricing, and AI-driven business intelligence.

---

## Table of Contents
- [System Overview](#system-overview)
- [Key Features & Modules](#key-features--modules)
- [System Workflows](#system-workflows)
- [Technology Stack & Integrations](#technology-stack--integrations)
- [Installation & Setup](#installation--setup)
- [Default Demo Credentials](#default-demo-credentials)
- [Automated Test Suite](#automated-test-suite)
- [Directory Structure](#directory-structure)
- [Capstone Defense Notes](#capstone-defense-notes)

---

## System Overview

Hinaguan Nature Park is an eco-tourism destination offering cottages, events facilities, day and night swimming, and nature attractions. The park experiences heavy foot traffic fluctuations due to weather conditions and celebrity presence.

This system provides a unified digital operational backbone:
* **For Guests:** Real-time cottage availability, transparent daytime/nighttime pricing, seamless GCash/Card downpayments via PayMongo, offline-ready printable QR passes, and proactive AI assistance.
* **For Front-Desk Staff:** Point-of-Sale (POS) walk-in booking, instant optical QR ticket check-ins, companion headcount tracking, stay extensions, and emergency occupancy monitoring.
* **For Park Administrators:** Dynamic tariff configuration, staff account control with OTP security, carrying-capacity compliance matrices, multi-lingual review moderation, and AI-assisted financial analytics.

---

## Key Features & Modules

### 1. Public & Guest Portal
* **Dynamic Booking Engine:** Supports both single-shift (Daytime: 8 AM–5 PM / Nighttime: 6 PM–8 AM) and continuous multi-day stays.
* **Historical Price Freezing (`price_at_booking`):** Confirmed reservations preserve agreed pricing regardless of future catalog adjustments.
* **PayMongo Payment Integration:** Automated downpayment processing supporting GCash, Maya, and credit/debit cards with webhook synchronization.
* **Offline-Ready QR Ticket Passes:** Generates PDF e-passes with embedded vector SVG QR codes via DomPDF and Endroid QR, scan-ready even during mobile network dead zones.
* **Celebrity Presence & Weather Status:** Live weather alerts and a dynamic widget indicating whether Brenda Mage is currently on-site.
* **Interactive AI Assistant:** Guest chatbot powered by OpenRouter LLM, equipped with prompt-injection defense and guardrails against disclosing private employee/sales records.

### 2. Front-Desk Staff Operations (POS & Check-In Desk)
* **Instant QR Code Check-In:** High-speed barcode/camera scanning to check in groups in seconds.
* **Walk-In Counter Booking:** Instant on-premise reservations with automatic entrance fee calculations and cash receipt generation.
* **Granular Companion Management:** Separates the lead booker from companions via junction records, allowing individual early checkout timestamps for accurate emergency headcounts.
* **In-House Stay Extensions & Room Swaps:** Add extra nights, shifts, or secondary amenities to active reservations with automated balance adjustment.
* **Operational Occupancy Monitor:** Live floor map showing occupied, reserved, and available amenities across ongoing shifts.

### 3. Administration & Business Intelligence
* **Park Configuration Hub:** Set opening/closing hours, daytime/nighttime thresholds, adult/child entrance fees, and swimming pool rates.
* **Emergency Park Closure:** One-click global park status switch (Open, Temporarily Closed, Under Maintenance) that immediately disables all booking routes during severe typhoons.
* **OTP Challenge Verification:** Sensitive actions (changing pricing or editing credentials) require One-Time Password authorization.
* **Multi-Lingual AI Feedback Moderation:** Analyzes guest reviews and automatically detects profanity, abusive terms, and colloquial insults in **Tagalog, Bisaya, and English**.
* **AI Reports & Predictive Analytics:**
  * Natural language executive summaries generated via LLM.
  * **Intelligent Local Fallback:** Mathematical rule-based local generator when external AI APIs are offline.
  * **Visitor Prediction Model:** Forecasts weekly guest foot traffic by combining day-of-week baselines, Philippine national holidays, and Open-Meteo precipitation multipliers.


---

## System Workflows

### 1. Guest Online Reservation Workflow
1. **Selection & Scheduling:** The guest visits the portal, selects their arrival date, chooses their shift (Daytime: 8 AM–5 PM or Nighttime: 6 PM–8 AM, or continuous multi-day stay), and selects available cottages/amenities.
2. **Real-Time Availability Validation:** The system immediately validates vacancy to ensure no overlapping reservations can be booked for the same slot.
3. **Secure Payment via PayMongo:** The guest pays the downpayment online using GCash, Maya, or Debit/Credit Card.
4. **Instant Confirmation & Junction Records:** Upon successful payment, the reservation is created, and agreed prices are locked (`price_at_booking`) so future price updates won't alter past receipts.
5. **Digital QR Pass Delivery:** An automated confirmation email is dispatched via Resend, containing a printable PDF e-pass with an embedded vector SVG QR code for gate admission.

### 2. Front-Desk Check-In & Headcount Lifecycle
1. **Guest Arrival & Scanning:** Upon arrival at the park entrance, the lead guest presents their digital or printed QR ticket pass.
2. **Instant Optical Verification:** Front-desk staff scan the QR code with the terminal camera/scanner, which immediately displays reservation details and timestamps the `check_in` time.
3. **Companion Logging & Live Headcount:** Staff register accompanying guests under the reservation. The live floor occupancy monitor updates immediately to reflect active park visitors.
4. **Granular / Early Companion Checkout:** If an individual guest leaves early, staff record their specific `checked_out_at` timestamp. The reservation remains active until the final companion departs.
5. **Group Departure & Amenity Release:** Once the entire party departs and any remaining balance or extension fees are settled, the reservation status is updated to 'Completed' and the amenities are returned to the available inventory pool.

---

## Technology Stack & Integrations

| Component | Technology | Purpose |
| :--- | :--- | :--- |
| **Core Framework** | Laravel 12.x / PHP 8.2+ | Robust MVC architecture, routing, ORM, and CLI tools |
| **Database** | MySQL / SQLite | Relational database engine with junction tables |
| **Styling & Assets** | Tailwind CSS / Vite / Vanilla CSS | Responsive UI with dark-mode and custom micro-interactions |
| **Payment Gateway** | PayMongo API | Online transactions (GCash, Maya, Debit/Credit Card) |
| **Weather Engine** | Open-Meteo API | Geolocation-targeted weather forecasting (cached 1 hr) |
| **AI LLM Engine** | OpenRouter API | Intelligent chatbot assistants and report synthesis |
| **PDF & Barcoding** | DomPDF / Endroid QR Code | Vector SVG QR codes and printable reservation passes |
| **Email Delivery** | Resend / Laravel Mail | High-deliverability transactional ticketing emails |
| **Bot Protection** | Cloudflare Turnstile | Non-intrusive CAPTCHA protection against brute-force login |

---

## Installation & Setup

### 1. Prerequisites
* **PHP:** >= 8.2 with `pdo`, `mbstring`, `openssl`, `curl`, and `gd` extensions enabled.
* **Composer:** >= 2.x
* **Node.js & NPM:** >= 18.x
* **Database Server:** MySQL 8.x (or SQLite for local testing).

### 2. Clone and Configure
```bash
# 1. Clone repository
git clone https://github.com/frenchjohn/development-hinaguan-system.git
cd development-hinaguan-system

# 2. Install backend dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Create environment file
cp .env.example .env

# 5. Generate application encryption key
php artisan key:generate
```

### 3. Database Migration & Seeding
Ensure your `.env` contains your valid database credentials (`DB_DATABASE=hinaguan_db`), then execute:
```bash
# Run migrations and seed default administrative accounts & park settings
php artisan migrate --seed
```

### 4. Build Assets & Start Development Server
```bash
# Run frontend assets compiler and web server concurrently
npm run dev

# In another terminal window:
php artisan serve
```
Access the application at `http://localhost:8000`.

---

## Default Demo Credentials

For testing and evaluation purposes, the seeder automatically provisions default accounts:

| Role | Email | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **Administrator** | `parkhinaguan@gmail.com` | `admin1234` | `/login` $\to$ `/admin/dashboard` |
| **Staff Personnel**| `staff@example.com` | `staff1234` | `/login` $\to$ `/staff/dashboard` |

---

## Automated Test Suite

The system includes **48 comprehensive Feature Test suites** covering concurrency, financial calculations, multi-day scheduling, and AI fallbacks.

```bash
# Run the complete test suite
php artisan test

# Run specific domain test groups
php artisan test --filter=StaffRecordsPageTest
php artisan test --filter=VisitorPredictionTest
php artisan test --filter=BrendaAvailabilityTest
```

---

## Directory Structure

```
hinaguan-system-replica/
├── app/
│   ├── Http/Controllers/       # Chatbot, AI Reports, Home, and Auth controllers
│   ├── Mail/                   # Transactional Reservation QR email definitions
│   ├── Models/                 # 21 Eloquent models (Reservations, Amenities, Logs)
│   └── Services/               # Domain services (PayMongo, AI, Weather, Prediction, PDF)
├── config/                     # Application and service configuration files
├── database/
│   ├── migrations/             # 32 database schema definitions
│   └── seeders/                # Default roles, park settings, and test dataset
├── public/                     # Compiled web assets, logos, and entry point
├── resources/
│   ├── css/                    # Custom stylesheets and admin/staff design system
│   ├── js/                     # Client scripts, payment handlers, and chatbot UI
│   └── views/                  # Blade templates (Admin, Staff, Guest, PDF, Emails)
├── routes/
│   ├── web.php                 # Application routes, transaction closures, and POS endpoints
│   └── console.php             # Scheduled tasks and artisan commands
└── tests/
    └── Feature/                # 48 end-to-end automated business logic tests
```

---

## Capstone Defense Notes

### Key Panel Talking Points
1. **Concurrency Protection:** The booking engine prevents double-booking through synchronized slot verification (`DisabledUnavailableAmenityOptionTest.php`).
2. **Accounting Integrity:** Price alterations never affect past reservations due to the `price_at_booking` snapshot model.
3. **Emergency Headcount Accuracy:** The junction structure (`reservation_guests`) enables granular companion checkouts, ensuring staff always know the exact number of people inside the park during emergencies.
4. **Predictive Analytics:** The `VisitorPredictionService` incorporates historical shift averages, Philippine holidays, and live weather coefficients to predict operational crowd loads.
5. **Fault Tolerance:** If external cloud AI fails, `AdminReportAiController` automatically switches to a local mathematical reporting algorithm with zero user downtime.

---

## Authors & Acknowledgments
* **System Developer:** French John & Capstone Team
* **Adviser / Institution:** Capstone Research Committee
* **Beneficiary:** Hinaguan Nature Park (Brenda Mage)
