# CAPIntake

**Open-source client intake and case management for Community Action Agencies.**

CAPIntake replaces expensive proprietary case management systems like CAPTAIN, GoEngage, and CaseWorthy -- tools that cost agencies thousands of dollars per year. Community Action Agencies serve low-income families and communities. Their budgets should go toward services, not software licenses.

This is the first open-source alternative built specifically for the CAP network.

<!-- Screenshot: Dashboard -->

## Deploy

Deploy your own instance with one click:

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/capintake/capintake)

Or pull the pre-built Docker image:

```bash
docker pull ghcr.io/capintake/capintake:latest
```

See the [Deployment Guide](docs/DEPLOYMENT.md) for Docker Compose, VPS, and other options.

## Who Is This For?

- **Community Action Agencies (CAPs)** that need client intake, enrollment tracking, and federal reporting without the cost of proprietary tools.
- **State CSBG offices** looking for a standardized platform they can offer to their network of local agencies.
- **Nonprofits** running CSBG-funded programs, emergency services, weatherization, or other anti-poverty programs that require NPI reporting.

## Key Features

- **Client Intake** -- Collect client demographics, contact info, and household composition in a streamlined workflow designed to take under 10 minutes.
- **Household Management** -- Track households, household members, addresses, and housing type. Household size auto-calculates.
- **Program Enrollment** -- Enroll clients in programs (CSBG, Emergency Services, Weatherization, and custom programs). Income eligibility is checked automatically against Federal Poverty Level guidelines.
- **Service Tracking** -- Record services delivered to clients with date, quantity, dollar value, and caseworker attribution.
- **Income Records** -- Track income sources for clients and household members with automatic annual income calculation across frequencies (weekly, biweekly, monthly, annually).
- **FPL Eligibility** -- Built-in Federal Poverty Level guidelines (continental US, Alaska, Hawaii) with automatic percentage-of-FPL calculation per household.
- **NPI Reporting** -- National Performance Indicators (Goals 1-7) are pre-loaded with all standard indicators. Services map to NPI indicators for unduplicated client counts and federal reporting.
- **Audit Logging** -- Every create, update, and delete is logged with old/new values, user, and IP address.
- **Role-Based Access** -- Three roles: Administrator, Supervisor, and Caseworker. Policies enforce authorization on every resource.
- **Exports** -- PDF and Excel exports via DomPDF and Laravel Excel.
- **Soft Deletes** -- Clients, households, enrollments, and service records are soft-deleted, never permanently lost.
- **Encrypted PII** -- SSN and other sensitive fields are encrypted at rest using Laravel's built-in encryption.

<!-- Screenshot: Client Intake Form -->
<!-- Screenshot: Enrollment List -->
<!-- Screenshot: Service Record Entry -->
<!-- Screenshot: NPI Reporting -->

## Tech Stack

| Layer          | Technology                              |
|----------------|----------------------------------------|
| Framework      | Laravel 13 (PHP 8.3+)                  |
| Admin Panel    | Filament 4.x                           |
| Frontend       | Livewire 3 + Alpine.js                 |
| Database       | MySQL 8 / PostgreSQL 15 / SQLite       |
| CSS            | Tailwind CSS 4                         |
| Testing        | Pest PHP                               |
| Exports        | Laravel Excel + DomPDF                 |
| Build          | Vite 8                                 |

## Comparison

| Feature                          | CAPIntake          | CAPTAIN            | GoEngage           | CaseWorthy         |
|----------------------------------|--------------------|--------------------|--------------------|---------------------|
| **Cost**                         | Free (AGPL-3.0)    | ~$3,000-8,000/yr   | ~$5,000-15,000/yr  | ~$5,000-20,000/yr   |
| **Open Source**                  | Yes                | No                 | No                 | No                  |
| **Self-Hosted**                  | Yes                | No                 | No                 | No                  |
| **NPI Reporting**                | Built-in           | Built-in           | Built-in           | Built-in            |
| **FPL Eligibility**              | Automatic          | Manual/Semi-auto   | Manual/Semi-auto   | Semi-auto           |
| **Client Intake**                | Under 10 min       | Varies             | Varies             | Varies              |
| **Customizable Programs**        | Yes                | Limited            | Limited            | Yes                 |
| **Audit Trail**                  | Full               | Varies             | Varies             | Full                |
| **Data Ownership**               | You own it all     | Vendor-hosted      | Vendor-hosted      | Vendor-hosted       |
| **No Vendor Lock-In**            | Yes                | No                 | No                 | No                  |

## System Requirements

