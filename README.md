# 🧮 BWET Farms – Poultry Module: Formulas, Logic & Implementation Blueprint

*Version 2.0 – September 2026*  
*This document is the authoritative reference for all calculations, business rules, and data flows in the poultry module.*

---

## 📖 Table of Contents

1. [Core Data Model](#core-data-model)
2. [Batch State (Checkpoint Approach)](#batch-state)
3. [State Update Operations](#state-update-operations)
4. [Batch Transfer (Grading) with Split Logic](#batch-transfer)
5. [Cost & Financial Metrics](#cost--financial-metrics)
6. [Slaughter & Cull Cost Allocation](#slaughter--cull-cost-allocation)
7. [Feed Conversion Ratio (FCR)](#feed-conversion-ratio-fcr)
8. [Weight Records & Coefficient of Variation (CV)](#weight-records--coefficient-of-variation-cv)
9. [Price Calculator](#price-calculator)
10. [Inventory & Consumption](#inventory--consumption)
11. [Batch Age (Dynamic)](#batch-age-dynamic)
12. [System Variables](#system-variables)
13. [Slaughter Triggers (Automated Alerts)](#slaughter-triggers-automated-alerts)
14. [Pen Assignment](#pen-assignment)
15. [Migration Log (Audit Trail)](#migration-log-audit-trail)
16. [Flowchart Summary](#flowchart-summary)

---

## Core Data Model

| Table | Purpose |
|-------|---------|
| `poultry_batches` | Stores **current state** (checkpoint) plus historical totals for mortality, feed, weight gain, and FCR. |
| `batch_state_migrations` | Audit trail of **every state change** (transfers, feed, expenses, mortality, etc.) with before/after snapshots. |
| `flock_records` | Daily mortality, culls, and slaughter events (historical). |
| `weight_records` | Individual bird weights with CV, mean, and status. |
| `feed_records` | Daily feed consumption, linked to inventory items. |
| `expenses` | Costs linked to a batch (or general). |
| `inventory_items` | Stock items (feed, medicine, etc.) with quantity, cost, and minimum level. |
| `inventory_consumptions` | Usage of inventory items, linked to batch (for cost allocation). |

---

## Batch State (Checkpoint Approach)

**Core Idea:**  
Instead of recalculating all metrics from historical records every time, we store the **current state** of the batch in dedicated columns. Each new event (feed, mortality, expense, transfer) updates this state by applying a delta – this is fast, consistent, and fully auditable.

### 1.1 State Fields

| Column | Description | Formula / Update Rule |
|--------|-------------|----------------------|
| `current_count` | Number of birds currently in the batch | Updated directly by events |
| `current_weight_kg` | Total weight of all birds in the batch (kg) | Updated by weight records, mortality, culls, slaughter, and transfers |
| `current_cost` | Total cost incurred by the batch **not yet allocated** to slaughter/transfers (₦) | Updated by feed, expenses, inventory consumption, and transfers |
| `current_average_weight` | Average weight per bird (kg) | `current_weight_kg / current_count` (recalculated after each update) |
| `current_average_cost` | Average cost per bird (₦) | `current_cost / current_count` (recalculated) |
| `total_weight_gain` | Cumulative live weight gained by the batch (kg) | Updated by weight records (difference from previous) |
| `starting_flock` | Original number of birds placed | **Updated on transfers** (destination receives transferred birds) |
| `total_mortality` | Cumulative deaths in the batch | Updated by mortality records **and transfers** (mortality share moves with birds) |
| `total_feed_used` | Cumulative feed consumed (kg) | Updated by feed records **and transfers** (feed share moves with birds) |

---

## State Update Operations

### 2.1 Adding a Record (Feed, Expense, Mortality, etc.)

When a new record is added, the state is updated by applying a delta.

**General formula:**
```
new_count   = old_count   + Δcount
new_weight  = old_weight  + Δweight
new_cost    = old_cost    + Δcost

new_avg_weight = new_weight / new_count (if new_count > 0 else 0)
new_avg_cost   = new_cost   / new_count (if new_count > 0 else 0)
```

**Example – Feed Record:**
- Δcount = 0
- Δweight = 0
- Δcost = `feed_used × cost_per_kg` (added to batch cost)
- Also update `total_feed_used += feed_used`

**Example – Mortality Record:**
- Δcount = `-mortality`
- Δweight = `-mortality × current_avg_weight` (subtract weight of dead birds)
- Δcost = 0 (cost is not reduced – it’s a loss)
- Also update `total_mortality += mortality`

**Example – Cull Record:**
- Same as mortality – birds removed, weight subtracted, no cost reduction.

**Example – Slaughter Record:**
- Δcount = `-slaughter`
- Δweight = `-slaughter × current_avg_weight` (subtract weight of harvested birds)
- Δcost = 0 (cost is allocated separately – see Section 4)

**Example – Expense:**
- Δcount = 0
- Δweight = 0
- Δcost = `amount`

All state changes are logged in `batch_state_migrations` with a snapshot of the state before and after.

---

## Batch Transfer (Grading) with Split Logic

### 3.1 The Problem We Solve

When transferring a subset of birds from one batch to another:
- These birds may have a **different weight** than the batch average (e.g., fast growers).
- Their **mortality history** and **feed consumption** should travel with them to keep FCR and mortality rates accurate.
- The destination batch's starting flock must be increased to include the transferred birds, so that mortality percentages remain meaningful.

### 3.2 Inputs for a Transfer

| Input | Source | Description |
|-------|--------|-------------|
| `transfer_count` | User | Number of birds to move |
| `manual_weight` | User (manual input) | Average weight of the specific birds being transferred (entered by farm staff) |
| `source` | Selected batch | The batch the birds come from |
| `destination` | Selected batch | The batch the birds go to |

### 3.3 Calculations Performed

#### A. Weight & Cost Transfer
```
transfer_weight = transfer_count × manual_weight
transfer_cost   = transfer_count × source.current_average_cost
```

#### B. Mortality Share Transfer
Mortality is proportional to the source batch's mortality rate.

```
source_mortality_rate = source.total_mortality / source.starting_flock
transfer_mortality    = transfer_count × source_mortality_rate
```

#### C. Feed & Weight Gain Share Transfer
Feed and weight gain are proportional to the fraction of the source population being moved.

```
transfer_fraction = transfer_count / source.current_count
transfer_feed     = source.total_feed_used × transfer_fraction
transfer_weight_gain = source.total_weight_gain × transfer_fraction
```

### 3.4 Applying the Changes

**Source Batch (after transfer):**
```
current_count      -= transfer_count
current_weight_kg  -= transfer_weight
current_cost       -= transfer_cost
total_mortality    -= transfer_mortality
total_feed_used    -= transfer_feed
total_weight_gain  -= transfer_weight_gain
starting_flock     remains unchanged
remaining_flock     = current_count
```

**Destination Batch (after transfer):**
```
current_count      += transfer_count
current_weight_kg  += transfer_weight
current_cost       += transfer_cost
total_mortality    += transfer_mortality
total_feed_used    += transfer_feed
total_weight_gain  += transfer_weight_gain
starting_flock     += transfer_count   ← Critical: ensures mortality % stays meaningful
remaining_flock     = current_count
```

**Recalculate Averages:**
```
current_average_weight = current_weight_kg / current_count
current_average_cost   = current_cost / current_count
```

### 3.5 Audit Log

Two migration records are created:
- **transfer_out** (from source)
- **transfer_in** (to destination)

Each record stores:
- Count, weight, cost, mortality, feed, weight gain moved.
- Snapshot of source and destination state before the transfer.

---

## Cost & Financial Metrics

### 4.1 Cost per Bird (COP)
```
COP_per_bird = current_cost / current_count
```
This is the live average cost per bird (unallocated costs only – costs already assigned to slaughtered or transferred birds are excluded).

### 4.2 Cost per kg (dressed)
```
live_weight = current_average_weight
dress_percentage = system_variable('dress_percentage', 75)
dressed_weight = live_weight × (dress_percentage / 100)
cost_per_kg = COP_per_bird / dressed_weight
```

### 4.3 Selling Price per kg (Recommended)
```
profit_margin = system_variable('profit_margin', 20)
selling_price_per_kg = cost_per_kg × (1 + profit_margin / 100)
```

### 4.4 Selling Price per Bird
```
selling_price_per_bird = COP_per_bird × (1 + profit_margin / 100)
```

### 4.5 Selling Price per Carton (10 kg)
```
selling_price_per_carton = selling_price_per_kg × 10
```

---

## Slaughter & Cull Cost Allocation

When birds are removed from the batch (slaughter or cull), we allocate a portion of the batch’s cost to those birds – this prevents double-counting cost for birds that are no longer present.

**Variables:**
- `total_COP` = current cost of the batch (before allocation)
- `allocated_COP` = cost already allocated to previous removals
- `remaining_fish` = current count before removal
- `harvest_quantity` = number of birds removed

**Steps:**
```
unallocated_COP = total_COP - allocated_COP
COP_per_fish   = unallocated_COP / remaining_fish
harvest_COP    = COP_per_fish × harvest_quantity
allocated_COP  = allocated_COP + harvest_COP
remaining_fish = remaining_fish - harvest_quantity
```

**Weight Removal:**
```
removed_weight = harvest_quantity × current_average_weight
current_weight_kg -= removed_weight
```

The weight removal is automatically handled when `current_count` and `current_average_weight` are updated.

---

## Feed Conversion Ratio (FCR)

### 6.1 Cumulative FCR (cFCR)
Measures overall feed efficiency from the start of the batch (or from the last reset).
```
cFCR = total_feed_used / total_weight_gain
```
- A lower cFCR is better (less feed per kg of gain).
- Updated **after every feed record and after transfers**.

### 6.2 Instantaneous FCR (iFCR)
Measures feed efficiency over a recent period (e.g., last `n` days).
```
iFCR = feed_used_in_period / weight_gained_in_period
```
- Weight gained in period is derived from weight records (or from checkpoint state).
- If no weight records exist in the period, iFCR is not computed.

**Interpretation:**
- `iFCR < cFCR` → Fish are performing better than historical average.
- `iFCR ≈ cFCR` → Performance is consistent.
- `iFCR > cFCR` → Performance is declining (warning).

---

## Weight Records & Coefficient of Variation (CV)

### 7.1 Sample Size
```
required_sample = min(max(ceil(remaining_flock × 0.10), 5), 10)
```
- Minimum 5 birds, maximum 10.
- Used in the weight record form to guide staff.

### 7.2 Coefficient of Variation (CV)
```
mean = sum(weights) / count(weights)
variance = Σ(weight - mean)² / count
stddev = √variance
CV = (stddev / mean) × 100
```
- CV is expressed as a percentage.
- Higher CV indicates greater size variation.

### 7.3 CV Status Interpretation (Poultry)

| CV Range | Status | Action |
|----------|--------|--------|
| < 10%    | Excellent | Uniform flock |
| 10–12%   | Caution   | Monitor |
| 12–15%   | Warning   | Check feeding/health |
| ≥ 15%    | Rejected  | Re‑take sample (invalid) |

---

## Price Calculator

Used to determine selling price for a specific customer order.

**Inputs:**
- `customer_bird_weight` – weight of the bird the customer wants (kg)
- `mode_weight` – most frequent/dominant weight in the batch (kg)
- `profit_margin` – target margin (from system variables)

**Formula:**
```
cost_scaled = (customer_bird_weight / mode_weight) × current_average_cost
selling_price_per_bird = cost_scaled × (1 + profit_margin / 100)
```
**Derived:**
```
dressed_weight = customer_bird_weight × (dress_percentage / 100)
selling_price_per_kg = selling_price_per_bird / dressed_weight
selling_price_per_carton = selling_price_per_kg × 10
```

---

## Inventory & Consumption

### 9.1 Stock Update
When a feed record is created:
```
inventory.quantity_in_stock -= feed_used
inventory.quantity_used    += feed_used
```

### 9.2 Cost Addition
When an inventory item is consumed, its cost is added to the batch’s `current_cost`:
```
batch.current_cost += feed_used × cost_per_unit
```

---

## Batch Age (Dynamic)

Age is calculated on‑the‑fly, not stored.
```
age_days = today - start_date
```
- Used for sorting and display.
- The stored `current_age_days` is kept for sorting performance (updated periodically).

---

## System Variables

| Key | Default | Description |
|-----|---------|-------------|
| `profit_margin` | 20 | Target profit margin (%) |
| `dress_percentage` | 75 | Dressing out percentage (live → dressed) |
| `weighing_frequency_days` | 4 | Days between scheduled weighings |
| `daily_profit_tolerance` | -15 | Daily profit % threshold for alert |
| `fcr_efficiency_tolerance` | 20 | FCR efficiency drop threshold (%) |
| `stop_loss_amount` | 20000 | Maximum loss before alert (₦) |

---

## Slaughter Triggers (Automated Alerts)

| Trigger | Condition | Severity |
|---------|-----------|----------|
| Daily profit | `current_marginal_profit_percent <= daily_profit_tolerance` | Critical |
| FCR efficiency | `(iFCR / cFCR - 1) × 100 >= fcr_efficiency_tolerance` | Warning |
| Stop‑loss | `peak_profit - current_profit >= stop_loss_amount` | Critical |
| Missed weighings | 3+ missed scheduled weighings | Emergency |
| Weight loss | > 5% loss between consecutive weight records | Emergency |
| High mortality | `total_mortality / starting_flock × 100 >= 7%` | Emergency |

---

## Pen Assignment

When a batch is created with phase `batch`:
- The system finds an available pen (`Pen::available()`).
- If a pen is found and its capacity ≥ starting flock, assign it.
- Otherwise, warn the user but allow creation.

---

## Migration Log (Audit Trail)

Every state change is recorded in `batch_state_migrations`. The table structure:

| Column | Description |
|--------|-------------|
| `source_batch_id` | Batch being modified |
| `destination_batch_id` | Batch receiving (for transfers) |
| `migration_type` | `feed`, `expense`, `mortality`, `cull`, `slaughter`, `transfer_out`, `transfer_in`, `weight_gain` |
| `count_moved` | Number of birds changed |
| `weight_moved` | Weight changed (kg) |
| `cost_moved` | Cost changed (₦) |
| `mortality_moved` | Mortality share moved (for transfers) |
| `feed_moved` | Feed used share moved (for transfers) |
| `weight_gain_moved` | Weight gain share moved (for transfers) |
| `source_state_before` | JSON snapshot of source before change |
| `destination_state_before` | JSON snapshot of destination before change |

---

## Flowchart Summary

```
┌──────────────────────────────────────────────────────────────────┐
│                   BATCH CREATION                                 │
│  - start_date, starting_flock, initial_chicken_cost             │
│  - phase (brooding / batch)                                     │
│  - pen assignment (if batch phase)                              │
│  - checkpoint columns initialized                               │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   DAILY OPERATIONS                              │
│  ┌───────────────────┐  ┌───────────────────┐  ┌──────────────┐│
│  │   Feed Record     │  │  Weight Record    │  │ Flock Record ││
│  │  - feed_used      │  │  - individual     │  │ - mortality  ││
│  │  - cost_per_kg    │  │    weights        │  │ - culls      ││
│  │  - total_cost     │  │  - CV calculation │  │ - slaughter  ││
│  │                   │  │  - status update  │  │ - weight sub ││
│  └───────────────────┘  └───────────────────┘  └──────────────┘│
│         │                        │                        │      │
│         └────────────────────────┼────────────────────────┘      │
│                                  ▼                               │
│          ┌──────────────────────────────────────────────┐       │
│          │  Batch State Update (Checkpoint)             │       │
│          │  - current_count, current_weight_kg,        │       │
│          │    current_cost, mortality, feed, weight_gain│       │
│          │  - log to batch_state_migrations            │       │
│          └──────────────────────────────────────────────┘       │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   TRANSFER (Grading)                            │
│  - Manual weight entry for transferred birds                   │
│  - Split mortality, feed, weight gain proportionally           │
│  - Update starting_flock of destination                        │
│  - Log transfer_out and transfer_in migrations                 │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   HARVEST (Slaughter)                           │
│  - allocate cost using harvest allocation formula              │
│  - subtract weight from batch weight                           │
│  - update allocated_COP and remaining_count                    │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   ANALYTICS & INSIGHTS                          │
│  - FCR (cFCR, iFCR)                                            │
│  - Growth charts (weight vs age)                               │
│  - Profit margins                                              │
│  - Slaughter triggers                                          │
└──────────────────────────────────────────────────────────────────┘
```

---

## ✅ Implementation Status

| Feature | Status |
|---------|--------|
| Checkpoint state (count, weight, cost) | ✅ Fully implemented |
| Manual weight entry on transfers | ✅ Implemented |
| Mortality split on transfers | ✅ Implemented |
| Feed and weight gain split on transfers | ✅ Implemented |
| Destination `starting_flock` update | ✅ Implemented |
| Percentage bar `min()` fix | ✅ Implemented |
| Cost allocation for slaughter | ✅ Implemented |
| Weight subtraction for culls/slaughter | ✅ Implemented |
| FCR (cFCR, iFCR) on batch details | ✅ Displayed after each update |
| Migration audit log | ✅ Fully functional |

---

*This document is the single source of truth for all calculations in the BWET Farms poultry module. It is updated whenever logic changes.*