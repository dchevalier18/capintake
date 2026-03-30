# CAPIntake Administrator Guide

This guide is for system administrators who configure programs, manage users, maintain reference data, and oversee the CAPIntake installation.

## Table of Contents

- [User Management](#user-management)
- [Program Configuration](#program-configuration)
- [Service Setup](#service-setup)
- [NPI Indicator Mapping](#npi-indicator-mapping)
- [Federal Poverty Level Data](#federal-poverty-level-data)
- [Viewing Audit Logs](#viewing-audit-logs)
- [System Settings](#system-settings)

---

## User Management

### Roles

CAPIntake has three user roles:

| Role            | Permissions                                                              |
|-----------------|--------------------------------------------------------------------------|
| **Administrator** | Full system access. Can manage users, programs, services, NPI configuration, FPL data, and system settings. Can view audit logs. |
| **Supervisor**  | Can manage clients, households, enrollments, service records, and income records. Can view reports. Can manage users within their scope. |
| **Caseworker**  | Can create and manage clients, households, enrollments, service records, and income records. Cannot access admin-only resources like user management, program configuration, or system settings. |

### Creating a User

1. Navigate to **Users** in the sidebar (Administrators only).
2. Click **New User**.
3. Fill in the fields:
   - **Name** -- The user's full name.
   - **Email** -- Must be unique. This is the login identifier.
   - **Password** -- Set an initial password. The user should change it after first login.
   - **Role** -- Select Administrator, Supervisor, or Caseworker.
   - **Phone** (optional) -- The user's phone number.
   - **Title** (optional) -- The user's job title (for example, "Senior Caseworker" or "Program Director").
   - **Active** -- Toggle whether the user can log in. Defaults to active.
4. Click **Create**.

### Deactivating a User

To prevent a user from logging in without deleting their record:

1. Navigate to **Users**.
2. Edit the user.
3. Uncheck the **Active** toggle.
4. Click **Save**.

Deactivated users cannot log in, but their historical records (enrollments they managed, services they provided) remain intact and attributed to them.

### Deleting a User

Users are soft-deleted. When you delete a user:

- They can no longer log in.
- Their name still appears on historical enrollments and service records.
- The record can be restored if needed.

### Password Resets

If a user forgets their password, an administrator can edit the user record and set a new password. There is no self-service password reset unless your agency configures a mail driver (see [System Settings](#system-settings)).

---

## Program Configuration

Programs represent the funded initiatives your agency runs. Each program can have its own eligibility requirements and services.

### Creating a Program

1. Navigate to **Programs** in the sidebar.
2. Click **New Program**.
3. Fill in the fields:
   - **Name** -- The full program name (for example, "Community Services Block Grant").
   - **Code** -- A short unique code (for example, "CSBG"). This is used in service codes and reporting.
   - **Description** -- A brief description of the program's purpose.
   - **Funding Source** -- Where the funding comes from (CSBG, federal, state, local, private).
   - **Fiscal Year Start** -- The first day of the program's fiscal year. Federal programs typically use October 1.
   - **Fiscal Year End** -- The last day of the fiscal year. Federal programs typically use September 30.
   - **Requires Income Eligibility** -- Check this if clients must meet an income threshold to enroll. Most federally funded programs require this.
   - **FPL Threshold Percent** -- The maximum FPL percentage allowed for eligibility. For example, 200 means clients must be at or below 200% of the Federal Poverty Level.
   - **Active** -- Whether the program is currently accepting enrollments.
4. Click **Create**.

### Pre-Loaded Programs

The database seeder creates three programs:

| Program                        | Code  | FPL Threshold | Funding Source |
|--------------------------------|-------|---------------|----------------|
| Community Services Block Grant | CSBG  | 200%          | CSBG           |
| Emergency Services             | EMRG  | 150%          | CSBG           |
| Weatherization Assistance      | WAP   | 200%          | Federal        |

You can edit these programs or add new ones to match your agency's specific program portfolio.

### Deactivating a Program

Set a program's **Active** toggle to off to prevent new enrollments. Existing enrollments and service records are not affected.

---

## Service Setup

Services are the specific types of assistance available under a program. Each service has a code, unit of measure, and is linked to a parent program.

### Creating a Service

1. Navigate to the program's detail page, or navigate to **Services** directly.
2. Click **New Service**.
3. Fill in the fields:
   - **Program** -- Select the parent program.
   - **Name** -- The service name (for example, "Emergency Food Box" or "Case Management").
   - **Code** -- A short code (for example, "EMRG-FOOD"). Convention: use the program code as a prefix.
   - **Description** -- What this service provides.
   - **Unit of Measure** -- How the service is quantified:
     - **instance** -- A single occurrence (information and referral, an energy audit).
     - **hour** -- Time-based services (case management, employment training).
     - **dollar** -- Dollar-denominated assistance (rent payment, utility payment).
     - **item** -- Physical items (food boxes, clothing).
   - **Active** -- Whether this service is available for new service records.
4. Click **Create**.

### Pre-Loaded Services

The seeder creates 15 services across the three default programs:

**CSBG:**
- Case Management (CSBG-CM) -- hour
- Information and Referral (CSBG-IR) -- instance
- Financial Literacy Workshop (CSBG-FLW) -- instance
- Employment Readiness Training (CSBG-ERT) -- hour
- Tax Preparation / VITA (CSBG-VITA) -- instance

**Emergency Services:**
- Emergency Food Box (EMRG-FOOD) -- item
- Emergency Rent Assistance (EMRG-RENT) -- dollar
- Emergency Utility Payment (EMRG-UTIL) -- dollar
- Emergency Prescription Assistance (EMRG-RX) -- dollar
- Emergency Clothing Voucher (EMRG-CLO) -- dollar

**Weatherization:**
- Energy Audit (WAP-AUDIT) -- instance
- Insulation Installation (WAP-INS) -- instance
- Furnace Repair/Replacement (WAP-FURN) -- instance
- Air Sealing (WAP-SEAL) -- instance
- Window/Door Replacement (WAP-WIN) -- instance

---

## NPI Indicator Mapping

National Performance Indicators (NPIs) are the federal reporting framework for CSBG-funded agencies. CAPIntake comes pre-loaded with all seven NPI goals and their standard indicators.

### NPI Goals

| Goal | Name                                          |
|------|-----------------------------------------------|
| 1    | Employment                                    |
| 2    | Education and Cognitive Development            |
| 3    | Income and Asset Building                      |
| 4    | Housing                                        |
| 5    | Health and Social/Behavioral Development       |
| 6    | Civic Engagement and Community Involvement     |
| 7    | Services Supporting Multiple Domains           |

Each goal contains multiple indicators (for example, Goal 1 has indicators 1.1, 1.2, and 1.3). These are seeded from the CSBG Annual Report format.

### How NPI Mapping Works

Services are mapped to NPI indicators through a many-to-many relationship. When a service record is created for a mapped service, it contributes to the count for that NPI indicator.

For example:
- The service "Emergency Food Box" (EMRG-FOOD) is mapped to NPI indicators 7.1 (Emergency assistance for immediate needs) and 7.2 (Emergency food assistance).
- Every client who receives an Emergency Food Box in a reporting period counts toward indicators 7.1 and 7.2.
- Clients are counted as **unduplicated** -- a client who receives three food boxes in the period counts as one client for NPI reporting.

### Viewing and Editing Mappings

1. Navigate to **Services**.
2. Edit the service whose NPI mapping you want to change.
3. In the NPI Indicators section, add or remove indicator associations.
4. Click **Save**.

### Pre-Loaded Mappings

The seeder establishes these default mappings:

| Service Code | NPI Indicators       |
|-------------|----------------------|
| CSBG-ERT    | 1.1, 1.2             |
| CSBG-FLW    | 2.2, 3.4             |
| CSBG-VITA   | 3.1                  |
| CSBG-IR     | 3.3                  |
| CSBG-CM     | 1.2, 3.3, 4.2        |
| EMRG-RENT   | 4.1, 4.3, 7.4        |
| EMRG-RX     | 5.1, 7.5             |
| EMRG-FOOD   | 7.1, 7.2             |
| EMRG-UTIL   | 7.1, 7.3             |
| EMRG-CLO    | 7.1                  |
| WAP-AUDIT   | 4.2                  |
| WAP-INS     | 4.2                  |
| WAP-FURN    | 4.2                  |
| WAP-SEAL    | 4.2                  |
| WAP-WIN     | 4.2                  |

Adjust these mappings to match your agency's reporting methodology. Different agencies may interpret service-to-indicator mappings differently based on their state CSBG office guidance.

---

## Federal Poverty Level Data

CAPIntake uses the HHS Poverty Guidelines to determine income eligibility. This data is stored in the `federal_poverty_levels` table and must be updated each year when new guidelines are published (typically in January or February).

### Pre-Loaded Data

The seeder includes the 2025 HHS Poverty Guidelines for all three regions:

**Continental US (48 states + DC):**

| Household Size | Poverty Guideline |
|---------------|-------------------|
| 1             | $15,650           |
| 2             | $21,150           |
| 3             | $26,650           |
| 4             | $32,150           |
| 5             | $37,650           |
| 6             | $43,150           |
| 7             | $48,650           |
| 8             | $54,150           |

For each additional person above 8, add $5,500.

Alaska and Hawaii have higher guideline amounts, which are also included in the seed data.

### Updating FPL Data Annually

When HHS publishes new poverty guidelines each year:

1. Navigate to **Federal Poverty Levels** in the admin panel.
2. Add new records for the new year, or update existing ones.
3. Each record requires: **Year**, **Household Size** (1-8), **Poverty Guideline** (annual income in dollars), and **Region** (continental, alaska, or hawaii).

Alternatively, you can update the `FederalPovertyLevelSeeder` with the new year's data and run:

```bash
php artisan db:seed --class=FederalPovertyLevelSeeder
```

The seeder uses `updateOrCreate`, so running it again will update existing records without creating duplicates.

### How FPL Calculations Work

For households of 8 or fewer members, the system looks up the guideline directly.

For households larger than 8, the system:
1. Looks up the guideline for a household of 8.
2. Calculates the per-person increment (the difference between size 8 and size 7).
3. Adds the increment for each additional person beyond 8.

The default fallback per-person increment for continental US is $5,140 (2025 value), used only if the lookup fails.

---

## Viewing Audit Logs

CAPIntake logs every create, update, and delete action performed on records. This is essential for compliance and accountability.

### What Is Logged

Each audit log entry contains:

| Field              | Description                                                      |
|--------------------|------------------------------------------------------------------|
| **User**           | The user who performed the action.                               |
| **Auditable Type** | The model that was changed (Client, Enrollment, ServiceRecord, etc.). |
| **Auditable ID**   | The ID of the specific record that was changed.                  |
| **Action**         | What happened: created, updated, deleted, or restored.           |
| **Old Values**     | The previous values of changed fields (for updates and deletes). |
| **New Values**     | The new values of changed fields (for creates and updates).      |
| **IP Address**     | The IP address of the user at the time of the action.            |
| **Timestamp**      | When the action occurred.                                        |

### Viewing Audit Logs

1. Navigate to **Audit Logs** in the sidebar (Administrators only).
2. Use filters to narrow results:
   - Filter by **User** to see all actions by a specific person.
   - Filter by **Auditable Type** to see all changes to a specific type of record.
   - Filter by **Action** to see only creates, updates, or deletes.
   - Filter by **Date Range** to focus on a specific time period.

Audit logs are read-only. They cannot be edited or deleted.

---

## System Settings

### Environment Configuration

Core system settings are managed through the `.env` file on the server. Key settings for administrators to be aware of:

| Setting              | Description                                          | Example                     |
|----------------------|------------------------------------------------------|-----------------------------|
| `APP_NAME`           | The name shown in the browser title and navigation.  | `CAPIntake`                 |
| `APP_URL`            | The public URL of your installation.                 | `https://intake.agency.org` |
| `APP_ENV`            | Set to `production` on live servers.                 | `production`                |
| `APP_DEBUG`          | Must be `false` in production.                       | `false`                     |
| `DB_CONNECTION`      | Database driver: `mysql`, `pgsql`, or `sqlite`.      | `mysql`                     |
| `MAIL_MAILER`        | Mail driver for notifications and password resets.   | `smtp`                      |
| `QUEUE_CONNECTION`   | Queue backend: `database`, `redis`, or `sync`.       | `database`                  |

Changes to the `.env` file require restarting the application (or clearing the config cache with `php artisan config:clear`).

### Cache Management

If the system is behaving unexpectedly after a configuration change, clear the caches:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

For production environments, rebuild the caches after clearing:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Maintenance Mode

To take the application offline for maintenance:

```bash
# Enable maintenance mode
php artisan down

# Enable with a secret bypass token
php artisan down --secret="your-secret-token"

# Disable maintenance mode
php artisan up
```

While in maintenance mode, all requests receive a 503 response. If you set a secret, you can bypass maintenance mode by visiting `https://your-url.com/your-secret-token` in your browser.
