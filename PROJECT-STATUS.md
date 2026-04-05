# CAPIntake — Project Status Report

**Date:** March 31, 2026
**Repository:** https://github.com/filmdc/capintake
**Branch:** master (13 commits)

---

## Executive Summary

CAPIntake is a functional MVP of an open-source client intake and case management system for Community Action Agencies. It replaces expensive proprietary tools (CAPTAIN, GoEngage, CaseWorthy) that cost agencies thousands of dollars per year.

The application covers the full caseworker workflow: client intake with eligibility screening, household management, program enrollment, service delivery tracking, and federal NPI reporting with PDF/CSV export. It is built on Laravel 13 with Filament 4.x and has 105 passing tests.

---

## Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 13.x |
| PHP | Required | 8.3+ |
| Admin Panel | Filament | 4.9.x |
| Frontend | Livewire 3 + Alpine.js | via Filament |
| Database | SQLite (dev) / MySQL / PostgreSQL | any |
| Testing | Pest PHP | 4.x |
| PDF Export | barryvdh/laravel-dompdf | 3.x |
| CSS | Tailwind CSS | 4.x |
| Build | Vite | 8.x (requires Node 20+) |

---

## Codebase Metrics

| Metric | Count |
|--------|-------|
| Total PHP files (app/) | 60 |
| Application code (LOC) | 5,711 |
| Test code (LOC) | 2,330 |
| Database code (LOC) | 1,531 |
| Blade templates (LOC) | 736 |
| **Total LOC** | **~10,300** |
| Models | 13 |
| Filament Resources | 5 (with 33 supporting files) |
| Authorization Policies | 5 |
| Enums | 5 |
| Migrations | 17 |
| Factories | 11 |
| Seeders | 5 |
| Test files | 10 (feature + unit) |
| **Tests passing** | **105 (294 assertions)** |

---

## Domain Model

```
User (admin / supervisor / caseworker)
  |
  |-- enrollments (as caseworker)
  |-- serviceRecords (as provider)

Household
  |-- address, housing_type, household_size
  |-- clients (one-to-many)
  |-- members (HouseholdMember, one-to-many)

Client
  |-- personal info (name, DOB, SSN encrypted, phone, email)
  |-- demographics (race, gender, ethnicity, veteran, disabled)
  |-- intake_status (draft / complete)
  |-- household (belongs-to)
  |-- enrollments (one-to-many)
  |-- serviceRecords (one-to-many)
  |-- incomeRecords (one-to-many)

Enrollment
  |-- client + program + caseworker
  |-- status (pending / active / completed / withdrawn / denied)
  |-- eligibility snapshot (FPL %, income, household size at enrollment)

Program (CSBG, Emergency Services, Weatherization, etc.)
  |-- services (one-to-many)
  |-- FPL threshold for income eligibility

Service
  |-- belongs to a Program
  |-- npiIndicators (many-to-many via pivot)

ServiceRecord
  |-- client + service + enrollment + provider
  |-- service_date, quantity, value

IncomeRecord
  |-- client or household_member
  |-- source, amount, frequency, annual_amount (auto-calculated)
  |-- verification tracking

NpiGoal (7 federal goals) → NpiIndicator (27 indicators)
  |-- mapped to Services via npi_indicator_service pivot

FederalPovertyLevel
  |-- year, household_size, region, poverty_guideline
  |-- seeded for 2025 and 2026
```

---

## Current Capabilities

### 1. Client Intake Wizard

A 5-step guided workflow for new client intake:

- **Step 1 — Client Information:** Personal details, contact info, address, demographics (HUD race categories), veteran/disability status. SSN is encrypted at rest. Reactive duplicate detection checks for matching name+DOB or SSN as the caseworker types.
- **Step 2 — Household:** Create new or link to existing household. Add household members (name, DOB, relationship). Household size auto-calculates.
- **Step 3 — Income & Eligibility:** Add income sources with live annual calculation. FPL percentage computed in real-time against federal poverty guidelines. Documentation flags shown (e.g., self-employment needs tax return).
- **Step 4 — Program Enrollment:** Select programs with eligibility labels shown per-program. Caseworker assignment. Enrollment date.
- **Step 5 — Review & Submit:** Full summary of all entered data before final submission.

**Draft support:** Progress saves to the database after each step. Caseworkers can close the browser and resume later. The browser URL updates with the draft client ID so page refreshes preserve context. Duplicate drafts are prevented by checking for existing drafts with the same name+DOB.

**Orphan cleanup:** When an intake completes, any orphaned draft clients with the same name (from abandoned duplicate tests) are automatically cleaned up along with their empty households.

### 2. CRUD Administration (Filament Resources)

