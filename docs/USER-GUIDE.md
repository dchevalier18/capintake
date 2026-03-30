# CAPIntake User Guide

This guide is for caseworkers and supervisors who use CAPIntake day-to-day to manage client intake, enrollments, and service delivery.

## Table of Contents

- [Logging In](#logging-in)
- [Dashboard Overview](#dashboard-overview)
- [Adding a New Client (Intake)](#adding-a-new-client-intake)
- [Managing Households](#managing-households)
- [Managing Household Members](#managing-household-members)
- [Enrolling a Client in a Program](#enrolling-a-client-in-a-program)
- [Recording Services Delivered](#recording-services-delivered)
- [Managing Income Records](#managing-income-records)
- [Understanding Eligibility and FPL](#understanding-eligibility-and-fpl)
- [Searching and Filtering Records](#searching-and-filtering-records)

---

## Logging In

1. Open your browser and navigate to your agency's CAPIntake URL (for example, `https://intake.youragency.org/admin`).
2. Enter your email address and password.
3. Click **Sign In**.

If you cannot log in, contact your system administrator. They can reset your password or check whether your account is active.

Your user account has one of three roles:

| Role            | What You Can Do                                                      |
|-----------------|----------------------------------------------------------------------|
| **Caseworker**  | Create and manage clients, enrollments, and service records          |
| **Supervisor**  | Everything a caseworker can do, plus view reports and manage users    |
| **Administrator** | Full access to all system settings, programs, NPI configuration, and user management |

---

## Dashboard Overview

After logging in, you land on the admin dashboard. From here you can navigate to all sections of the application using the sidebar menu. Key sections include:

- **Clients** -- View, search, and manage all clients in the system.
- **Households** -- View and manage household records including address and composition.
- **Programs** -- View available programs (CSBG, Emergency Services, Weatherization, etc.).
- **Enrollments** -- View and manage all client-program enrollments.
- **Service Records** -- View and manage records of services delivered.
- **Income Records** -- View and manage income information for clients and household members.

The sidebar also provides access to administrative sections if your role permits (Users, NPI Configuration, Federal Poverty Levels, Audit Logs).

---

## Adding a New Client (Intake)

Intake is the core workflow. The goal is to complete a new client record in under 10 minutes.

### Step 1: Create or Select a Household

Before adding a client, you need a household record. A household represents the group of people living together at one address.

1. Navigate to **Households** in the sidebar.
2. Click **New Household**.
3. Fill in the address fields:
   - **Address Line 1** (required) -- Street address.
   - **Address Line 2** (optional) -- Apartment, unit, or suite number.
   - **City** (required)
   - **State** (required) -- Two-letter state abbreviation.
   - **ZIP** (required)
   - **County** (optional but recommended -- many reports filter by county).
   - **Housing Type** (optional) -- Own, Rent, Homeless, Transitional, or Other.
4. Click **Create**.

If the household already exists (for example, a second family member is seeking services), search for the existing household instead of creating a new one.

### Step 2: Add the Client

1. Navigate to **Clients** in the sidebar.
2. Click **New Client**.
3. Fill in the required fields:
   - **Household** -- Select the household you just created (or search for an existing one).
   - **First Name** (required)
   - **Last Name** (required)
   - **Date of Birth** (required)
4. Fill in additional fields as available:
   - **Middle Name**
   - **SSN** -- This field is encrypted at rest. Only the last four digits are stored in plain text for lookup purposes.
   - **Phone**
   - **Email**
   - **Gender**
   - **Race** (HUD categories)
   - **Ethnicity** (Hispanic/Latino or Not Hispanic/Latino)
   - **Veteran Status**
   - **Disability Status**
   - **Head of Household** -- Check this if this client is the head of household.
   - **Relationship to Head** -- Self, Spouse, Child, Parent, or Other.
   - **Preferred Language** -- Defaults to English.
   - **Notes** -- Any additional information relevant to intake.
5. Click **Create**.

### Step 3: Add Household Members (If Any)

If other people live in the household who are not the client, add them as household members. See the [Managing Household Members](#managing-household-members) section below.

### Step 4: Add Income Records

Document all income sources for the client and household members. This is essential for determining program eligibility. See the [Managing Income Records](#managing-income-records) section below.

### Step 5: Enroll in a Program

Once the client and household information is complete, you can enroll the client in the appropriate program. See [Enrolling a Client in a Program](#enrolling-a-client-in-a-program) below.

---

## Managing Households

A household represents a group of people living at the same address. It tracks:

- **Address** -- Full street address including city, state, ZIP, and county.
- **Housing Type** -- Own, Rent, Homeless, Transitional, or Other.
- **Household Size** -- Automatically calculated as the number of clients plus the number of household members associated with this household.

### Editing a Household

1. Navigate to **Households**.
2. Click on the household you want to edit.
3. Update the fields as needed.
4. Click **Save**.

The household size recalculates automatically when clients or household members are added or removed.

### Viewing Household Income

The household detail view shows the total annual income across all clients and household members in the household. This total is used for FPL eligibility calculations.

---

## Managing Household Members

Household members are people who live in the household but are not the primary client. They might be a spouse, children, parents, or other relatives.

### Adding a Household Member

1. Navigate to **Household Members** (or access them from within a household record).
2. Click **New Household Member**.
3. Fill in the fields:
   - **Household** -- Select the household this person belongs to.
   - **First Name** (required)
   - **Last Name** (required)
   - **Relationship to Client** -- Spouse, Child, Parent, Sibling, Grandchild, or Other.
   - **Date of Birth**
   - **Gender**
   - **Race**
   - **Ethnicity**
   - **Employment Status** -- Employed (Full-Time), Employed (Part-Time), Unemployed, Retired, Disabled, Student, Homemaker, or Self-Employed.
   - **Veteran Status**
   - **Disability Status**
   - **Student Status**
   - **Education Level** -- Less than High School, HS/GED, Some College, Associates, Bachelors, or Graduate.
   - **Health Insurance** -- Medicaid, Medicare, Employer, Marketplace, None, or Other.
4. Click **Create**.

Adding a household member automatically updates the household size.

---

## Enrolling a Client in a Program

An enrollment connects a client to a specific program. The system captures eligibility information at the time of enrollment so it is preserved even if the client's income changes later.

### Creating an Enrollment

1. Navigate to **Enrollments**.
2. Click **New Enrollment**.
3. Fill in the fields:
   - **Client** -- Search for and select the client.
   - **Program** -- Select the program (for example, CSBG, Emergency Services, or Weatherization Assistance).
   - **Caseworker** -- Select the caseworker assigned to manage this enrollment.
   - **Enrollment Date** -- The date the client is being enrolled.
   - **Status** -- Typically starts as **Pending** or **Active**.
4. Click **Create**.

When an enrollment is created, the system can snapshot the client's eligibility information:

- **Household Income at Enrollment** -- The total annual household income at the time of enrollment.
- **Household Size at Enrollment** -- The number of people in the household at enrollment.
- **FPL Percent at Enrollment** -- The household's income as a percentage of the Federal Poverty Level.
- **Income Eligible** -- Whether the client meets the program's income eligibility threshold.

### Enrollment Statuses

| Status        | Meaning                                                                 |
|---------------|-------------------------------------------------------------------------|
| **Pending**   | Enrollment has been started but not yet approved or activated.          |
| **Active**    | Client is actively enrolled and receiving services.                     |
| **Completed** | Client has completed the program or services have concluded.            |
| **Withdrawn** | Client withdrew from the program before completion.                     |
| **Denied**    | Client was denied enrollment (usually due to ineligibility).            |

If an enrollment is denied, record the reason in the **Denial Reason** field. Use the **Eligibility Notes** field for any additional context about the eligibility determination.

---

## Recording Services Delivered

A service record documents a specific service delivered to a client on a specific date.

### Creating a Service Record

1. Navigate to **Service Records**.
2. Click **New Service Record**.
3. Fill in the fields:
   - **Client** -- Select the client who received the service.
   - **Service** -- Select the service type (for example, Emergency Food Box, Case Management, Energy Audit). Services are organized under their parent program.
   - **Enrollment** -- Select the enrollment this service is being provided under.
   - **Provided By** -- Select the caseworker or staff member who provided the service.
   - **Service Date** -- The date the service was delivered.
   - **Quantity** -- The number of units (default is 1). The unit of measure depends on the service type (hours, instances, items, dollars).
   - **Value** -- The dollar value of the service, if applicable (for example, the dollar amount of a rent assistance payment).
   - **Notes** -- Any additional details about this service delivery.
4. Click **Create**.

### Units of Measure

Each service has a defined unit of measure:

| Unit       | Examples                                                     |
|------------|--------------------------------------------------------------|
| **hour**   | Case Management, Employment Readiness Training               |
| **instance** | Information and Referral, Energy Audit, Financial Literacy Workshop |
| **dollar** | Emergency Rent Assistance, Utility Payment, Prescription Assistance |
| **item**   | Emergency Food Box                                           |

Enter the quantity in the appropriate unit. For dollar-based services, the quantity and value fields typically match.

---

## Managing Income Records

Income records track the income sources for clients and household members. Accurate income data is essential for FPL eligibility determination.

### Adding an Income Record

1. Navigate to **Income Records**.
2. Click **New Income Record**.
3. Fill in the fields:
   - **Client** or **Household Member** -- Select who this income belongs to. An income record is attached to either a client or a household member, not both.
   - **Source** -- The type of income: Employment, SSI, SSDI, TANF, SNAP, Child Support, Pension, Unemployment, Self-Employment, or Other.
   - **Source Description** -- Additional detail, such as the employer name.
   - **Amount** -- The dollar amount per pay period.
   - **Frequency** -- How often this income is received:
     - Weekly (multiplied by 52 for annual)
     - Bi-Weekly (multiplied by 26 for annual)
     - Monthly (multiplied by 12 for annual)
     - Annually (multiplied by 1)
     - One-Time (counted as-is)
   - **Verification Status** -- Whether the income has been verified.
   - **Verification Method** -- Pay stub, tax return, benefit letter, or self-declaration.
   - **Verified At** -- The date verification was completed.
   - **Effective Date** -- When this income source started.
   - **Expiration Date** -- When this income source ends (if applicable).
4. Click **Create**.

### Automatic Annual Calculation

The system automatically calculates the **Annual Amount** based on the amount and frequency you enter. For example:

- $500 Weekly = $26,000/year
- $1,200 Monthly = $14,400/year
- $30,000 Annually = $30,000/year

This annual amount is what the system uses for FPL eligibility calculations. You do not need to calculate annual totals manually.

### Expired Income

If an income record has an expiration date that has passed, it is considered expired. Review and update income records periodically, especially before enrolling a client in a new program.

---

## Understanding Eligibility and FPL

Many programs require clients to be below a certain percentage of the Federal Poverty Level (FPL) to be eligible. CAPIntake handles this automatically.

### How It Works

1. The system looks up the FPL guideline for the current year based on the household size and region (continental US, Alaska, or Hawaii).
2. It sums up all annual income across every client and household member in the household.
3. It divides the total household income by the FPL guideline and multiplies by 100 to get the FPL percentage.

**Example:**

- Household size: 3
- Total annual household income: $30,000
- 2025 FPL guideline for household of 3 (continental): $26,650
- FPL percentage: ($30,000 / $26,650) x 100 = **113%**

### Program Thresholds

Each program defines its own FPL threshold:

| Program                        | FPL Threshold |
|--------------------------------|---------------|
| Community Services Block Grant | 200% FPL      |
| Emergency Services             | 150% FPL      |
| Weatherization Assistance      | 200% FPL      |

A client whose household is at 113% FPL would be eligible for all three programs. A client at 175% FPL would be eligible for CSBG and Weatherization but not Emergency Services.

### Eligibility Snapshot

When a client is enrolled in a program, the system captures their eligibility at that moment: household income, household size, FPL percentage, and whether they met the threshold. This snapshot is preserved on the enrollment record even if the household's income changes later.

---

## Searching and Filtering Records

All list views in CAPIntake support searching and filtering.

### Searching Clients

On the Clients list page, you can search by:

- **Name** -- First name, last name, or both.
- **SSN (last four)** -- Enter the last four digits of a client's SSN.
- **Date of Birth** -- Search by a specific date.

### Filtering Lists

Most list views include filter options. Common filters include:

- **Program** -- Filter enrollments or service records by program.
- **Status** -- Filter enrollments by status (Pending, Active, Completed, Withdrawn, Denied).
- **Date Range** -- Filter service records or enrollments by a date range.
- **Caseworker** -- Filter enrollments by the assigned caseworker.
- **County** -- Filter households by county.
- **Income Source** -- Filter income records by type.

### Sorting

Click on any column header to sort the list by that column. Click again to reverse the sort order.

### Exporting Data

Where available, use the export button to download records as PDF or Excel files. This is useful for generating reports for supervisors, state offices, or auditors.
