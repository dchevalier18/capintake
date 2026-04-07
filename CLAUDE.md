# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

CAPIntake is an open-source client intake and case management system for Community Action Agencies (CAPs). It replaces expensive proprietary tools (CAPTAIN, GoEngage, CaseWorthy) that cost agencies thousands per year. Licensed AGPL-3.0.

## Commands

```bash
composer setup       # First-time: install deps, generate key, migrate, build assets
composer dev         # Dev server: Laravel + queue + log tail + Vite (hot reload)
composer test        # Clear config cache, then run Pest tests (in-memory SQLite)
php artisan test --filter=ClientResourceTest   # Run a single test file
php artisan test --filter="it can create"      # Run tests matching a name
./vendor/bin/pint    # PHP code style fixer (PSR-12)
npm run build        # Production Vite build (requires Node 20+)
npm run dev          # Vite dev server only
php artisan migrate --seed   # Run migrations + seed reference data
```

Tests use in-memory SQLite (`phpunit.xml` sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

## Tech Stack

- **Framework:** Laravel 13 (PHP 8.3+, `declare(strict_types=1)` in every file)
- **Admin Panel:** Filament 4.x (all CRUD via Filament Resources, all pages via Filament Pages)
- **Frontend:** Livewire 3 + Alpine.js via Filament (no separate SPA, no React/Vue/Inertia)
- **Database:** SQLite (dev) / MySQL 8 / PostgreSQL 15
- **CSS:** Tailwind CSS 4 via Vite 8
- **Testing:** Pest PHP 4 with `pestphp/pest-plugin-laravel`
- **PDF Export:** barryvdh/laravel-dompdf
- **Auth:** Filament built-in auth with role-based access. User model must implement `FilamentUser`.

## Architecture

**Entry point:** `/` redirects to `/admin` (Filament panel). The entire UI is the Filament admin panel configured in `app/Providers/Filament/AdminPanelProvider.php`.

**Setup flow:** `EnsureSetupComplete` middleware redirects all authenticated requests to `/admin/setup` until `AgencySetting::isSetupComplete()` returns true. The `SetupWizard` page configures agency identity, branding, and fiscal year.

**White-labeling:** Agency name, logo, and primary color are stored in `AgencySetting` (singleton). `AdminPanelProvider::applyBranding()` reads these at boot. Use `AgencySetting::current()` — never hardcode agency name or branding.

**Key directories:**
- `app/Filament/Resources/` — 11 Filament Resources (Client, Household, Program, Enrollment, ServiceRecord, User, Outcome, FederalPovertyLevel, FundingSource, LookupCategory, CommunityInitiative, CsbgExpenditure)
- `app/Filament/Pages/` — IntakeWizard, NpiReport, CsbgAnnualReport, SetupWizard, DataQualityDashboard, AuditLogViewer, FnpiTargets, SrvCodeMapping, CsbgReportSettings
- `app/Filament/Widgets/` — StatsOverview, QuickActions, MyCaseload, ProgramBreakdown, TargetsVsActuals, UpcomingFollowUps
- `app/Services/` — Lookup (configurable dropdown values), NpiReportService, CsbgReportService, CsbgReportPdfExporter, DataQualityService, TrendAnalysisService
- `app/Policies/` — 20 authorization policies, one per resource. Every resource must have a policy.
- `app/Enums/` — UserRole, EnrollmentStatus, IncomeFrequency, IntakeStatus, CasePlanStatus, GoalStatus, FollowUpStatus, OutcomeStatus, ReferralStatus, CnpiType, CasePlanStatus
- `database/seeders/` — DatabaseSeeder calls: LookupSeeder → FederalPovertyLevelSeeder → NpiSeeder → ProgramSeeder → NpiServiceMappingSeeder → CsbgSrvCategorySeeder → CnpiIndicatorSeeder → CsbgStrCategorySeeder → AdminUserSeeder

**Configurable lookups:** `LookupCategory` and `LookupValue` models provide admin-configurable dropdown values (race, gender, housing type, etc.) managed via `LookupCategoryResource`. The `Lookup` service class (`app/Services/Lookup.php`) provides a facade for retrieving these values.

**Routes:** Minimal — only `/` (redirect to `/admin`) and two authenticated CSBG export routes (`/csbg/export/csv`, `/csbg/export/pdf`) via `CsbgExportController`. Everything else is Filament auto-discovered.

## Domain Vocabulary

Use these exact terms — they match how CAP agencies talk:

| Model | What it represents |
|---|---|
| `Client` | An individual seeking services (the primary intake record) |
| `Household` | A group of people living together; a Client belongs to a Household |
| `HouseholdMember` | A person in the household (may or may not be a Client) |
| `Program` | A funded program the agency runs (CSBG, Emergency Services, etc.) |
| `Enrollment` | A Client's enrollment in a specific Program, with eligibility status |
| `Service` | A type of service available under a Program |
| `ServiceRecord` | An actual service delivered to a Client (date, caseworker, notes) |
| `IncomeRecord` | An income source for a Client or HouseholdMember (amount, frequency) |
| `NpiGoal` / `NpiIndicator` | Federal National Performance Indicator goals (7) and indicators (27) |
| `CnpiIndicator` / `CnpiResult` | Community NPI indicators and results |
| `Outcome` | Links a ServiceRecord to an NPI indicator for outcome tracking |
| `FederalPovertyLevel` | FPL guidelines by year, household size, and region |
| `CasePlan` / `CasePlanGoal` | Client case plans with measurable goals |
| `FollowUp` | Scheduled follow-ups for clients |
| `Referral` | Client referrals to external services |
| `SelfSufficiencyAssessment` | Client self-sufficiency scoring |
| `FundingSource` | Funding sources for programs |
| `CsbgExpenditure` | CSBG expenditure tracking for annual report |
| `CommunityInitiative` | Community-level initiatives for CSBG reporting |
| `AgencySetting` | Singleton for agency identity, branding, fiscal year config |
| `AuditLog` | Polymorphic record of model changes (who, what, when, old/new vals) |
| `User` | System user: admin, supervisor, or caseworker |

## Authorization Model

Three roles (`UserRole` enum): **Admin**, **Supervisor**, **Caseworker**.

| Action | Admin | Supervisor | Caseworker |
|---|---|---|---|
| View clients | Yes | Yes | Yes |
| Create/edit clients | Yes | Yes | Yes |
| Delete clients | Yes | No | No |
| Manage programs | Yes | Yes | View only |
| Manage enrollments | Full | Full | Own only |
| Delete service records | Yes | Yes | No |

Every Filament Resource must have a corresponding Policy in `app/Policies/`.

## Code Standards

- PSR-12 style. `declare(strict_types=1)` in every PHP file.
- Named routes: `route('clients.show', $client)`.
- Form Requests for all validation — never validate inline in controllers or resources.
- All admin CRUD goes through Filament Resources. No custom controllers for admin screens.
- Encrypt PII at rest: SSN, DOB, and income fields use Laravel's `encrypted` cast. SSN last four stored separately for lookup without decryption.
- Soft deletes on Client, Household, HouseholdMember, Enrollment, ServiceRecord.
- Every model relationship explicitly defined. Migrations include indexes on foreign keys and filter columns.

## Testing

- **Framework:** Pest PHP. `uses(TestCase::class)->in('Feature')` configured in `tests/Pest.php`.
- Every model must have a factory in `database/factories/`.
- Every Filament Resource must have Pest feature tests covering: list, create, edit, delete, validation errors, and authorization.
- Use `RefreshDatabase` trait in all feature tests.
- Test authorization: caseworkers cannot access admin-only resources.

## What NOT To Do

- **Don't over-engineer.** No event sourcing, no microservices, no GraphQL.
- **Don't add features outside scope.** No "while I'm here" additions. Open an issue and move on.
- **Don't break existing tests.** Fix your change, not the test — unless the test is genuinely wrong.
- **Don't use `$table->string()` for sensitive fields.** SSN and PII must use encrypted storage.
- **Don't create Resources without a Policy.** Every resource needs a policy. No exceptions.
- **Don't skip the factory.** Model + factory in the same commit.
- **Don't build a separate frontend.** Everything goes through Filament and Livewire.
- **Don't log SSN, password, or remember_token in audit logs.**
- **Don't hardcode agency name or branding.** Use `AgencySetting::current()`.

## Known Gotchas

- **Filament 4 Wizard `afterValidation` + `Halt` doesn't fully prevent step advancement.** The step index increments before try/catch. Use reactive `live()` fields for inline validation instead.
- **`IntakeWizard.php` is ~1280 lines.** If adding new steps, consider extracting step logic into separate classes.
- **Filament 4 action imports differ from v3.** Use `\Filament\Actions\CreateAction` (not `Filament\Tables\Actions\CreateAction`).
- **Filament 4 property types are strict.** `$navigationGroup` must be `string|\UnitEnum|null`. Check parent class signatures.
- **SQLite BETWEEN with datetimes.** `BETWEEN '2026-03-30' AND '2026-03-30'` excludes `'2026-03-30 00:00:00'`. Use `endOfDay()` or append `' 23:59:59'`.
- **User model must implement `FilamentUser` interface.** Without it, `APP_ENV=testing` or production returns 403 for all authenticated users.
- **Heroicon Blade components in custom widget views.** Use inline SVG with explicit `style="width:1.25rem;height:1.25rem"` in custom Livewire views.