Five Filament Resources provide full list/create/edit/delete interfaces:

| Resource | Key Features |
|----------|-------------|
| **ClientResource** | Full name search, veteran/disabled filters, household address display. Edit page has relation manager tabs for Enrollments, Service Records, and Income Records. Drafts filtered out of listing. |
| **HouseholdResource** | Address, housing type, size. Relation managers for Clients and Members. |
| **ProgramResource** | Name, code, funding source, FPL threshold, fiscal year dates. Relation manager for Services. |
| **EnrollmentResource** | Client, program, caseworker, status badges, eligibility indicator. Filterable by status, program, caseworker. |
| **ServiceRecordResource** | Client, service, enrollment (filtered by selected client), provider, date, quantity, value. Date range and service type filters. |

### 3. Authorization (Role-Based Access)

Three roles: Admin, Supervisor, Caseworker. Each resource has a Policy:

| Action | Admin | Supervisor | Caseworker |
|--------|-------|------------|------------|
| View clients | Yes | Yes | Yes |
| Create clients | Yes | Yes | Yes |
| Delete clients | Yes | No | No |
| Manage programs | Yes | Yes | View only |
| Manage enrollments | Full | Full | Own only |
| Delete service records | Yes | Yes | No |

### 4. Dashboard

Four widgets on the landing page:

- **Stats Overview:** Clients served (this month + this year), new intakes this week (with trend indicator), active enrollments (with top program), unduplicated client count YTD. Each stat has a 7-month sparkline.
- **Quick Actions:** "New Intake" and "Record Service" buttons, draft count badge, instant client search (by name, SSN, or phone) with live dropdown results.
- **My Caseload:** Table of the logged-in caseworker's active enrollments showing client name (linked), program, enrollment date, last service date (color-coded green/warning/red by recency), and service count.
- **Program Breakdown:** Bar chart of clients served per program with month/quarter/year filter.

### 5. NPI Performance Reporting

A dedicated report page for CSBG National Performance Indicator reporting:

- **Date range presets:** Current fiscal year (Oct-Sep), calendar year, quarter, month, or custom range.
- **Program filter:** All programs or a specific program.
- **Report table:** All 7 NPI Goals with their 27 indicators. Each row shows unduplicated client count, total services delivered, and total dollar value.
- **Demographic breakdown:** Collapsible section showing per-indicator breakdowns by race (7 HUD categories), gender (male/female/non-binary), and age range (8 CSBG-standard buckets: 0-5, 6-12, 13-17, 18-24, 25-44, 45-54, 55-64, 65+).
- **Unduplicated counting:** Uses `COUNT(DISTINCT client_id)` at every level — per indicator, per goal, and grand total. A client who receives 5 services under the same indicator counts as 1.
- **Export PDF:** Landscape letter format with goal summary rows, indicator detail rows, demographic breakdown on page 2, and grand total. Formatted for state CSBG office submission.
- **Export CSV:** Includes all indicator data plus demographic columns for analysis.

The NPI service-to-indicator mapping is pre-seeded (24 mappings across 15 services). When a caseworker records a service, the NPI association is automatic — no manual tagging needed.

### 6. Eligibility Engine

- Federal Poverty Level guidelines seeded for 2025 and 2026 (continental US, Alaska, Hawaii).
- Supports household sizes 1-8 with per-person increments for larger households.
- FPL percentage calculated from total household income and household size.
- Programs define their own FPL thresholds (e.g., CSBG = 200%, Emergency Services = 150%).
- Eligibility snapshots captured at enrollment time (income, household size, FPL % frozen).

### 7. Data Protection

- SSN stored using Laravel's `encrypted` cast (AES-256-CBC at rest).
- SSN last four stored separately for quick lookup without decryption.
- Soft deletes on Client, Household, HouseholdMember, Enrollment, ServiceRecord.
- Audit log table structure in place (model, action, old/new values, IP address).

---

## Seeded Reference Data

The `DatabaseSeeder` populates:

| Data | Records |
|------|---------|
| NPI Goals | 7 (Employment, Education, Income, Housing, Health, Civic, Multi-domain) |
| NPI Indicators | 27 (specific measurable outcomes under each goal) |
| Programs | 3 (CSBG, Emergency Services, Weatherization Assistance) |
| Services | 15 (5 per program — e.g., Case Management, Emergency Food, Energy Audit) |
| NPI Mappings | 24 (service-to-indicator associations) |
| Federal Poverty Levels | 48 (8 household sizes x 3 regions x 2 years) |

---

## Test Coverage

