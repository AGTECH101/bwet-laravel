# BWET Farms Management System

## Overview

BWET Farms is a digital farm operations and decision-support system built to help farm teams record production data, monitor performance, and support financial decisions using real operational figures. The system is designed around agricultural records that must remain transparent, auditable, and structured for day-to-day farm decisions.

The platform currently focuses on poultry operations, but the architecture is organized so future sectors such as fishery and livestock can be added without breaking the core business rules. The application combines role-based access control, operational record entry, analytical reporting, and financial summaries in a single system.

## Project Goals

- digitize farm records and reduce manual spreadsheet-based reporting
- enforce role-based access for admin, manager, staff, and investor users
- support real-time operational monitoring for batches and records
- keep calculations based on production data rather than unverified assumptions
- maintain historically fair system settings by ensuring future changes do not alter past decisions
- provide exports and summaries for team review and financial analysis

## Core Business Rules

### Role Access

The app separates user responsibilities by role:

- Admin: full system access, user management, approvals, financial oversight, and configuration
- Manager: operational oversight, batch management, staff support, and performance monitoring without direct access to sensitive financial information in the same way as the admin
- Staff: daily field entry such as flock counts, weight samples, feed usage, and related forms
- Investor: financial-facing summaries such as profitability, returns, cost visibility, and performance metrics

The role-driven logic is enforced through Laravel gates and route-level authorization patterns. Admins retain a system override, while other roles are limited to their assigned permissions.

## Architecture Overview

### Application Layers

1. Presentation layer
   - Blade views under resources/views
   - role-aware navigation and layouts in the main application views
2. HTTP layer
   - controllers under app/Http/Controllers
   - form requests under app/Http/Requests
   - route definitions under routes/web.php and routes/sectors/poultry.php
3. Domain / business logic layer
   - batch calculations in app/Services/Poultry/BatchCalculationService.php
   - system variable rules in app/Models/SystemVariable.php
   - export logic in app/Services/Poultry/ExportService.php
4. Persistence layer
   - Eloquent models under app/Models
   - database migrations in database/migrations

### Key Model Relationships

- User
  - owns account, role, and approval status
- Sector
  - top-level farm segment such as poultry
- Batch
  - production cycle for a flock or group of birds
- FlockRecord
  - mortality, culls, slaughter, and flock movement entries
- WeightRecord
  - individual sample weights and calculation metrics
- FeedRecord
  - feed usage and feed conversion tracking
- Expense
  - operational cost entries
- InventoryConsumption
  - stock usage and cost allocation
- SystemVariable
  - configuration values such as profit margin, dress percentage, and operating thresholds
- MarketPrice
  - informational external benchmark pricing; it must not directly affect calculations

## System Configuration and Historical Rules

### SystemVariable Behavior

System settings are designed so that changes apply only to future calculations. This protects historical records from retroactive changes, which is important for accurate and defensible financial reporting.

The rule is implemented by evaluating the effective configuration date associated with each version. When the system value changes, a new version is created with a new effective date, but older records keep their original values.

The key logic is:

- each variable can have multiple versions over time
- a value applies only from its effective date onward
- historical calculations are resolved by the closest valid version not after the calculation date

This ensures a formula such as profit margin is calculated correctly for a past batch using the margin valid at that time, while current batches use the latest approved value.

## Poultry Calculation Logic

### 1. Required Sample Size

The system determines the minimum sample size based on remaining flock size.

Formula:

$$
requiredSample = \min(\max(\lceil remainingFlock \times 0.10 \rceil, 5), 10)
$$

This ensures:
- the sample is never less than 5 birds
- the sample is never more than 10 birds
- the sample follows a 10% rule on flock size while keeping a practical cap

### 2. Weight Variance and Coefficient of Variation (CV)

For weight records, the app calculates the mean and the standard deviation of the sample and then computes the coefficient of variation.

Formula:

$$
\mu = \frac{\sum w_i}{n}
$$

$$
\sigma = \sqrt{\frac{\sum (w_i - \mu)^2}{n}}
$$

$$
CV = \left(\frac{\sigma}{\mu}\right) \times 100
$$

Interpretation:
- CV < 10%: excellent uniformity
- 10% to < 12%: caution
- 12% to < 15%: warning
- CV >= 15%: rejected sample

This is strictly enforced in both the browser and the backend. A sample with CV >= 15% is blocked from submission and the user is asked to remeasure.

