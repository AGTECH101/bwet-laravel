# 🧮 BWET Farms – Poultry Module: Formulas & Logic Blueprint

This document outlines all the mathematical formulas, business rules, and logical flows used in the poultry module. Use it as a reference for development, debugging, and training.

---

## 📦 Core Data Model

| Table | Purpose |
|-------|---------|
| `poultry_batches` | Stores current batch state + historical aggregates |
| `batch_state_migrations` | Audit log of all state changes (transfers, feed, expenses, mortality, etc.) |
| `flock_records` | Daily mortality, culls, and slaughter events |
| `weight_records` | Individual bird weights with CV calculation |
| `feed_records` | Daily feed consumption (linked to inventory) |
| `expenses` | Costs linked to a batch |
| `inventory_items` | Stock items (feed, medicine, etc.) |
| `inventory_consumptions` | Usage of inventory items, linked to batch |

---

## 1️⃣ Batch State (Checkpoint Approach)

**Goal:** Store the current state of a batch (count, weight, cost) and update it incrementally – no historical recalculations.

### 1.1 State Fields

| Column | Description |
|--------|-------------|
| `current_count` | Number of birds currently in the batch |
| `current_weight_kg` | Total weight of all birds in the batch |
| `current_cost` | Total cost incurred by the batch (excluding cost allocated to slaughters/transfers) |
| `current_average_weight` | `current_weight_kg / current_count` |
| `current_average_cost` | `current_cost / current_count` |
| `total_weight_gain` | Cumulative weight gain (for FCR calculation) |

---

## 2️⃣ Batch State Updates

### 2.1 Adding a Record (Feed, Expense, Mortality, etc.)

When a new record is added (e.g., feed consumption, expense, mortality), the state is updated by applying a delta:

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
- Δcost = feed_used × cost_per_kg

**Example – Mortality:**
- Δcount = -mortality
- Δweight = -mortality × current_avg_weight
- Δcost = 0 (cost is not reduced – it’s a loss)

**Example – Expense:**
- Δcount = 0
- Δweight = 0
- Δcost = amount

All state changes are logged in `batch_state_migrations` with a snapshot of the state before and after.

---

### 2.2 Batch Transfer (Grading)

Transfers move birds, weight, and cost from a source batch to a destination batch.

**Transfer Amounts:**
```
transfer_weight = transfer_count × source.current_average_weight
transfer_cost   = transfer_count × source.current_average_cost
```

**Source Batch (after transfer):**
```
new_count  = old_count - transfer_count
new_weight = old_weight - transfer_weight
new_cost   = old_cost   - transfer_cost
new_avg_weight = new_weight / new_count
new_avg_cost   = new_cost   / new_count
```

**Destination Batch (after transfer):**
```
new_count  = old_count + transfer_count
new_weight = old_weight + transfer_weight
new_cost   = old_cost   + transfer_cost
new_avg_weight = new_weight / new_count
new_avg_cost   = new_cost   / new_count
```

**Audit:** A `batch_state_migration` record is created for both source and destination (type `transfer_out` and `transfer_in`).

---

## 3️⃣ Cost & Financial Metrics

### 3.1 Cost per Bird (COP)

```
COP_per_bird = current_cost / current_count
```
This is the live average cost per bird (unallocated costs only – costs already assigned to slaughtered or transferred birds are excluded).

### 3.2 Cost per kg (dressed)

```
live_weight = current_average_weight
dress_percentage = system_variable('dress_percentage', 75)
dressed_weight = live_weight × (dress_percentage / 100)
cost_per_kg = COP_per_bird / dressed_weight
```

### 3.3 Selling Price per kg (Recommended)

```
profit_margin = system_variable('profit_margin', 20)
selling_price_per_kg = cost_per_kg × (1 + profit_margin / 100)
```

### 3.4 Selling Price per Bird

```
selling_price_per_bird = COP_per_bird × (1 + profit_margin / 100)
```

### 3.5 Selling Price per Carton (10 kg)

```
selling_price_per_carton = selling_price_per_kg × 10
```

---

## 4️⃣ Slaughter Cost Allocation

When birds are slaughtered, we allocate a portion of the batch’s cost to those birds. This prevents costs from being assigned to birds that no longer exist.

**Variables:**
- `total_COP` = current cost of the batch (before allocation)
- `allocated_COP` = cost already allocated to previous slaughters/transfers
- `remaining_fish` = current count before slaughter
- `harvest_quantity` = number of birds slaughtered

**Steps:**
```
unallocated_COP = total_COP - allocated_COP
COP_per_fish   = unallocated_COP / remaining_fish
harvest_COP    = COP_per_fish × harvest_quantity
allocated_COP  = allocated_COP + harvest_COP
remaining_fish = remaining_fish - harvest_quantity
```

**Note:** This logic is used for slaughter records (poultry) and will also be used for fishery harvests.

---

## 5️⃣ Feed Conversion Ratio (FCR)

### 5.1 Cumulative FCR (cFCR)

Measures overall feed efficiency from the start of the batch.

```
cFCR = total_feed_used / total_weight_gain
```

### 5.2 Instantaneous FCR (iFCR)

Measures feed efficiency over a recent period (e.g., last `n` days).

```
iFCR = feed_used_in_period / weight_gained_in_period
```

