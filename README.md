# 🧮 BWET Farms – Poultry Module: Formulas, Logic & Implementation Blueprint

*Version 3.0 – September 2026*  
*This document is the authoritative reference for all calculations, business rules, and data flows in the poultry module.*

---

## 📖 Table of Contents

1. [Core Data Model](#core-data-model)
2. [Batch State Model (Recalculation Approach)](#batch-state-model)
3. [Record Operations & Recalculation Triggers](#record-operations)
4. [Mortality Split Logic (Critical)](#mortality-split-logic)
5. [Batch Transfer (Grading) with Split Logic](#batch-transfer)
6. [Cost & Financial Metrics](#cost--financial-metrics)
7. [Mortality vs COP Behavior](#mortality-vs-cop-behavior)
8. [Feed Conversion Ratio (FCR)](#feed-conversion-ratio-fcr)
9. [Weight Records & Coefficient of Variation (CV)](#weight-records--cv)
10. [Price Calculator](#price-calculator)
11. [Inventory & Consumption](#inventory--consumption)
12. [Control Panel (Admin-Only Data Correction)](#control-panel)
13. [Batch Age (Dynamic)](#batch-age-dynamic)
14. [System Variables](#system-variables)
15. [Slaughter Triggers (Automated Alerts)](#slaughter-triggers)
16. [Pen Assignment](#pen-assignment)
17. [Migration Log (Audit Trail)](#migration-log)
18. [Flowchart Summary](#flowchart-summary)
19. [Deployment & Recovery](#deployment--recovery)

---

## Core Data Model

| Table | Purpose |
|-------|---------|
| `poultry_batches` | Stores the **current derived state** of a batch (count, weight, cost, mortality, FCR) + base data (start date, starting flock, initial cost). |
| `batch_state_migrations` | Audit trail of **transfers only** (transfer_in / transfer_out). Feeds the mortality-split logic. |
| `flock_records` | Daily mortality, culls, and slaughter events. **One record per batch per day.** |
| `weight_records` | Individual bird weights with CV, mean, average weight. |
| `feed_records` | Daily feed consumption, linked to inventory items. |
| `expenses` | Costs linked to a batch (or general). |
| `inventory_items` | Stock items (feed, medicine, etc.) with quantity, cost, minimum level. |
| `inventory_consumptions` | Usage of inventory items, linked to batch (for cost allocation). |

---

## Batch State Model (Recalculation Approach)

**Core Idea:**
Batch state is **derived, not accumulated**. Every time a record changes (create / update / delete), the entire batch state is rebuilt from raw records + the transfer migration log. This guarantees accuracy after any deletion, correction, or edit.

### 1.1 State Fields

| Column | Description | Derived From |
|--------|-------------|--------------|
| `current_count` | Birds currently alive | `starting_flock − mortality − culls − slaughter − transfers_out` |
| `current_weight_kg` | Total weight (kg) | `current_count × latest average weight` |
| `current_cost` | Total cost not yet allocated | `initial_cost + feed + expenses + inventory − transfers_out + transfers_in` |
| `current_average_weight` | Average weight per bird (kg) | `current_weight_kg ÷ current_count` |
| `current_average_cost` | Average cost per bird (₦) | `current_cost ÷ current_count` |
| `remaining_flock` | Mirror of `current_count` | Same as `current_count` |
| `starting_flock` | Original placement | **Updated on transfers in** (increments) |
| `total_mortality` | Historical mortality (carried) | `pen_mortality + transfer_in_mortality − transfer_out_mortality` |
| `historical_mortality` | Same as above | Synced with `total_mortality` |
| `pen_mortality` | Physical deaths in this batch | `SUM(flock_records.mortality)` |
| `mortality_rate` | % mortality | `(historical_mortality ÷ starting_flock) × 100` |
| `total_culls` | Cumulative culls | `SUM(flock_records.culls)` |
| `total_slaughter` | Cumulative slaughter | `SUM(flock_records.slaughter)` |
| `total_feed_used` | Cumulative feed (kg) | `SUM(feed_records.feed_used) + transfer_in_feed − transfer_out_feed` |
| `total_expenses` | Cumulative expenses | `SUM(expenses.amount)` |
| `total_weight_gain` | Cumulative weight gain | `transfer_in_weight_gain − transfer_out_weight_gain` |
| `current_ifcr` | Instantaneous FCR | See [FCR Section](#feed-conversion-ratio-fcr) |
| `current_cfcr` | Cumulative FCR | `total_feed_used ÷ total_weight_gain` |
| `current_marginal_profit_percent` | Daily marginal profit | See [Metrics Section](#cost--financial-metrics) |
| `peak_profit` | Highest profit seen | Tracked on each recalc |
| `stop_loss_used_percent` | Stop-loss progress | `(peak_profit − current_profit) ÷ stop_loss_amount × 100` |

### 1.2 When Is State Rebuilt?

The following operations call `BatchRecalculationService::recalculateAll()`:

| Operation | Trigger Point |
|-----------|---------------|
| Create/Update/Delete flock record | `FlockRecordController` |
| Create/Update/Delete feed record | `FeedRecordController` |
| Create/Update/Delete weight record | `WeightRecordController` |
| Create/Update/Delete expense | `ExpenseController` |
| Create/Delete inventory consumption | `InventoryConsumptionController` |
| Batch transfer | `BatchTransferController` |
| Batch create/edit | `BatchController` |
| **Any edit via Control Panel** | `ControlPanelController` |
| Manual trigger | `GET /recalculate-batches` |

---

## Record Operations & Recalculation Triggers

### 2.1 Adding a Record

1. Save the raw record (e.g., `FlockRecord::create()`).
2. Call `BatchRecalculationService::recalculateAll($batch)`.
3. State is rebuilt from scratch — no delta math, no drift.

### 2.2 Editing a Record

1. Update the raw record.
2. Call `BatchRecalculationService::recalculateAll($batch)`.

**Special handling:**
- **Feed record edit** → recalculates `feed_cost_per_kg`, `total_feed_cost`; adjusts inventory (restore old, deduct new); updates the linked `InventoryConsumption`.
- **Weight record edit** → recalculates `average_weight`, `total_weight`, `coefficient_variation`, `cv_status`.

### 2.3 Deleting a Record

1. **Restore inventory** (for feed records: quantity_in_stock += feed_used; delete consumption).
2. Delete the raw record.
3. Call `BatchRecalculationService::recalculateAll($batch)`.

Because state is derived, **deletion reverses everything automatically** — the deleted row simply stops being counted.

---

## Mortality Split Logic (Critical)

This is the heart of the client's theft-investigation requirement. It's preserved across every recalculation.

### 3.1 The Formula

```
pen_mortality        = SUM(flock_records.mortality) for this batch
transfer_in_mortality  = SUM(mortality_moved WHERE destination_batch_id = batch AND type = 'transfer_in')
transfer_out_mortality = SUM(mortality_moved WHERE source_batch_id = batch AND type = 'transfer_out')   ← negative

historical_mortality = pen_mortality + transfer_in_mortality + transfer_out_mortality
mortality_rate       = (historical_mortality / starting_flock) × 100
```

### 3.2 Worked Example

**Batch A:** 1,000 birds, 50 deaths.
- pen_mortality = 50
- historical_mortality = 50
- mortality_rate = 5.00%

**Transfer 200 birds A → B.**
Mortality share = 200 × (50 ÷ 1000) = 10.

Migration log:
| source | dest | type | mortality_moved |
|--------|------|------|-----------------|
| A | B | transfer_out | −10 |
| A | B | transfer_in | +10 |

**After recalc:**
| Batch | pen | historical | rate |
|-------|-----|-----------|------|
| A | 50 | **40** | 4.00% |
| B | 0 | **10** | 1.43% (10 ÷ 700) |

Sum check: 40 + 10 = 50 = total deaths ✓

### 3.3 Why It's Bulletproof

- **Immutable sources** — flock records and migration log are never deleted by recalc.
- **Derived** — no state to drift.
- **Delete-friendly** — deleting a flock record removes it from the sum; recalc fixes everything.

---

## Batch Transfer (Grading) with Split Logic

### 4.1 Inputs

| Input | Source | Notes |
|-------|--------|-------|
| `transfer_count` | User | Number of birds |
| `manual_weight` | User | Average weight of the transferred birds (may differ from batch avg) |
| `source`, `destination` | Batch selection | Active batches only |

### 4.2 Calculations

```
transfer_weight = transfer_count × manual_weight
transfer_cost   = transfer_count × source.current_average_cost

source_mortality_rate = source.historical_mortality / source.starting_flock
transfer_mortality    = transfer_count × source_mortality_rate

transfer_fraction     = transfer_count / source.current_count
transfer_feed         = source.total_feed_used × transfer_fraction
transfer_weight_gain  = source.total_weight_gain × transfer_fraction
```

### 4.3 Applying Changes

**Migration log entries (only these are written):**
- `transfer_out` (source): all values negative (`−count`, `−weight`, `−cost`, `−mortality`, `−feed`, `−weight_gain`).
- `transfer_in` (destination): all values positive.

**Then:**
- `destination.starting_flock += transfer_count`
- Full recalc of **both batches**.

**Cost, count, weight, mortality, feed, weight_gain for both batches are recomputed by the recalc service.** No manual deltas.

---

## Cost & Financial Metrics

### 5.1 Cost per Bird (COP)

```
COP_per_bird = current_cost / current_count
```

Only **unallocated** costs are included. Costs already transferred out with birds are excluded.

### 5.2 Dressed Weight Per Bird

```
dressed_weight = current_average_weight × (dress_percentage / 100)
```

### 5.3 Cost per kg (dressed)

```
cost_per_kg = COP_per_bird / dressed_weight
```

### 5.4 Selling Price (Recommended)

```
selling_price_per_bird  = COP_per_bird × (1 + profit_margin / 100)
selling_price_per_kg    = cost_per_kg × (1 + profit_margin / 100)
selling_price_per_carton = selling_price_per_kg × 10
```

---

## Mortality vs COP Behavior

**Client requirement:** *"I want customers to pay for mortality."*

### 6.1 The Rule

When birds die:
- **Cost does NOT decrease** (feed, expenses, and initial cost are already spent).
- **Count decreases.**
- Therefore **COP increases.**

### 6.2 Worked Example

| Scenario | Cost | Count | COP | Sell Price (20% margin) |
|----------|------|-------|-----|-------------------------|
| 0 mortality | ₦400,000 | 1,000 | ₦400 | ₦480 |
| 100 mortality | ₦400,000 | 900 | ₦444 | ₦533 |
| 200 mortality | ₦400,000 | 800 | ₦500 | ₦600 |
| 500 mortality | ₦400,000 | 500 | ₦800 | ₦960 |

The survivors carry the full cost of the batch. Customers pay for the loss.

### 6.3 Event-by-Event Impact

| Event | Cost Impact | Count Impact | COP Impact |
|-------|-------------|--------------|------------|
| **Mortality** | Unchanged | ↓ | ⬆️ Increases |
| **Culls** | Unchanged | ↓ | ⬆️ Increases |
| **Slaughter** | Cost allocated out | ↓ | ⬆️ Slight (cost/count) |
| **Transfer Out** | Cost leaves with birds | ↓ | ➡️ Unchanged |
| **Transfer In** | Cost arrives with birds | ⬆️ | ➡️ Unchanged |
| **Feed Added** | ⬆️ | — | ⬆️ Increases |
| **Expense Added** | ⬆️ | — | ⬆️ Increases |

---

## Feed Conversion Ratio (FCR)

### 7.1 Cumulative FCR (cFCR)

```
cFCR = total_feed_used / total_weight_gain
```

- Lower is better.
- Updated after every feed record and after every transfer (because feed and weight gain shares travel with birds).

### 7.2 Instantaneous FCR (iFCR)

```
iFCR = feed_used_last_period / weight_gained_last_period
```

- Period = `weighing_frequency_days` (default 4).
- Uses the most recent weight records and feed records.

**Interpretation:**
- `iFCR < cFCR` → Current efficiency better than historical.
- `iFCR ≈ cFCR` → Consistent.
- `iFCR > cFCR` → Efficiency declining (warning).

---

## Weight Records & CV

### 8.1 Sample Size

```
required_sample = min(max(ceil(current_count × 0.10), 5), 10)
```

### 8.2 Coefficient of Variation

```
mean     = sum(weights) / count
variance = Σ(weight − mean)² / count
stddev   = √variance
CV       = (stddev / mean) × 100
```

### 8.3 CV Status (High Variation Allowed)

| CV Range | Status | Handling |
|----------|--------|----------|
| < 10% | Excellent | Saved |
| 10–12% | Caution | Saved |
| 12–15% | Warning | Saved |
| ≥ 15% | High | **Saved with flash warning** |

**Threshold is configurable** via the `CV_THRESHOLD` constant in `WeightRecordController` (default 20). High CV is **never rejected** — the client wants the data stored regardless.

### 8.4 Auto-Recalculated Fields on Weight Edit

Editing `individual_weights` (via Control Panel or form) triggers:
- `birds_weighed`, `total_weight`, `average_weight`, `coefficient_variation`, `cv_status`, `expected_weight` — all recalculated.

---

## Price Calculator

**Inputs:**
- `customer_bird_weight` — weight the customer wants (kg)
- `mode_weight` — most frequent weight in the batch (kg)
- `profit_margin` — from system variables

**Formula:**
```
cost_scaled            = (customer_bird_weight / mode_weight) × current_average_cost
selling_price_per_bird = cost_scaled × (1 + profit_margin / 100)
dressed_weight         = customer_bird_weight × (dress_percentage / 100)
selling_price_per_kg   = selling_price_per_bird / dressed_weight
selling_price_per_carton = selling_price_per_kg × 10
```

---

## Inventory & Consumption

### 10.1 Feed Record → Inventory

**On create:**
```
inventory.quantity_in_stock -= feed_used
inventory.quantity_used     += feed_used
InventoryConsumption created (source_type = 'feed', source_id = feed_record.id)
```

**On delete:**
```
inventory.quantity_in_stock += feed_used
inventory.quantity_used     -= feed_used
InventoryConsumption deleted
```

**On edit (Control Panel):**
- Restore old stock to old item.
- Deduct new stock from new item.
- Update or recreate the `InventoryConsumption` row.

### 10.2 Cost Flow

All `inventory_consumptions.total_cost` entries (excluding `waste`) are summed by the recalc service and added to the batch's `current_cost`.

---

## Control Panel

A dedicated admin-only page for correcting mistakes. Only **safe fields** are editable.

### 11.1 Editable Fields Per Table

| Table | Editable | Locked (Read-Only) |
|-------|----------|-------------------|
| `poultry_batches` | name, hatchery, start_date, starting_flock, initial_chicken_cost, status, phase, pen_id | all derived metrics |
| `flock_records` | date, mortality, culls, slaughter, slaughter_avg_weight, notes | delta_* fields |
| `weight_records` | date, individual_weights, notes | average_weight, total_weight, cv, cv_status, expected_weight |
| `feed_records` | date, feed_used, inventory_item_id | feed_cost_per_kg, total_feed_cost, feed_per_bird |
| `expenses` | date, category, description, amount, receipt_number, vendor | — |

### 11.2 Backend Whitelisting

Even if a client bypasses the frontend, the controller enforces the whitelist:

```php
$editable = self::EDITABLE_FIELDS[$table];
$filtered = array_intersect_key($data, array_flip($editable));
$record->update($filtered);
```

**Anything not in the whitelist is silently ignored.**

### 11.3 Special Handling

- **Feed edit** → recompute cost + adjust inventory + update consumption.
- **Weight edit** → decode JSON/comma input → rerun `calculateMetrics()`.
- **Batch edit** → does NOT touch derived fields (only safe base fields).

### 11.4 After Every Operation

The Control Panel calls `BatchRecalculationService::recalculateAll()` — so editing any record propagates correctly to bird count, COP, mortality split, FCR, and all derived metrics.

### 11.5 Transfer Migrations

**Transfer migrations are not exposed in the Control Panel.** They are immutable because reversing a transfer requires also adjusting `starting_flock`. If reversal is needed, it must be done via a dedicated "Reverse Transfer" action (future).

---

## Batch Age (Dynamic)

```
age_days = today − start_date
```

Accessor: `$batch->age_days`. Always fresh, no storage. `current_age_days` column is kept for sorting performance only.

---

## System Variables

| Key | Default | Description |
|-----|---------|-------------|
| `profit_margin` | 20 | Target margin (%) |
| `dress_percentage` | 75 | Dressing out % |
| `weighing_frequency_days` | 4 | Days between weighings |
| `daily_profit_tolerance` | −15 | Daily profit % threshold for alert |
| `fcr_efficiency_tolerance` | 20 | FCR efficiency drop threshold (%) |
| `stop_loss_amount` | 20,000 | Max loss before alert (₦) |

---

## Slaughter Triggers (Automated Alerts)

| Trigger | Condition | Severity |
|---------|-----------|----------|
| Daily profit | `current_marginal_profit_percent <= daily_profit_tolerance` | Critical |
| FCR efficiency | `(iFCR / cFCR − 1) × 100 >= fcr_efficiency_tolerance` | Warning |
| Stop-loss | `peak_profit − current_profit >= stop_loss_amount` | Critical |
| Missed weighings | 3+ missed schedules | Emergency |
| Weight loss | > 5% between consecutive weighings | Emergency |
| High mortality | `mortality_rate >= 7%` | Emergency |

---

## Pen Assignment

When a batch is created with `phase = 'batch'`:
- Find the first available pen with capacity ≥ `starting_flock`.
- Assign; otherwise flash warning.

---

## Migration Log

Every transfer writes two rows in `batch_state_migrations`:

| Column | Description |
|--------|-------------|
| `source_batch_id` | Batch losing birds |
| `destination_batch_id` | Batch gaining birds |
| `migration_type` | `transfer_out` or `transfer_in` |
| `source_type` | `batch_transfer` |
| `count_moved` | Positive on transfer_in, negative on transfer_out |
| `weight_moved` | Same sign convention |
| `cost_moved` | Same sign convention |
| `mortality_moved` | Same sign convention |
| `feed_moved` | Same sign convention |
| `weight_gain_moved` | Same sign convention |
| `source_state_before` | JSON snapshot |
| `destination_state_before` | JSON snapshot |

This log is the **only persisted record** of a transfer. Feeds the mortality and FCR split logic.

---

## Flowchart Summary

```
┌───────────────────────────────────────────────────────────────┐
│                     BATCH CREATION                            │
│  start_date, starting_flock, initial_cost, phase, pen         │
│  → Full recalc initializes derived state                      │
└───────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────────┐
│                     DAILY OPERATIONS                          │
│   Feed ────┐                                                  │
│   Weight ──┤────► Save raw record ──► recalculateAll(batch)   │
│   Flock ───┤                                                  │
│   Expense ─┘                                                  │
└───────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────────┐
│                   BATCH TRANSFER (Grading)                    │
│  - Manual weight entry                                        │
│  - Write 2 migration rows (out / in)                          │
│  - destination.starting_flock += count                        │
│  - recalculateAll(source) + recalculateAll(destination)       │
└───────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────────┐
│                    DELETION / EDIT                            │
│  - Feed delete → restore inventory → delete → recalc          │
│  - Flock delete → delete → recalc                             │
│  - Weight delete → delete → recalc                            │
│  - Expense delete → delete → recalc                           │
│  - Control Panel uses same path (whitelisted fields only)     │
└───────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────────┐
│                   ANALYTICS & DISPLAY                         │
│  - Bird count, weight, cost, COP                              │
│  - Mortality split (pen / historical / rate)                  │
│  - FCR (cFCR, iFCR)                                           │
│  - Growth charts                                              │
│  - Slaughter triggers                                         │
└───────────────────────────────────────────────────────────────┘
```

---

## Deployment & Recovery

### 18.1 Pending Migrations

| Migration | Adds |
|-----------|------|
| `2026_09_09_add_source_to_batch_state_migrations` | `source_type`, `source_id` |
| `2026_09_09_add_delta_columns_to_flock_records` | `delta_count`, `delta_weight`, `delta_mortality` (legacy — no longer used, kept for backward compatibility) |

### 18.2 Force Global Recalculation

```
GET /recalculate-batches   (admin-only)
```

Rebuilds every batch from raw records + migration log. Use after:
- A bulk data import
- A manual SQL fix
- Deploying new recalc logic

### 18.3 Recovery from Bad Data

Because state is derived, corrections are simple:
1. Fix the offending raw record (via Control Panel or SQL).
2. Hit `/recalculate-batches` (or edit any related record — the recalc fires automatically).

No manual "un-allocation" or delta chasing needed.

### 18.4 Immutable Sources

Never delete directly via SQL:
- `batch_state_migrations` (breaks mortality split + `starting_flock`)
- `inventory_consumptions` (breaks inventory traceability)

Use the Control Panel or dedicated actions instead.

---

## ✅ Implementation Status

| Feature | Status |
|---------|--------|
| Recalculation service (`BatchRecalculationService`) | ✅ Every change triggers rebuild |
| Mortality split (pen / historical / rate) | ✅ Preserved across all operations |
| Feed deletion → inventory restore | ✅ |
| Flock / Weight / Feed / Expense delete → full state rebuild | ✅ |
| Duplicate flock record per day blocked | ✅ Friendly error |
| High CV allowed (threshold configurable) | ✅ |
| Control Panel with field whitelisting | ✅ |
| Control Panel covers batches, flock, weight, feed, expenses | ✅ |
| Feed edit recalculation + inventory adjustment | ✅ |
| Weight edit recalculation | ✅ |
| Global `/recalculate-batches` route | ✅ |
| Mortality increases COP (client requirement) | ✅ |

---

*This document is the single source of truth for all calculations in the BWET Farms poultry module.*  
*Version 3.0 — Recalculation-based model with preserved mortality split.*