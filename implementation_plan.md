# AURA ERP — Polishing Plan v3 (FINAL — All Decisions Locked)

> [!IMPORTANT]
> All open questions have been answered. This plan is ready for execution.

---

## Decisions Summary

| Question | Decision |
|---|---|
| Currency | **JOD** (Jordanian Dinar) — all prices, invoices, reports |
| Production Optimizer | **Two algorithms**: Fastest (min iterations) & Cheapest (min cost). Toggle between them. Supports multi-recipe combinations for multi-item orders |
| Site User OTP | **Dual mode**: Shows on screen for dev/testing + sends real email (like Learning Platform) |
| Data cleanup | Keep financial history. Add **auto-archive after 24h** for delivered orders |
| Hiring status | **Applicants can track status** (New → Reviewed → Interview → Hired/Rejected) |
| Optimizer action | **Recommend only** with a quick-click "Start Production" button. All batches must be **linked to an order** |

---

## WP1: Currency Fix + Selling Prices ⚡ HIGH

**DB Change:**
```sql
ALTER TABLE item_master ADD COLUMN selling_price DECIMAL(12,2) DEFAULT 0.00;
```

**Replace all `$` symbols with `JOD` across the entire system** (storefront, ERP views, invoices, reports).

**Selling Prices (JOD, ~50% margin over production cost):**

| Product Type | Price Range (JOD) |
|---|---|
| Standard Tiles (ساده, رملي, etc.) | 6.00 – 9.00 |
| Premium Tiles (بروازي, مطبه فرز, etc.) | 8.00 – 12.00 |
| Marble Slab 120×60 | 60.00 |
| Granite Tile 60×60 | 32.00 |
| Kitchen Countertop 200×60 | 110.00 |
| Decorative Panel 100×50 | 48.00 |
| Bathroom Vanity Top | 85.00 |
| Wall Cladding 30×30 | 14.00 |
| Cornices (20, 25, درج, etc.) | 9.00 – 12.00 |
| Corners (زوايا 15, 20, 25) | 6.00 – 8.00 |
| Crowns (تاج 40, 50) | 11.00 – 14.00 |
| Bases (قاعده 40, 50) | 10.00 – 13.00 |
| Columns (عامود) | 25.00 – 32.00 |

**Files to modify:** Every view/page that displays `$` → `JOD`

---

## WP2: Data Cleanup & Fresh Orders ⚡ HIGH

1. Delete all 17 orphan orders (no line items)
2. Clean linked production batches if any
3. Seed 7 fresh orders with proper `sales_order_lines`:

| Order | Customer | Status | Items | Total (JOD) |
|---|---|---|---|---|
| Fresh-01 | Al-Aqsa Construction | Pending | 50× Plain Tile, 30× Sand Tile | 570 |
| Fresh-02 | Petra Stone Designs | Pending | 10× Marble Slab, 5× Countertop | 1,150 |
| Fresh-03 | Gulf Building | In Production | 200× Granite Tile, 20× Wall Cladding | 6,680 |
| Fresh-04 | Mediterranean Builders | In Production | 100× Patterned Tile, 50× Cornice 25 | 1,500 |
| Fresh-05 | Al-Aqsa Construction | Pending Delivery | 80× Plain Tile, 40× Frame Tile | 840 |
| Fresh-06 | Sahara Interior | Delivered | 3× Vanity Top, 5× Decorative Panel | 495 |
| Fresh-07 | Petra Stone Designs | Archived | 20× Marble Slab | 1,200 |

---

## WP3: Smart Production Optimizer 🆕 HIGH

### Two Algorithms (User toggles between them)

**Algorithm 1 — FASTEST (Minimize Total Iterations)**
- Goal: Fulfill the order in the fewest mix runs possible
- Strategy: For each item, pick the recipe that produces the **most** of it per run
- Use case: **Urgent orders** — get it done ASAP