**Weight gained in period:** derived from weight records (or from checkpoint state).

**Interpretation:**
- `iFCR < cFCR` → Fish are performing better than historical average.
- `iFCR ≈ cFCR` → Performance is consistent.
- `iFCR > cFCR` → Performance is declining (warning).

---

## 6️⃣ Weight Records & CV

### 6.1 Sample Size

```
required_sample = min(max(ceil(remaining_flock × 0.10), 5), 10)
```
> Minimum 5 birds, maximum 10.

### 6.2 Coefficient of Variation (CV)

```
mean = sum(weights) / count(weights)
variance = Σ(weight - mean)² / count
stddev = √variance
CV = (stddev / mean) × 100
```

### 6.3 CV Status Interpretation (Poultry)

| CV Range | Status | Action |
|----------|--------|--------|
| < 10%    | Excellent | Uniform flock |
| 10–12%   | Caution   | Monitor |
| 12–15%   | Warning   | Check feeding/health |
| ≥ 15%    | Rejected  | Re‑take sample (invalid) |

---

## 7️⃣ Price Calculator

Used to determine selling price for a specific customer order based on the customer’s desired bird weight and the batch’s mode weight.

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

## 8️⃣ Inventory & Consumption

### 8.1 Stock Update

When a feed record is created:
```
inventory.quantity_in_stock -= feed_used
inventory.quantity_used    += feed_used
```

### 8.2 Cost Addition

When an inventory item is consumed, its cost is added to the batch’s `current_cost`:
```
batch.current_cost += feed_used × cost_per_unit
```

---

## 9️⃣ Batch Age (Dynamic)

Age is calculated on‑the‑fly, not stored.
```
age_days = today - start_date
```

Used for sorting and display. The stored `current_age_days` is kept for sorting performance.

---

## 🔟 System Variables

| Key | Default | Description |
|-----|---------|-------------|
| `profit_margin` | 20 | Target profit margin (%) |
| `dress_percentage` | 75 | Dressing out percentage (live → dressed) |
| `weighing_frequency_days` | 4 | Days between scheduled weighings |
| `daily_profit_tolerance` | -15 | Daily profit % threshold for alert |
| `fcr_efficiency_tolerance` | 20 | FCR efficiency drop threshold (%) |
| `stop_loss_amount` | 20000 | Maximum loss before alert (₦) |

---

## 1️⃣1️⃣ Slaughter Triggers (Automated Alerts)

| Trigger | Condition | Severity |
|---------|-----------|----------|
| Daily profit | `current_marginal_profit_percent <= daily_profit_tolerance` | Critical |
| FCR efficiency | `(iFCR / cFCR - 1) × 100 >= fcr_efficiency_tolerance` | Warning |
| Stop‑loss | `peak_profit - current_profit >= stop_loss_amount` | Critical |
| Missed weighings | 3+ missed scheduled weighings | Emergency |
| Weight loss | > 5% loss between consecutive weight records | Emergency |
| High mortality | `total_mortality / starting_flock × 100 >= 7%` | Emergency |

---

## 1️⃣2️⃣ Pen Assignment

When a batch is created with phase `batch`:
- The system finds an available pen (`Pen::available()`).
- If a pen is found and its capacity ≥ starting flock, assign it.
- Otherwise, warn the user but allow creation.

---

## 1️⃣3️⃣ Migration Log (Audit Trail)

Every state change is recorded in `batch_state_migrations`:

| Column | Description |
|--------|-------------|
| `source_batch_id` | Batch being modified |
| `destination_batch_id` | Batch receiving (for transfers) |
| `migration_type` | `feed`, `expense`, `mortality`, `cull`, `slaughter`, `transfer_out`, `transfer_in`, `weight_gain` |
| `count_moved` | Number of birds changed |
| `weight_moved` | Weight changed (kg) |
| `cost_moved` | Cost changed (₦) |
| `source_state_before` | JSON snapshot of source before change |
| `destination_state_before` | JSON snapshot of destination before change |

---

## 🧭 Flowchart Summary

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
│  │                   │  │  - status update  │  │ - allocation ││
│  └───────────────────┘  └───────────────────┘  └──────────────┘│
│         │                        │                        │      │
│         └────────────────────────┼────────────────────────┘      │
│                                  ▼                               │
│          ┌──────────────────────────────────────────────┐       │
│          │  Batch State Update (Checkpoint)             │       │
│          │  - current_count, current_weight_kg,        │       │
│          │    current_cost                             │       │
│          │  - log to batch_state_migrations            │       │
│          └──────────────────────────────────────────────┘       │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   TRANSFER (Grading)                            │
│  - source: subtract count, weight, cost                        │
│  - destination: add count, weight, cost                        │
│  - log transfer_out and transfer_in migrations                 │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   HARVEST (Slaughter)                           │
│  - allocate cost using harvest allocation formula              │
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

## ✅ Final Notes

- All formulas are implemented in **`BatchCalculationService`**, **`BatchStateService`**, and the various controllers.
- The **checkpoint approach** ensures that historical records are never recalculated – only the current state is updated.
- The **migration log** provides full auditability.
- All logic is **sector‑independent** and will be reused for the fishery module.

---

*This document is maintained as the single source of truth for all calculations in the BWET Farms poultry module.*