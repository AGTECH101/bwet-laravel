# 📘 BWET Farms — The Complete Guide

**Version 3.1 — September 2026**
*Written for farm managers, staff, and investors. No programming knowledge required.*

---

## Table of Contents

1. [What is BWET Farms?](#1-what-is-bwet-farms)
2. [Who Uses It](#2-who-uses-it)
3. [The Big Picture](#3-the-big-picture)
4. [Core Concepts](#4-core-concepts)
5. [The Bird Count](#5-the-bird-count)
6. [The Cost](#6-the-cost)
7. [The Weight](#7-the-weight)
8. [Mortality Split — The Theft Detective](#8-mortality-split)
9. [Transfers — Moving Birds Between Batches](#9-transfers)
10. [Feed and FCR](#10-feed-and-fcr)
11. [Weight Records and CV](#11-weight-records-and-cv)
12. [Inventory](#12-inventory)
13. [Price Calculator](#13-price-calculator)
14. [Automated Alerts (Triggers)](#14-automated-alerts)
15. [Manual Mode](#15-manual-mode)
16. [System Variables (The Settings)](#16-system-variables)
17. [Frequently Asked Questions](#17-faq)
18. [Glossary](#18-glossary)

---

## 1. What is BWET Farms?

BWET Farms is a digital record-keeping system for a working poultry farm. It replaces the paper notebook and the Excel spreadsheet with something that **calculates automatically** as you enter data.

The core promise is simple: **every time you log anything, the system tells you what your batch is worth right now** — how much each bird costs you, how much you should sell it for, and whether the flock is healthy or in trouble.

You record reality — feed, weights, deaths, expenses. The system does the math.

---

## 2. Who Uses It

There are four roles, each with a different view:

| Role | What They Do | What They See |
|------|--------------|---------------|
| **Admin** | Full control. Manages users, system settings, corrects mistakes | Everything, including cost and profit |
| **Manager** | Runs day-to-day operations. Creates batches, approves staff | Operational data, no financials |
| **Staff** | Records data at the farm — weights, feed, deaths | Only the forms they need |
| **Investor** | Watches their stake grow | Their investment and its return |

New staff accounts must be approved by an Admin or Manager before they can log in.

---

## 3. The Big Picture

Here's how a batch of birds travels through the system:

```
┌─────────────────────────────────────────────────────────────┐
│  1. You place an order for chicks                            │
│     → You create a "Batch" in the system                     │
│     → You enter: start date, how many birds, what they cost  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Every day, you record reality                            │
│     → Weight of a sample of birds                            │
│     → Feed consumed                                          │
│     → Deaths, culls, birds sold or slaughtered                │
│     → Money spent (medication, labor, utilities, etc.)       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. The system recalculates automatically                    │
│     → Every entry triggers a full rebuild of the batch       │
│     → Cost per bird, feed efficiency, mortality — all fresh  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. You use the numbers to make decisions                    │
│     → Price for a customer (via Price Calculator)            │
│     → When to slaughter (via Alerts)                         │
│     → Whether the flock is healthy (via CV, FCR)             │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Core Concepts

### 4.1 The Batch

A **batch** is a group of birds you're raising together. It has:

- A **start date** (when the chicks arrived)
- A **starting flock** (how many birds you started with)
- An **initial cost** (how much you paid for the chicks)
- A **status** — *active*, *closed*, or *completed*

Every record in the system belongs to a batch. Nothing exists outside of a batch except general expenses (which are farm-level and don't count toward any single batch's cost).

### 4.2 The Records

There are five kinds of records you'll create:

| Record Type | What It Captures |
|-------------|------------------|
| **Flock Record** | Deaths (mortality), culls, slaughter on a given day |
| **Weight Record** | Weight of individual birds (a sample) |
| **Feed Record** | How much feed was consumed from stock |
| **Expense** | Money spent on the batch (or the farm) |
| **Inventory Consumption** | Stock used that isn't feed (medicine, packaging, etc.) |

### 4.3 Recalculation — The Heart of the System

**This is the most important idea in BWET Farms:**

> **The batch's state is never accumulated. It is always rebuilt from scratch.**

Every time you add, edit, or delete any record, the system:
1. Reads every record for the batch (all weights, all feed, all deaths, all transfers)
2. Recomputes the bird count, total cost, total weight, mortality rate, FCR — everything
3. Saves the fresh values onto the batch

**Why this matters:** If you delete a record six months after entering it, the system doesn't try to "un-add" it. It just rebuilds from what's left. Nothing can drift, no ghost numbers survive.

Think of it like a bank reconciliation. You don't try to reverse every transaction — you just re-add the ledger from the start.

---

## 5. The Bird Count

### 5.1 The Formula

```
Current Birds = Starting Flock
              − (all deaths ever recorded)
              − (all culls ever recorded)
              − (all birds slaughtered ever recorded)
              + (birds transferred IN)
              − (birds transferred OUT)
```

Simple. If you started with 1,000 and 50 died, you have 950.

### 5.2 Why Transfers "Add" to Starting Flock

When you transfer 200 birds **into** a batch, the receiving batch's `starting_flock` increases by 200. This is so that mortality percentage stays meaningful:

- A batch that received 200 birds after starting with 500 is treated as if it started with 700
- If 35 of those 200 birds later die, the mortality rate reflects the full 700-bird base

### 5.3 Scenario

**Day 1:** You create Batch A with 1,000 chicks.

**Day 15:** 30 birds die. Recorded as a Flock Record.

**Day 30:** 20 more die.

**Day 30 (same day):** You transfer 200 birds to Batch B.

| Step | Starting Flock | Deaths | Transferred Out | Current Count |
|------|----------------|--------|-----------------|---------------|
| Day 1 | 1,000 | 0 | 0 | 1,000 |
| Day 15 | 1,000 | 30 | 0 | 970 |
| Day 30 | 1,000 | 50 | 0 | 950 |
| Day 30 after transfer | 1,000 | 50 | 200 | 750 |

Batch B's view after the same transfer:

| Step | Starting Flock | Deaths | Transferred In | Current Count |
|------|----------------|--------|----------------|---------------|
| Before | 500 | 20 | 0 | 480 |
| After | 700 | 20 | 200 | 680 |

---

## 6. The Cost

### 6.1 What Counts Toward a Batch's Cost

Five things add to a batch's cost:

1. **Initial chick cost** — what you paid for the starting birds
2. **Feed** — every feed record's total cost
3. **Expenses** — medication, labor, transport, etc.
4. **Manual inventory consumptions** — non-feed items used (medicine, packaging)
5. **Transfers in/out** — cost that travels with the birds

Two things do **NOT** count:

- **Waste** (spoiled/damaged stock) — the farm lost money, but the birds that ate good feed shouldn't be charged for the loss
- **Feed-sourced inventory consumptions** — these are already reflected via the feed record itself. Counting both would double-count

### 6.2 The Formula

```
Total Cost of Batch =
    Initial chick cost
  + All feed records' cost
  + All expenses
  + All manual (non-feed, non-waste) consumptions
  + (Transfer-in cost)  ← cost that came with birds you received
  − (Transfer-out cost)  ← cost that left with birds you sent away
```

### 6.3 Cost Per Bird (COP)

```
Cost Per Bird = (Total Cost − Cost Already Allocated) / Current Bird Count
```

Where "Cost Already Allocated" is cost that has been formally assigned to birds you already sold/slaughtered (if you use the allocation feature).

### 6.4 Scenario — The Core Business Rule

You've spent **₦400,000** on a batch. Here's what happens to cost per bird as birds die:

| Birds Alive | Total Cost | Cost Per Bird |
|-------------|------------|---------------|
| 1,000 | ₦400,000 | ₦400 |
| 900 | ₦400,000 | ₦444 |
| 800 | ₦400,000 | ₦500 |
| 500 | ₦400,000 | ₦800 |

**The cost doesn't shrink when birds die.** The farm already spent that money on feed and chicks. The birds that survive carry the full cost.

**This is intentional** — it's how the customer effectively pays for the farm's mortality losses.

### 6.5 Cost Per Kilogram

```
Dressed Weight = Live Weight × (Dress Percentage / 100)
Cost Per Kg    = Cost Per Bird / Dressed Weight
```

**Scenario:**
- Average live weight: 2.5 kg
- Dress percentage: 75% (this is configurable)
- Dressed weight: 2.5 × 0.75 = **1.875 kg**
- Cost per bird: ₦500
- Cost per kg: 500 / 1.875 = **₦266.67**

### 6.6 Scenario — Every Event's Effect on Cost Per Bird

| Event | Total Cost | Bird Count | Cost Per Bird |
|-------|------------|------------|---------------|
| **Start** — 1,000 birds, ₦100,000 spent | ₦100,000 | 1,000 | ₦100 |
| **Add ₦50,000 of feed** | ₦150,000 | 1,000 | ₦150 |
| **Add ₦20,000 of labor** | ₦170,000 | 1,000 | ₦170 |
| **50 birds die** | ₦170,000 | 950 | ₦178.95 |
| **Slaughter 100 birds** | ₦170,000 | 850 | ₦200 |
| **Transfer 200 birds out (with ₦40,000 cost)** | ₦130,000 | 650 | ₦200 |
| **Transfer 300 birds in (bringing ₦60,000 cost)** | ₦190,000 | 950 | ₦200 |

Notice how transfers keep cost per bird unchanged — that's the correct behavior. The birds carry their proportional share of cost with them.

---

## 7. The Weight

### 7.1 How Current Weight Is Computed

The system starts from the most recent weight record and walks forward:

1. Take the **last weight record's average weight**
2. Multiply by the bird count **at that time**
3. Then apply every event **after that weigh-in**, in order:
   - **Deaths** → subtract `count × batch average weight`
   - **Culls** → subtract `count × batch average weight`
   - **Slaughter** → subtract `count × the actual recorded slaughter weight`
   - **Transfer in/out** → add/subtract the actual weight that moved

### 7.2 Why This Matters

The old (wrong) way was:
```
Current Weight = Current Count × Last Average Weight
```

This assumed every bird that left had the average weight. But when you sell 100 heavy birds (say 3.0 kg each) from a batch averaging 2.5 kg, the survivors' average should drop — you sold the big ones.

**Scenario:**
- 1,000 birds at 2.5 kg = 2,500 kg total
- Slaughter 100 birds, average weight **3.0 kg** each
- Physical reality: 2,500 − 300 = **2,200 kg** across 900 birds = 2.444 kg average

The old formula would have said: 900 × 2.5 = **2,250 kg** — wrong by 50 kg.

**The new formula uses the actual recorded slaughter weight.**

### 7.3 Average Weight After Any Event

```
New Average Weight = New Total Weight / New Bird Count
```

### 7.4 Dressed Weight (Live vs Sellable)

A live bird is not a sellable bird. You lose weight during processing.

```
Dressed Weight = Live Weight × (Dress Percentage / 100)
```

Dress percentage is a system setting (default 75%). It varies by breed and by customer preference.

---

## 8. Mortality Split

### 8.1 The Problem It Solves

When you transfer birds from Batch A to Batch B, some of A's history travels with them — including the deaths that already happened.

Without tracking this, Batch B would look artificially healthy because it "never lost a bird," and Batch A would look like it lost more than it did.

### 8.2 The Three Numbers

| Number | Meaning |
|--------|---------|
| **Pen Mortality** | Deaths that physically happened in this batch (from Flock Records) |
| **Historical Mortality** | Deaths carried in from transfers − deaths carried out |
| **Total Mortality** | Pen + Historical (used for mortality rate) |

### 8.3 The Formula

```
pen_mortality        = SUM(flock_records.mortality)

transfer_in_mortality  = total deaths "sent with" birds you received
transfer_out_mortality = total deaths "sent with" birds you sent away (negative)

historical_mortality = pen_mortality + transfer_in_mortality + transfer_out_mortality

mortality_rate = (historical_mortality / starting_flock) × 100
```

### 8.4 Scenario

**Batch A** starts with 1,000 birds. Over time, 50 birds die.

- `pen_mortality` = 50
- `historical_mortality` = 50
- `mortality_rate` = 5.00%

**Now you transfer 200 birds from A to B.**

A's current mortality rate is 50/1000 = 5%. So the transferred birds "carry" 5% of their own count in mortality:
```
transfer_mortality = 200 × 0.05 = 10 deaths
```

The system logs:
- Transfer Out from A: `mortality_moved = −10`
- Transfer In to B: `mortality_moved = +10`

**After recalculation:**

| Batch | Pen Mortality | Historical Mortality | Mortality Rate |
|-------|---------------|---------------------|----------------|
| A | 50 | 40 | 4.00% (40/1000) |
| B | 0 (or its own) | 10 | 1.43% (10/700) |

**Sum check:** 40 + 10 = 50. No deaths were lost or invented.

### 8.5 Why This Is Bulletproof

- Flock records and migrations are **never deleted** by recalculation
- Every number is **derived** from raw data
- Deleting a flock record just removes it from the sum — the next recalc fixes everything

### 8.6 Theft Detection Scenario

Suppose someone walks 100 birds off your farm without telling you. Later, the physical count doesn't match. Here's how the numbers help you see it:

- **Pen mortality** = deaths recorded honestly by staff
- **Current count** = what the system says you should have
- **Physical count** = what you actually have

If the numbers don't match, and pen mortality is low, the missing birds are unaccounted for. That's not a "theft detector" — it's a consistency check that flags where your ledger and your reality disagree.

---

## 9. Transfers

### 9.1 What Is a Transfer?

A transfer moves birds from one active batch to another. You do this for several reasons:

- **Grading** — sell the biggest birds, keep the rest growing
- **Consolidation** — merge two smaller batches into one pen
- **Relocation** — free up a pen for a new batch

### 9.2 The Inputs

When you create a transfer, you provide:

| Input | Meaning |
|-------|---------|
| Source Batch | Where birds are leaving from |
| Destination Batch | Where they're going to |
| Birds to Transfer | How many |
| **Manual Weight** | The **actual** average weight of the birds being moved |
| Reason | Why (free text) |

**Why manual weight?** Because the birds you're transferring might not be average. If you're grading by size, you might be moving only the heaviest birds. The system trusts your number.

### 9.3 What the System Does

1. Computes the transferred bird's "share" of everything:
   ```
   transfer_weight         = count × manual_weight
   transfer_cost           = count × source.cost_per_bird
   transfer_mortality      = count × source.mortality_rate
   transfer_feed           = source.total_feed × (count / source.current_count)
   transfer_weight_gain    = source.total_weight_gain × (count / source.current_count)
   ```

2. Writes **two** migration rows — one outgoing from source (negative values), one incoming to destination (positive values).

3. Increments `destination.starting_flock` by the transferred count.

4. Recalculates both batches from scratch.

### 9.4 Scenario — A Clean Transfer

**Batch A:**
- 1,000 birds alive
- Average weight: 2.5 kg
- Cost per bird: ₦400
- Total feed used: 2,000 kg
- Total weight gain: 2,000 kg
- Historical mortality: 50 (rate = 5%)

**Batch B:**
- 500 birds alive
- Starting flock: 500

**You transfer 200 birds from A to B, at an average weight of 3.0 kg.**

System computes:
```
transfer_weight         = 200 × 3.0 = 600 kg
transfer_cost           = 200 × 400 = ₦80,000
transfer_mortality      = 200 × 0.05 = 10 deaths
transfer_feed           = 2,000 × (200 / 1,000) = 400 kg
transfer_weight_gain    = 2,000 × (200 / 1,000) = 400 kg
```

**After transfer & recalc:**

| | Batch A | Batch B |
|---|---|---|
| Current count | 800 | 700 |
| Starting flock | 1,000 | 700 |
| Historical mortality | 40 | 10 |
| Total feed used | 1,600 kg | 400 kg |
| Total weight gain | 1,600 kg | 400 kg |

Note: Batch B's `starting_flock` increased by 200 — this is deliberate.

### 9.5 What Can't Be Transferred

- **Birds from a closed or completed batch** — only active batches can transfer
- **More birds than the source has** — validation blocks it
- **To the same batch** — you can't transfer to yourself

---

## 10. Feed and FCR

### 10.1 Feed Conversion Ratio — What It Means

FCR measures how efficiently the birds turn feed into body weight:

```
FCR = Feed Consumed / Weight Gained
```

- **FCR of 1.5** — the flock eats 1.5 kg of feed for every 1 kg of weight gain. Good.
- **FCR of 2.0** — the flock eats 2 kg of feed for every 1 kg of weight gain. Below average.
- **Lower is better.**

### 10.2 Two Versions of FCR

The system tracks two:

| Version | What It Measures | When to Use |
|---------|------------------|-------------|
| **cFCR (Cumulative)** | Total feed / total weight gain, from the very start | Overall efficiency |
| **iFCR (Instantaneous)** | Feed used in the last "period" / weight gained in that period | Current trend |

**Period** is defined by the `weighing_frequency_days` setting (default 4).

### 10.3 Reading Them Together

| Comparison | Meaning |
|------------|---------|
| iFCR < cFCR | Efficiency is **improving** — flock is doing better than its average |
| iFCR ≈ cFCR | **Consistent** — no change |
| iFCR > cFCR | Efficiency is **declining** — investigate |

### 10.4 Scenario

A batch has been running 30 days.

- Total feed used: 3,000 kg
- Total weight gain: 1,750 kg
- **cFCR = 3,000 / 1,750 = 1.71**

Last 4 days:
- Feed used: 500 kg
- Weight gained: 250 kg
- **iFCR = 500 / 250 = 2.00**

**Interpretation:** The flock is currently converting feed at 2.00 — worse than its lifetime average of 1.71. Efficiency is declining. Something may be wrong — check feed quality, disease, or environmental stress.

### 10.5 When FCR Updates

FCR is recalculated every time you:
- Add, edit, or delete a feed record
- Add, edit, or delete a weight record
- Perform a transfer
- Edit anything via the Control Panel

There's no stale FCR anywhere.

---

## 11. Weight Records and CV

### 11.1 Sample Size Rule

You don't weigh every bird — that's impractical. Instead:

```
Required Sample = min(max(ceil(bird_count × 0.10), 5), 10)
```

**Translation:**
- 10% of the flock, **but**
- Never fewer than 5 birds, **and**
- Never more than 10 birds

**Example:** A batch with 40 birds → required sample = 5 (10% of 40 = 4, rounded up to 5). A batch with 200 birds → required sample = 10 (10% of 200 = 20, capped at 10). A batch with 3,000 birds → required sample = 10 (10% would be 300, capped at 10).

### 11.2 Coefficient of Variation (CV)

CV tells you how **uniform** your flock is:

```
mean     = sum(weights) / number of birds
variance = average of (each weight − mean)²
stddev   = square root of variance
CV       = (stddev / mean) × 100
```

A low CV means all birds are roughly the same weight. A high CV means some birds are much bigger or smaller than others.

### 11.3 The Four Status Levels

| CV Range | Status | Meaning |
|----------|--------|---------|
| < 10% | Excellent | Very uniform flock |
| 10–12% | Caution | Slightly uneven |
| 12–15% | Warning | Noticeably uneven |
| ≥ 15% | High | Wide variation — flag for monitoring |

### 11.4 High CV Is Accepted, Not Rejected

**Important policy:** A CV of 15% or higher **is saved**. The record is stored, flagged as `high`, and used in every downstream metric (average weight, FCR, batch cost).

When you submit a high-CV record, you see a **warning** (not an error):
> "Weight record saved with high variation (CV 18.50%, threshold 15%). The sample was still recorded and used in analytics. Please monitor feeding and flock health."

**Why save it?** Because rejecting the record would mean:
1. You lose visibility into the flock's variation problem
2. The batch's average weight would go stale
3. You'd have nothing to act on

By saving it and warning you, the system gives you both data and a nudge.

### 11.5 Scenario

You weigh 10 birds and get:
```
1.5, 1.6, 1.55, 1.45, 1.7, 1.5, 1.6, 1.55, 1.4, 1.65
```

- Mean = 1.55
- Variance ≈ 0.0076
- Stddev ≈ 0.087
- **CV = (0.087 / 1.55) × 100 = 5.63% → Excellent**

Same scenario but with two outliers:
```
1.5, 1.6, 1.55, 1.45, 1.7, 1.5, 1.6, 1.55, **0.8**, **2.5**
```

- Mean = 1.53
- Stddev ≈ 0.43
- **CV = (0.43 / 1.53) × 100 = 28.1% → High Variation**

The system saves it, flags it, and warns you. Now you know to check why two birds are wildly off — is it disease? Different genetics? Environmental?

### 11.6 Expected Weight

The system also stores an "expected weight" for each age, so you can compare your actual average against a target:

| Age | Expected Weight |
|-----|-----------------|
| 1 week | 0.18 kg |
| 2 weeks | 0.45 kg |
| 3 weeks | 0.85 kg |
| 4 weeks | 1.30 kg |
| 5 weeks | 1.80 kg |
| 6 weeks | 2.20 kg |
| 7 weeks | 2.50 kg |
| 8+ weeks | 2.70 kg |

If your flock is far behind the target, that's a signal.

---

## 12. Inventory

### 12.1 What Inventory Tracks

Every stock item — feed, medicine, vaccines, packaging, consumables — has:

- **Quantity in stock** — how much you have
- **Quantity used** — how much has been consumed (lifetime)
- **Minimum quantity** — the threshold below which you get a warning
- **Cost per unit** — what it costs per kilogram, bag, unit, etc.
- **Status** — active or killed (deactivated but preserved for audit)

### 12.2 The "Bag" Rule

Many farms measure feed in **bags**, not kilograms. But the system thinks in kilograms because it needs consistent math. So:

> **1 bag = 25 kg**

When you enter an item with unit = "bag":
- Quantity is multiplied by 25
- Cost per unit is divided by 25
- Unit is changed to "kg"

**Scenario:**
- You enter: "10 bags of starter feed, ₦5,000 per bag"
- The system stores: "250 kg, ₦200 per kg"

This happens **automatically**. Every path into the system (the form, the Control Panel, the seeder) uses the same conversion.

### 12.3 How Stock Is Deducted

Every time stock leaves the store, it's tracked. There are four ways:

| Source Type | Trigger | Counts Toward Batch Cost? |
|-------------|---------|--------------------------|
| **feed** | Automatic — created when you save a feed record | Via the feed record (not double-counted) |
| **manual** | Direct entry — e.g., used medicine on a batch | ✅ Yes |
| **waste** | Spoilage, breakage, expiry | ❌ **No** — never inflates batch cost |
| **expense** | Reserved (not currently created) | — |

### 12.4 Why Waste Doesn't Inflate Cost

**Scenario:** You buy 100 kg of feed. 5 kg spoils before it's used.

- Feed record: 95 kg used, ₦19,000 charged to the batch
- Waste record: 5 kg lost, **not charged to the batch**

The farm lost money on the 5 kg — that's real. But the birds that ate good feed shouldn't see their cost per bird go up because of spoilage.

Waste still reduces stock (100 kg becomes 95 kg used, 0 kg in stock) but doesn't touch batch cost.

### 12.5 The Deduction Path (One Rule)

Stock deduction happens in **exactly one place**: the `InventoryConsumptionObserver`. Every controller, every service, every path — they all create a consumption record and let the observer do the deduction.

**Why this matters:**
- No double deduction
- No "sometimes the stock moves, sometimes it doesn't"
- Deleting a consumption record restores stock automatically

### 12.6 Scenario — Full Lifecycle

**Day 1:** You create "Starter Feed" — 100 kg at ₦200/kg.

**Day 2:** You record a feed record: 20 kg of Starter Feed used for Batch A.
- Stock drops to 80 kg
- Batch A's cost increases by ₦4,000 (20 × 200)
- An inventory consumption row is created (source_type = feed)

**Day 10:** You realize the 20 kg should have been 15 kg.
- You edit the feed record (via Control Panel) → 15 kg
- Old consumption is deleted → 20 kg restored
- New consumption is created → 15 kg deducted
- Stock: 85 kg
- Batch cost: ₦3,000 (adjusted automatically)

**Day 20:** You kill the "Starter Feed" item (stop using it).
- Item is deactivated
- All consumption history is preserved
- You can still see what was used and when

---

## 13. Price Calculator

### 13.1 The Purpose

You have a customer who wants to buy a bird of a specific weight. What should you charge?

The Price Calculator figures that out from your actual batch cost.

### 13.2 The Inputs

| Input | Meaning |
|-------|---------|
| **Batch** | Which batch to price from |
| **Customer Bird Weight** | How heavy the customer wants their bird |
| **Mode Weight** | The most common weight in your batch |
| **Profit Margin** | Your target markup (default from system settings) |

### 13.3 The Formula

```
cost_scaled = (customer_weight / mode_weight) × batch_cost_per_bird
price_per_bird = cost_scaled × (1 + margin / 100)

dressed_weight = customer_weight × (dress_percentage / 100)
price_per_kg = price_per_bird / dressed_weight
price_per_carton = price_per_kg × 10
```

### 13.4 Scenario

- Batch cost per bird: **₦400**
- Mode weight: **2.5 kg**
- Customer wants: **3.0 kg**
- Margin: **20%**
- Dress percentage: **75%**

**Step 1 — Scale the cost:**
```
cost_scaled = (3.0 / 2.5) × 400 = 1.2 × 400 = ₦480
```

**Step 2 — Add margin:**
```
price_per_bird = 480 × 1.20 = ₦576
```

**Step 3 — Compute dressed weight:**
```
dressed_weight = 3.0 × 0.75 = 2.25 kg
```

**Step 4 — Per kg:**
```
price_per_kg = 576 / 2.25 = ₦256
```

**Step 5 — Per carton (10 kg):**
```
price_per_carton = 256 × 10 = ₦2,560
```

**What you tell the customer:** "For a 3 kg bird, ₦576. That works out to ₦256 per kilogram, or ₦2,560 per 10 kg carton."

### 13.5 Why We Scale by Weight Ratio

A heavier bird costs more to raise — it ate more feed, consumed more of everything. But not **linearly**. Fixed costs (chick price, initial vaccines) don't grow with weight.

The system simplifies by assuming cost scales proportionally with weight. This overestimates the cost of heavier birds slightly, but keeps pricing easy to explain.

---

## 14. Automated Alerts

### 14.1 The Six Triggers

The system watches for six conditions and raises a flag on the batch page:

| Trigger | Condition | Severity |
|---------|-----------|----------|
| **Daily Profit** | Marginal profit ≤ tolerance (default −15%) | Critical |
| **FCR Efficiency Drop** | (iFCR / cFCR − 1) × 100 ≥ 20% | Warning |
| **Stop Loss** | Profit fell more than ₦20,000 from peak | Critical |
| **Missed Weighings** | 3+ scheduled weighings not done | Emergency |
| **Weight Loss** | Weight dropped > 5% between weighings | Emergency |
| **High Mortality** | Mortality rate ≥ 7% | Emergency |

### 14.2 What Each Trigger Means in Practice

**Daily Profit** — the batch lost money for the last period. Maybe feed got expensive, or prices fell.

**FCR Efficiency Drop** — the flock is converting feed less efficiently than its historical average. Something's wrong.

**Stop Loss** — the batch reached its highest profit at some point, then dropped significantly. If you'd sold at peak, you'd have made more.

**Missed Weighings** — the scheduled weight sample wasn't done. Data is stale; predictions may be off.

**Weight Loss** — the flock lost weight between two samples. Serious. Could be disease, starvation, heat stress, or theft.

**High Mortality** — more than 7% of the batch has died. Something is very wrong.

### 14.3 Emergency Triggers → Manual Mode

The three emergency triggers (missed weighings, weight loss, high mortality) also flag the batch as being in **manual mode** — see section 15.

### 14.4 Scenario

A batch has been performing well. Last weighing was 5 days ago. The batch page shows:

**⚠️ Attention Required**
- **Emergency** — Missed 3 consecutive weighings. Switching to manual mode.
- **Warning** — FCR efficiency dropped by 23.5%, above tolerance of 20%.

You investigate. Turns out a staff member was on leave and nobody covered the weighing. You schedule a weighing, and once CV data flows back in, the FCR may settle.

The batch is flagged **Manual Mode** until you explicitly clear it.

---

## 15. Manual Mode

### 15.1 What It Is

Manual Mode is a flag on a batch that says:

> "The automated system detected conditions where its advice may not be trustworthy. A human should look at this before acting."

**It does not close the batch, block records, or stop anything.** It just surfaces the flag prominently.

### 15.2 When It Fires

Automatically, when any of these emergency triggers fire:
- 3+ missed weighings
- Weight loss > 5% between consecutive weighings
- Mortality rate ≥ 7%

### 15.3 What It Does

- Sets `is_manual_mode = true` on the batch
- Logs the reason
- Sends a notification to admins
- Shows a badge on the batch page and dashboards

### 15.4 What It Does NOT Do

- ❌ Close the batch
- ❌ Block new records
- ❌ Stop recalculation
- ❌ Freeze pricing or exports
- ❌ Affect other batches

### 15.5 How to Clear It

An admin opens the batch page and clicks "Clear Manual Mode". This:
- Sets `is_manual_mode = false`
- Logs who cleared it and when
- Sends a notification

### 15.6 Scenario

A batch had a bad week — 8% mortality and two missed weighings. Manual Mode fires.

Staff record a full weigh-in. The flock is recovering. Mortality is now stable. The manager asks admin to clear manual mode.

Admin clears it. The batch continues as normal, but the audit log shows the flag was raised and cleared.

**Why keep the history?** If something goes wrong later, you have a clear record of when automated oversight was suspended.

---

## 16. System Variables

These are settings an admin can change. They affect every calculation in the system.

| Setting | Default | What It Changes |
|---------|---------|-----------------|
| **Profit Margin** | 20% | How much above cost you aim to sell for |
| **Dress Percentage** | 75% | What % of live weight becomes sellable meat |
| **Weighing Frequency** | 4 days | How often you should weigh (used in iFCR, schedules) |
| **Daily Profit Tolerance** | −15% | Below this, the batch triggers a "critical" alert |
| **FCR Efficiency Tolerance** | 20% | FCR drop above this triggers a warning |
| **Stop Loss Amount** | ₦20,000 | Retracement from peak profit that triggers a critical alert |

### 16.1 Changing a Setting — What Happens

Every variable has an **effective date**. When you change a setting, the new value applies from now on. The old value is preserved in the audit trail.

This means historical reports show **what the setting was at the time**, not what it is today. If you raise the profit margin from 20% to 30% in October, September's reports still show 20%.

### 16.2 Scenario

- In September, profit margin = 20%. You sell a batch at ₦480/bird (cost ₦400).
- In October, you raise profit margin to 30%.
- You run a September report. It shows the correct historical margin of 20%.

The system doesn't retroactively rewrite history.

---

## 17. FAQ

### Q: What happens if I delete a feed record?

Three things happen automatically:
1. Inventory is restored (the feed goes back into stock)
2. The linked inventory consumption is deleted
3. The batch recalculates — cost drops, FCR updates

You don't have to do anything else.

### Q: What if I enter the wrong weight for a bird?

Open the Control Panel (admin only), find the weight record, edit it. The system recalculates CV and updates the batch. No manual fixing required.

### Q: Can I add multiple feed records for the same day?

Yes. Multiple entries per day are allowed. Each contributes to the same batch's totals.

### Q: What happens if two people record feed at the same time?

Each feed record is separate. Both contribute to the batch cost. If one was a mistake, delete it — the batch recalculates instantly.

### Q: How do I know if a batch is profitable?

Look at the batch page. It shows:
- **Cost per bird**
- **Recommended selling price per bird**
- **Current marginal profit %**
- **Peak profit** (highest profit ever seen on this batch)

If current profit is well below peak, you may have missed your best window.

### Q: Can I transfer birds to a closed batch?

No. Only active batches can receive or send birds.

### Q: What happens if a bag of feed is 50 kg, not 25 kg?

The system assumes 25 kg per bag. If your bags are different, use the "kg" unit and enter the weight directly.

### Q: Can I undo a transfer?

Not yet — reverse transfers are a planned feature. For now, contact an admin if a transfer needs correcting.

### Q: Why does my cost per bird keep increasing when birds die?

Because the farm already spent the money on those birds. Feed, medicine, and labor were paid for. That cost is now spread across fewer surviving birds. When they die, the customer pays more per surviving bird.

### Q: What if my batch has no weight records yet?

The system uses a chick weight (0.045 kg default) as the starting point. As soon as you record your first weigh-in, this becomes the reference.

### Q: Can I export data?

Yes. Admins and managers can export to Excel (.xlsx) or CSV. There are quick-export buttons for common reports.

### Q: Why does the system sometimes say "Manual Mode"?

See Section 15. It means the system detected conditions that make its advice unreliable — missed weighings, high mortality, or weight loss. Investigate, then clear it when you're satisfied.

### Q: What if I make a mistake and the numbers look wrong?

The system never "remembers wrong." Fix the record (via the form or Control Panel) and the batch recalculates instantly. If the batch still looks off, use the admin route `/recalculate-batches` to force a rebuild of all batches.

---

## 18. Glossary

| Term | Meaning |
|------|---------|
| **Batch** | A group of birds raised together from placement to sale |
| **cFCR** | Cumulative Feed Conversion Ratio — feed ÷ weight gain from the start |
| **iFCR** | Instantaneous FCR — feed ÷ weight gain over the last period |
| **COP** | Cost Per bird (also written as cost per bird) |
| **Cull** | A bird removed from the flock for being unsuitable (not counted as death) |
| **CV** | Coefficient of Variation — a measure of how uniform the flock is |
| **Dressed Weight** | The sellable weight of a bird after processing |
| **Flock Record** | A daily log of deaths, culls, and slaughter |
| **Historical Mortality** | Deaths carried in from previous batches via transfer |
| **Manual Mode** | A batch flag indicating automated oversight is suspended |
| **Pen** | A physical enclosure on the farm housing a batch |
| **Pen Mortality** | Deaths that physically occurred in this batch |
| **Slaughter Weight** | The actual average weight of birds being slaughtered |
| **Transfer** | Moving birds from one batch to another |
| **Waste** | Stock lost to spoilage/breakage — never charged to a batch |

---

## Appendix — A Full Walkthrough

Here's how a batch of 1,000 chicks would look over a 42-day cycle. Just the events, with what the system would calculate at each step.

| Day | Event | Birds | Cost | COP | Weight/Bird | Notes |
|-----|-------|-------|------|-----|-------------|-------|
| 1 | Create batch, 1,000 chicks, ₦150,000 | 1,000 | ₦150,000 | ₦150 | 0.045 kg | Chicks placed |
| 3 | Feed 40 kg (₦200/kg = ₦8,000) | 1,000 | ₦158,000 | ₦158 | 0.045 kg | Early feed |
| 7 | Weigh 10 birds — CV 6%, avg 0.18 kg | 1,000 | ₦158,000 | ₦158 | 0.18 kg | Excellent uniformity |
| 7 | Feed 80 kg (₦16,000) | 1,000 | ₦174,000 | ₦174 | 0.18 kg | — |
| 14 | Weigh 10 birds — avg 0.44 kg | 1,000 | ₦174,000 | ₦174 | 0.44 kg | — |
| 14 | Feed 200 kg (₦40,000) | 1,000 | ₦214,000 | ₦214 | 0.44 kg | — |
| 15 | 15 birds die | 985 | ₦214,000 | ₦217 | 0.44 kg | Mortality raises COP |
| 21 | Weigh 10 birds — avg 0.83 kg, CV 11% | 985 | ₦214,000 | ₦217 | 0.83 kg | Caution on CV |
| 21 | Feed 300 kg (₦60,000) | 985 | ₦274,000 | ₦278 | 0.83 kg | — |
| 28 | Add ₦30,000 labor expense | 985 | ₦304,000 | ₦309 | 0.83 kg | — |
| 28 | Weigh 10 birds — avg 1.28 kg | 985 | ₦304,000 | ₦309 | 1.28 kg | — |
| 35 | Weigh 10 birds — avg 1.82 kg | 985 | ₦304,000 | ₦309 | 1.82 kg | — |
| 42 | Sell 300 birds — avg weight 2.20 kg | 685 | ₦304,000 | ₦444 | 2.20 kg | Heavy birds sold first, cost stays |
| 42 | Slaughter 200 birds — avg weight 2.15 kg | 485 | ₦304,000 | ₦627 | 2.09 kg | Weight updated by actual slaughter weight |
| 42 | Add ₦50,000 more feed + ₦20,000 packaging | 485 | ₦374,000 | ₦771 | 2.09 kg | — |
| 42 | Final batch state | 485 | ₦374,000 | ₦771 | 2.09 kg | Ready for final sale |

**At any point, the batch page shows:**
- Current bird count
- Total cost
- Cost per bird
- Average weight
- FCR (iFCR and cFCR)
- Recommended selling price
- Any active alerts

---

*This document is the living README for BWET Farms. When new features are added, this file is updated first — so it stays the single source of truth for how the system works.*