| Test Suite | Tests | Assertions |
|------------|-------|------------|
| ClientResourceTest | 9 | list, create, edit, delete, validation, authorization |
| HouseholdResourceTest | 9 | list, create, edit, delete, validation, authorization |
| ProgramResourceTest | 11 | list, create, edit, delete, validation, unique code, authorization |
| EnrollmentResourceTest | 11 | list, create, edit, delete, validation, own-enrollment restriction |
| ServiceRecordResourceTest | 9 | list, create, edit, delete, validation, authorization |
| IntakeWizardTest | 25 | page load, full flow, duplicate detection, draft save/resume, income calculation, draft filtering |
| DashboardWidgetTest | 12 | all 4 widgets render, data accuracy, search, draft count, chart data |
| NpiReportTest | 17 | 7-goal structure, unduplicated counts, date filtering, program filter, demographics, CSV format, page render, presets |
| **Total** | **105** | **294** |

---

## Documentation

| File | Contents |
|------|----------|
| `README.md` | Project overview, features, tech stack, installation guide, comparison table |
| `CLAUDE.md` | Developer conventions, domain vocabulary, code standards, testing rules |
| `docs/USER-GUIDE.md` | Caseworker workflow guide |
| `docs/ADMIN-GUIDE.md` | System administration guide |
| `docs/DEPLOYMENT.md` | Docker, VPS deployment, environment config, backup strategy |

---

## Known Limitations and Open Items

### Functional Gaps
- **Outcome tracking:** The `Outcome` model (linking a ServiceRecord to an NpiCategory for tracking whether someone *achieved* an outcome, not just received a service) is defined in the domain vocabulary but not yet implemented. Currently, NPI reporting counts services delivered, not outcomes achieved.
- **Audit logging:** The `audit_logs` table and `AuditLog` model exist but are not yet wired to automatically record changes. No Filament resource for viewing logs.
- **User management UI:** No Filament Resource for creating/managing users. Users must be created via Tinker or seeder.
- **Password reset:** No password reset flow implemented.

### UX Improvements Identified During Testing
- **Date picker:** Filament's calendar widget is cumbersome for historical dates (DOBs require year-by-year navigation). A direct text input option would help.
- **Orphaned drafts:** Abandoned drafts (where the user never completes the intake) accumulate. A scheduled cleanup job for drafts older than X days would help.
- **Client view page:** Clicking a client opens the edit form directly. A read-only summary view would be preferable for caseworkers who just need to review information.
- **NPI demographic table:** Column headers are truncated at narrow widths. Abbreviated headers or horizontal scrolling needed.
- **PDF export:** Uses `streamDownload` which may not work in all browser configurations. Consider a link-based download as fallback.

### Technical Debt
- `maatwebsite/excel` v1.1.5 (2014) is in `composer.json` but unused — the CSV export uses plain PHP streaming instead. Should be removed from dependencies.
- The `IntakeWizard.php` is 1,313 lines. If it grows further, the step methods could be extracted into separate step classes.
- The Filament 4 Wizard has a known issue where `afterValidation` + `Halt` doesn't fully prevent step advancement because the step index is incremented before the try/catch block. The duplicate detection was refactored to use reactive `live()` fields instead as a workaround.

---

## Commit History

| Commit | Description |
|--------|-------------|
| `da66b55` | Initial Laravel + Filament setup |
| `1e1a3f2` | Core domain models, migrations, and seeders (13 models, 15 migrations, 5 seeders) |
| `8229aac` | Filament resources and authorization policies (5 resources, 5 policies, 8 relation managers) |
| `260bed4` | Pest feature tests for all resources (51 tests) |
| `49566bd` | Project documentation (README, user guide, admin guide, deployment guide) |
| `258124d` | Multi-step client intake wizard (5 steps, draft support, duplicate detection) |
| `2af7c73` | Dashboard widgets (stats, caseload, search, chart) |
| `aae1916` | NPI performance report with PDF and CSV export |
| `6514863` | Program filter, demographic breakdowns, and performance indexes |
| `3b1f052` | Bug fixes from first manual test round (RelationManager imports, FPL seeder, SVG sizing) |
| `06f8e89` | Bug fixes: NPI date range, SVG icons, client title, draft filtering |
| `8a48f9a` | Bug fixes: review step display, draft cleanup, login redirect |
| `39047b5` | Bug fixes: draft resume URL, duplicate prevention, timezone, validation UX, enrollment filter |

---

## Getting Started

```bash
git clone https://github.com/filmdc/capintake.git
cd capintake
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build   # requires Node 20+
php artisan serve
```

Create an admin user:
```bash
php artisan tinker
> App\Models\User::create(['name'=>'Admin', 'email'=>'admin@capintake.test', 'password'=>Hash::make('password'), 'role'=>'admin', 'is_active'=>true]);
```

Login at **http://localhost:8000/admin**.