- PHP 8.3 or higher
- Composer 2.x
- Node.js 18+ and npm
- One of: MySQL 8, PostgreSQL 15, or SQLite (SQLite works for local development)
- A web server (Nginx recommended for production, or `php artisan serve` for development)

## Quick Start (Windows)

**No command line required.** Download or clone the repo, then double-click `install.bat`. The installer will:

1. Check for PHP, Composer, and Node.js (installs any that are missing)
2. Install all dependencies and build the app
3. Set up the database with all CSBG reference data pre-loaded
4. Create a **CAPIntake** shortcut on your desktop

After install, double-click the desktop shortcut to start the app. The setup wizard will walk you through configuring your agency.

> Already have PHP and Composer? Run `composer setup && composer serve` instead.

## Quick Start (Mac / Linux)

```bash
# Clone the repository
git clone https://github.com/capintake/capintake.git
cd capintake

# One-command setup: install deps, generate key, migrate, seed, build assets
composer setup

# Start the server
composer serve
```

The application will be available at `http://localhost:8000`.

### Default Login

After running `php artisan migrate --seed`, the database is populated with:

- **Federal Poverty Level guidelines** (2025 HHS data for continental US, Alaska, and Hawaii)
- **NPI Goals and Indicators** (all 7 CSBG National Performance Indicator goals with their indicators)
- **Sample Programs**: Community Services Block Grant (CSBG), Emergency Services, and Weatherization Assistance -- each with pre-configured services
- **NPI-to-Service mappings** linking services to their corresponding NPI indicators

To create your first admin user, use Laravel Tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Enums\UserRole;

User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => UserRole::Admin,
]);
```

Then log in at `http://localhost:8000/admin` with those credentials.

### Development Mode

For a full development environment with hot-reloading, queue worker, and log tailing:

```bash
composer dev
```

This starts the web server, Vite dev server, queue listener, and log tail concurrently.

## Documentation

- [User Guide](docs/USER-GUIDE.md) -- For caseworkers: intake workflow, enrollments, service records, income tracking.
- [Admin Guide](docs/ADMIN-GUIDE.md) -- For administrators: user management, program setup, NPI configuration, audit logs.
- [Deployment Guide](docs/DEPLOYMENT.md) -- Docker, VPS, environment configuration, backups, and updates.

## Why This Exists

Community Action Agencies are the front line of the fight against poverty in the United States. Over 1,000 CAPs operate across the country, serving millions of low-income individuals and families every year through programs funded by the Community Services Block Grant (CSBG) and other federal, state, and local sources.

These agencies are required to track client demographics, household income, program enrollments, services delivered, and outcomes -- then report it all to their state CSBG office using National Performance Indicators. This requires case management software.

The problem: the tools available to CAPs are expensive proprietary systems. CAPTAIN, GoEngage, and CaseWorthy charge thousands of dollars per year in licensing fees. For agencies with tight budgets -- agencies whose entire mission is serving people who cannot afford basic needs -- these costs are a real burden.

There has never been an open-source alternative built for this specific domain. CAPIntake is that alternative. It is designed by people who understand the CAP network, built with the exact vocabulary caseworkers use, and focused on reducing the time and complexity of client intake and federal reporting.

Every dollar an agency saves on software is a dollar that can go toward helping a family keep their lights on, put food on the table, or avoid eviction.

## Project Structure

```
capintake/
  app/
    Enums/           # UserRole, EnrollmentStatus, IncomeFrequency, EmploymentStatus
    Models/           # Client, Household, HouseholdMember, Program, Service,
                      # Enrollment, ServiceRecord, IncomeRecord, NpiGoal,
                      # NpiIndicator, FederalPovertyLevel, AuditLog, User
    Filament/         # Filament admin panel resources (all CRUD)
    Policies/         # Authorization policies for every resource
  database/
    factories/        # Test factories for every model
    migrations/       # Schema definitions
    seeders/          # FPL data, NPI goals/indicators, sample programs
  docs/               # User guide, admin guide, deployment guide
  tests/              # Pest PHP feature and unit tests
```

## Contributing

Contributions are welcome. Before submitting a pull request:

1. Read `CLAUDE.md` for project conventions and code standards.
2. Every model must have a factory. Every Filament Resource must have a policy.
3. Run `php artisan test` and ensure all tests pass.
4. Keep PRs small -- one concern per commit.
5. Use the domain vocabulary exactly as defined (Client, Household, Enrollment, etc.).

If you find a bug, please open an issue with steps to reproduce it.

## License

CAPIntake is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](https://www.gnu.org/licenses/agpl-3.0.en.html).

This means you can use, modify, and distribute the software freely, but if you run a modified version as a network service, you must make the source code available to users of that service.