**Algorithm 2 — CHEAPEST (Minimize Total Material Cost)**
- Goal: Fulfill the order with the lowest raw material expenditure
- Strategy: For each item, pick the recipe with the **lowest cost per unit** of that item
- Use case: **Standard orders** — maximize profit margin

### How It Works (Multi-Recipe Combination)

**Example Order:** 200× FG-A + 40× FG-B

| Recipe | FG-A per run | FG-B per run | Cost per run |
|---|---|---|---|
| Mix 1 | 20 | 0 | 150 JOD |
| Mix 2 | 0 | 5 | 80 JOD |
| Mix 3 | 10 | 3 | 120 JOD |

**FASTEST algorithm:**
- FG-A: Mix 1 makes 20/run → ⌈200/20⌉ = 10 runs
- FG-B: Mix 2 makes 5/run → ⌈40/5⌉ = 8 runs
- **Total: 18 runs** | Cost: (10×150) + (8×80) = **2,140 JOD**

**CHEAPEST algorithm:**
- Mix 3 produces both: 10 FG-A + 3 FG-B per run
- For FG-A: ⌈200/10⌉ = 20 runs → produces 60 FG-B (enough!)
- **Total: 20 runs** | Cost: 20×120 = **2,400 JOD** — wait, this is MORE expensive
- Try combinations: Mix 1 for A (10 runs = 1,500) + Mix 2 for B (8 runs = 640) = **2,140 JOD**
- Actually cheapest: Mix 3 alone 14 runs (covers A=140... not enough)
- **The algorithm evaluates ALL viable combinations and picks the one with lowest total cost**

### Algorithm Pseudocode
```
function optimize(order_items, mode):
    recipes = get_all_recipes_with_outputs()
    
    // Build a matrix: recipe × item → output_per_run
    // Try all recipe combinations that can cover all items
    
    for each viable_combination:
        for each item in order:
            find limiting_iterations = max(ceil(qty_needed / output_per_run))
        total_cost = sum(iterations × recipe_cost)
        total_iterations = sum(iterations)
    
    if mode == 'fastest':
        return combination with min(total_iterations)
    else:
        return combination with min(total_cost)
```

### UI Page: `views/prod_optimizer.php`
- **Step 1:** Select a Pending or In Production order
- **Step 2:** Toggle: ⚡ Fastest | 💰 Cheapest
- **Step 3:** See recommendation table:
  - Recipe name, iterations needed, raw materials consumed, total cost
  - Surplus FG produced (bonus inventory)
  - Comparison with alternative combinations
- **Step 4:** "Start Production" quick-click → creates production batch(es) linked to the order

### Batch Linking Rule
> All production batches MUST be linked to a sales order (`so_id`). No orphan batches allowed. The "New Production Batch" button on Mix Batches page should require selecting an order first.

**Files:**
- **[CREATE]** `modules/production/optimize_production.php` — engine
- **[CREATE]** `views/prod_optimizer.php` — UI
- **[MODIFY]** `views/prod_orders.php` — add "Optimize" button per order
- **[MODIFY]** `views/mix_batches.php` — "New Batch" requires order selection
- **[MODIFY]** `includes/sidebar.php` — add Optimizer link

---

## WP4: Supplier Lead Times 🟡 MEDIUM

Already in `supplier_items.lead_time_days`. Seed realistic values:

| Supplier | Lead Time | Reason |
|---|---|---|
| Jordan Quarry Corp | 3 days | Local (Zarqa) |
| Turkish Marble Exports | 14 days | Import |
| Egyptian Aggregate Supply | 10 days | Import |
| Saudi Chemical Solutions | 12 days | Import |
| Italian Stone Masters | 21 days | Premium import |

**Files:** `proc_create.php`, `proc_track.php`, `proc_rop.php`

---

## WP5: EOQ & Inventory Cost Reports 🟡 MEDIUM

- Add `order_setup_cost` to suppliers, `annual_holding_cost_pct` to items
- **[CREATE]** `views/reports/inventory_costs.php` — EOQ report:
  - `EOQ = √(2DS/H)` per raw material
  - Annual holding + ordering costs
  - Total cycle inventory value