### 3. Current Average Weight

The system derives current average weight from batch weight history and interpolates values between known record points where necessary.

### 4. Cost Per Bird

The system calculates the effective cost per bird as the remaining unallocated investment divided by current remaining flock.

Formula:

$$
CostPerBird = \frac{TotalInvestment - CostAllocatedSoFar}{RemainingFlock}
$$

### 5. Cost Per Kg

The dressed weight determines the cost in terms of kilo output.

$$
DressedWeightPerBird = LiveWeight \times \left(\frac{DressPercentage}{100}\right)
$$

$$
CostPerKg = \frac{CostPerBird}{DressedWeightPerBird}
$$

### 6. Selling Price Logic

The app calculates a target selling price using the configured profit margin.

$$
SellingPricePerBird = CostPerBird \times (1 + \frac{ProfitMargin}{100})
$$

$$
SellingPricePerKg = \frac{SellingPricePerBird}{DressedWeightPerBird}
$$

### 7. Price Calculator Feature

The price calculator is a poultry-specific feature with this formula:

$$
CalculatedPrice = \frac{CostOfProduction \times CurrentAverageWeight}{ModWeight}
$$

Inputs:
- selected batch
- cost of production for the selected batch
- current average weight of the selected batch
- mod_weight: the weight of the most frequent bird size (10 weight observations combined into a modal-weight estimate)

The calculator is designed specifically for poultry and is not a general finance formula for all sectors.

## Record-Taking Workflow

### Weight Record Flow

1. Select batch
2. Capture date
3. Enter 10 sample weights
4. Live JS calculates average weight, total weight, and CV
5. If CV >= 15%, the form blocks submission
6. Valid records update batch metrics after save
7. Batch-level cached metrics are refreshed automatically

### Batch update mechanics

After a valid record saving event, the system recalculates:
- remaining flock
- weight gain
- cost values
- performance indicators
- current profit and profit margin usage

This ensures the dashboard and financial views reflect the latest valid operational record.

## Export System

The export controller and export service support report generation for different use cases:

- batch export
- database export
- analytics export
- financial export

The export system supports multiple output formats such as Excel and CSV. The user selects a report template before export, and batch export requires the specific batch selection. Quick export actions are implemented as POST submissions so they trigger actual file generation rather than a page reload.

## Financial Integrity Rules

### Market Price Usage

Market prices are informational and are not used in production-batch calculation formulas. This separation ensures farm decisions are based on internal operating cost and performance data rather than external market fluctuations alone.

### Future-Only System Effects

System variables such as profit margin and dress percentage are versioned and dated. This means:
- old figures remain valid for past analyses
- new values only affect future calculations
- historical decisions remain stable and reviewable

## Security and Access Control

The app uses Laravel authorization gates and route protection. Authenticated users are required for sector routes, and each role is restricted to the operations it is meant to oversee. The registration flow supports internal user creation by admin and manager, while public self-registration remains available with an approval process.

## Public Landing Page

The landing page promotes BWET Farms as a digitalized farm platform. It communicates the farm’s operations, automation, automation of records, and the use of technology for more accurate and data-driven agricultural decision-making.

The page also makes the investment opportunity clear and includes the contact number:

+234 703 868 7630

## Repository Structure

- app/Http/Controllers — HTTP logic and routing handlers
- app/Http/Requests — form validation and input handling
- app/Models — data entities and domain behavior
- app/Services — central business calculation services
- app/Providers — auth and Fortify integration
- resources/views — Blade UI templates
- routes — route definitions by feature area
- database/migrations — schema and versioning
- tests — regression and behavioral validation

## Testing and Validation

The project includes feature tests for route presence and system-variable behavior. These tests verify the key guarantees that the app depends on:

- required poultry views exist
- future-only system variable changes are respected
- invalid high-variation weight samples are prevented

## Summary

BWET Farms is a role-aware agricultural management platform that combines operational records, batch calculations, financial logic, user permissions, and export reporting into a single system. Its design emphasizes accuracy, historical integrity, and decision-ready reporting, with a focus on poultry production data that directly influencing financial records.

This project is structured so each module has a clear purpose and each formula can be reviewed and traced back to the exact operational input it is based on.

## License

This project is intended for the BWET Farms operational system and follows the project’s local delivery and usage requirements.