---

## WP6: Site User Registration (OTP) 🟡 MEDIUM

**Separate from ERP employees.** Required for checkout AND job applications.

```sql
CREATE TABLE site_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    company_name VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT DEFAULT 0,
    otp_hash VARCHAR(255),
    otp_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**OTP behavior (dual mode — inspired by Learning Platform):**
- Sends real email to the address (when SMTP is configured)
- ALSO shows OTP on screen briefly for development/demo purposes
- When SMTP isn't available, falls back to screen-only

**Files:**
- **[CREATE]** `register.php`, `login.php`, `verify_email.php`
- **[CREATE]** `modules/auth/site_user_auth.php`
- **[MODIFY]** `checkout.php` — require site_user login
- **[MODIFY]** Storefront navbar — Login/Register/User name

---

## WP7: Hiring Page → HR Pipeline 🟢 NEW

```sql
CREATE TABLE job_postings (
    posting_id INT AUTO_INCREMENT PRIMARY KEY,
    position_title VARCHAR(150) NOT NULL,
    department_id INT,
    description TEXT,
    requirements TEXT,
    status ENUM('Open','Closed') DEFAULT 'Open',
    posted_date DATE DEFAULT CURRENT_DATE
);

CREATE TABLE job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    site_user_id INT NOT NULL,
    posting_id INT NOT NULL,
    cv_path VARCHAR(255),
    cover_letter TEXT,
    status ENUM('New','Reviewed','Interview','Hired','Rejected') DEFAULT 'New',
    rejection_reason TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_user_id) REFERENCES site_users(user_id),
    FOREIGN KEY (posting_id) REFERENCES job_postings(posting_id)
);

CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    interview_date DATE NOT NULL,
    interview_time TIME NOT NULL,
    location VARCHAR(255) DEFAULT 'MiskStone Factory, Amman',
    interviewer_id INT,
    notes TEXT,
    result ENUM('Pending','Passed','Failed') DEFAULT 'Pending',
    FOREIGN KEY (application_id) REFERENCES job_applications(application_id),
    FOREIGN KEY (interviewer_id) REFERENCES users(user_id)
);
```

**Public side:**
- `careers.php` — lists open positions
- `apply.php` — application form + CV upload (requires site_user login)
- Applicant can log in → see application status updates

**HR side:**
- `views/hr_applications.php` — incoming applications, CV download, review
- `views/hr_interviews.php` — schedule interview (date + time + factory location), mark result
- On "Hired" → option to create employee record in HR system

**Seed:** Production Worker, Sales Rep, Procurement Officer, Warehouse Operator, Quality Inspector

---

## WP8: Kanban Realignment 🟡 MEDIUM

- Enforce valid transitions only (no dragging Pending → Delivered)
- Show automated vs manual transitions
- Connect Kanban status changes to actual production/delivery workflows

---

## WP9: Auto-Archive + Reports 🟢 NICE TO HAVE

**Auto-archive:** Delivered orders auto-archive after 24 hours (cookie/timer check on page load).

**Reports:**
- Inventory Cost Analysis (EOQ, holding costs)
- Supplier Performance (lead time adherence)
- Production Efficiency (stage durations, yield)
- BOM Cost Trends

---

## Execution Order

```
Phase 1 (Fix Foundation):     WP1 (Prices+JOD) → WP2 (Clean Data)
Phase 2 (Core Features):      WP3 (Optimizer) → WP8 (Kanban)
Phase 3 (User System):        WP6 (Registration) → WP7 (Hiring)
Phase 4 (Analytics):          WP4 (Lead Times) → WP5 (EOQ) → WP9 (Reports)
```

> [!TIP]
> Phase 1 can be done in one session. Phase 2 is the most impressive feature for your portfolio. Phase 3 adds a complete public-facing pipeline. Phase 4 is polish.
