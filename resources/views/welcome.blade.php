<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BWET Farms | Digitalized Farm Operations</title>
    <meta name="description" content="BWET Farms uses digital records, automated calculations, and intelligent reporting to improve farm operations and decision-making across poultry, fishery, and livestock." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* ── Reset & base ── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f6f3ea;
            color: #1e2a25;
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        /* ── Variables ── */
        :root {
            --forest-deep: #0e2a1f;
            --forest: #1d392c;
            --forest-mid: #285041;
            --cream: #f6f3ea;
            --cream-dim: #ece7d8;
            --amber: #d7a948;
            --amber-deep: #b98a2f;
            --clay: #c17a4f;
            --muted: #5c675f;
            --white: #ffffff;
            --line: rgba(14, 42, 31, 0.12);
            --line-dark: rgba(255, 255, 255, 0.14);
            --shadow: 0 24px 60px rgba(14, 42, 31, 0.18);
            --display: 'Fraunces', serif;
            --body: 'Inter', sans-serif;
            --mono: 'IBM Plex Mono', monospace;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--amber-deep);
        }
        .eyebrow::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--amber);
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }

        /* Primary = amber everywhere. This is the one action color on the
           whole page, so it never blends into a section background,
           light or dark. */
        .btn-primary {
            background: var(--amber);
            color: var(--forest-deep);
            box-shadow: 0 10px 24px rgba(215, 169, 73, 0.28);
        }
        .btn-primary:hover { background: #e2b658; }

        /* Outline on light (cream) sections */
        .btn-outline {
            border-color: var(--line);
            color: var(--forest-deep);
            background: rgba(255,255,255,0.5);
        }
        .btn-outline:hover { border-color: var(--forest-deep); }

        /* Outline on dark (hero / team) sections */
        .btn-outline-dark {
            border-color: var(--line-dark);
            color: var(--white);
            background: rgba(255,255,255,0.06);
        }
        .btn-outline-dark:hover { border-color: rgba(255,255,255,0.4); }

        /* ── Header & Navigation ── */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(246, 243, 234, 0.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }

        nav {
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: var(--display);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--forest-deep);
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%);
            flex-shrink: 0;
        }
        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            font-size: 0.96rem;
            font-weight: 500;
            color: var(--forest);
        }
        .nav-links a { position: relative; }
        .nav-links a::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -6px;
            height: 2px;
            background: var(--amber);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.2s ease;
        }
        .nav-links a:hover::after { transform: scaleX(1); }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Hamburger button (mobile) */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
        }
        .hamburger span {
            display: block;
            width: 28px;
            height: 2.5px;
            background: var(--forest-deep);
            border-radius: 4px;
            transition: 0.25s ease;
        }

        /* Mobile dropdown */
        .mobile-menu {
            display: none;
            position: absolute;
            top: 72px;
            left: 0;
            right: 0;
            background: rgba(246, 243, 234, 0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            padding: 24px;
            flex-direction: column;
            gap: 18px;
            align-items: center;
        }
        .mobile-menu.open {
            display: flex;
        }
        .mobile-menu a {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--forest-deep);
        }
        .mobile-menu .btn {
            width: 100%;
            justify-content: center;
        }

        /* ── Hero ── */
        /* Dark, textured, data-forward — the opposite of the previous
           centered-logo hero. This is meant to read like a glimpse of
           the actual product: a farm command center, not a brochure. */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 130px 24px 80px;
            background:
                radial-gradient(ellipse 900px 500px at 15% 10%, rgba(215,169,73,0.14), transparent 60%),
                radial-gradient(ellipse 700px 500px at 90% 90%, rgba(40,80,65,0.5), transparent 60%),
                linear-gradient(165deg, var(--forest-deep) 0%, #0a2118 60%, #081b13 100%);
            overflow: hidden;
            color: var(--white);
        }
        /* subtle grain so the dark gradient doesn't look flat/AI-default */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.35;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            max-width: 1180px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 60px;
            align-items: center;
        }

        .hero-eyebrow {
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--amber);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
        }
        .hero-eyebrow::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--amber);
            box-shadow: 0 0 0 4px rgba(215,169,73,0.18);
        }

        .hero h1 {
            font-family: var(--display);
            font-weight: 600;
            font-size: clamp(2.6rem, 4.4vw, 3.9rem);
            letter-spacing: -0.02em;
            line-height: 1.04;
            margin-bottom: 22px;
        }
        .hero h1 .highlight {
            font-style: italic;
            color: var(--amber);
        }

        .hero p {
            font-size: 1.08rem;
            color: rgba(255,255,255,0.72);
            max-width: 520px;
            margin-bottom: 32px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 30px;
        }

        .investment-contact {
            display: inline-block;
            padding: 13px 20px;
            background: rgba(215, 169, 73, 0.08);
            border: 1px solid rgba(215, 169, 73, 0.28);
            border-radius: 12px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.75);
        }
        .investment-contact strong { color: var(--white); }
        .investment-contact a {
            color: var(--amber);
            font-weight: 600;
            text-decoration: underline;
        }

        /* Live metrics panel — the hero's signature element. Real product
           fields (IFCR, mortality, active batches), not decoration. */
        .metrics-panel {
            position: relative;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--line-dark);
            border-radius: 22px;
            padding: 26px;
            backdrop-filter: blur(6px);
        }
        .metrics-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .metrics-panel-head span {
            font-family: var(--mono);
            font-size: 10.5px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
        }
        .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: var(--mono);
            font-size: 10.5px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8fd6a8;
        }
        .live-dot::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #8fd6a8;
            box-shadow: 0 0 0 4px rgba(143,214,168,0.18);
        }
        .metric-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            padding: 14px 0;
            border-top: 1px solid var(--line-dark);
        }
        .metric-row:first-of-type { border-top: none; padding-top: 0; }
        .metric-label {
            font-size: 0.86rem;
            color: rgba(255,255,255,0.6);
        }
        .metric-value {
            font-family: var(--mono);
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--white);
        }
        .metric-value.up { color: #8fd6a8; }
        .metric-value.warn { color: var(--amber); }

        /* ── Sections ── */
        .section {
            padding: 88px 0;
        }
        .section-head {
            max-width: 640px;
            margin-bottom: 36px;
        }
        .section-head h2 {
            font-family: var(--display);
            font-weight: 600;
            font-size: clamp(2rem, 3vw, 2.7rem);
            margin-top: 12px;
            color: var(--forest-deep);
            line-height: 1.1;
        }

        .sectors-grid, .features-grid, .roles-grid {
            display: grid;
            gap: 22px;
        }
        .sectors-grid { grid-template-columns: repeat(3, 1fr); }
        .features-grid { grid-template-columns: repeat(3, 1fr); }
        .roles-grid { grid-template-columns: repeat(4, 1fr); }

        .sector-card {
            background: rgba(255,255,255,0.55);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 26px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sector-card.featured {
            background: linear-gradient(180deg, var(--forest) 0%, var(--forest-deep) 100%);
            border-color: transparent;
            color: var(--white);
        }
        .sector-card.featured h3,
        .sector-card.featured p,
        .sector-card.featured .sector-foot {
            color: var(--white);
        }
        .sector-card.featured p { color: rgba(255,255,255,0.72); }
        .sector-tag {
            display: inline-flex;
            width: fit-content;
            padding: 5px 10px;
            border-radius: 999px;
            font-family: var(--mono);
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: rgba(14, 42, 31, 0.08);
            color: var(--forest);
        }
        .sector-card.featured .sector-tag {
            background: rgba(215,169,73,0.18);
            color: var(--amber);
        }
        .sector-card h3 {
            font-family: var(--display);
            font-weight: 600;
            font-size: 1.7rem;
            margin: 18px 0 10px;
        }
        .sector-foot {
            font-family: var(--mono);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
        }

        .feature-card {
            background: rgba(255,255,255,0.5);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 28px 24px;
        }
        .feature-number {
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--amber-deep);
            margin-bottom: 12px;
        }
        .feature-card h3 {
            font-family: var(--display);
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 10px;
        }
        .feature-card p { color: var(--muted); }

        .team-block {
            background: linear-gradient(180deg, var(--forest-deep) 0%, var(--forest) 100%);
            border-radius: 28px;
            padding: 52px 40px;
            color: var(--white);
        }
        .team-block .section-head h2 { color: var(--white); }
        .team-block .section-head .eyebrow { color: var(--amber); }

        .role-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--line-dark);
            border-radius: 14px;
            padding: 22px;
        }
        .role-card h4 {
            font-family: var(--mono);
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--amber);
            margin-bottom: 12px;
        }
        .role-card p {
            color: rgba(255,255,255,0.72);
            font-size: 0.94rem;
        }

        .cta {
            text-align: center;
            padding: 20px 0 80px;
        }
        .cta-box {
            max-width: 760px;
            margin: 0 auto;
            background: rgba(255,255,255,0.5);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 52px 40px;
        }
        .cta-box h2 {
            font-family: var(--display);
            font-weight: 600;
            font-size: clamp(2rem, 4vw, 3rem);
            margin: 12px 0;
            color: var(--forest-deep);
        }
        .cta-box p { color: var(--muted); max-width: 560px; margin: 0 auto; }
        .cta-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 26px;
        }

        footer {
            border-top: 1px solid var(--line);
            padding: 28px 0 42px;
        }
        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .footer-note {
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .sectors-grid, .features-grid { grid-template-columns: repeat(2, 1fr); }
            .roles-grid { grid-template-columns: repeat(2, 1fr); }
            .hero-grid { grid-template-columns: 1fr; }
            .metrics-panel { max-width: 420px; }
        }

        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .nav-links { display: none; }
            .nav-cta { display: none; } /* hidden on mobile, shown in dropdown */
            .brand { font-size: 1rem; }
            .brand-mark { width: 38px; height: 38px; }

            .hero { padding: 110px 16px 50px; }
            .hero h1 { font-size: 2.3rem; }
            .hero p { font-size: 1rem; }
            .metrics-panel { max-width: 100%; }

            .sectors-grid, .features-grid, .roles-grid { grid-template-columns: 1fr; }
            .team-block { padding: 36px 22px; }
            .cta-box { padding: 36px 22px; }
            .investment-contact { font-size: 0.88rem; padding: 12px 16px; }

            .mobile-menu .btn { font-size: 0.9rem; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 1.95rem; }
            .btn { font-size: 0.85rem; padding: 10px 16px; }
            .brand-mark { width: 32px; height: 32px; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="brand">
                <span class="brand-mark"><img src="/favicon.ico" alt="BWET Farms Logo" /></span>
                <span>BWET Farms</span>
            </div>

            <div class="nav-links">
                <a href="#sectors">Sectors</a>
                <a href="#features">Operations</a>
                <a href="#team">Team</a>
            </div>

            <div class="nav-cta">
                <a href="{{ route('login') }}" class="btn btn-outline">Staff login</a>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
        </nav>

        <!-- Mobile dropdown -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="#sectors">Sectors</a>
            <a href="#features">Operations</a>
            <a href="#team">Team</a>
            <a href="{{ route('login') }}" class="btn btn-outline">Staff login</a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
        </div>
    </header>

    <main>
        <!-- ─── HERO ─── -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-copy">
                    <div class="hero-eyebrow">Farm operations, digitized</div>
                    <h1>Every batch, every bird, <span class="highlight">accounted for</span>.</h1>
                    <p>BWET Farms replaces spreadsheets and paper logs with a live operational system: feed, weight, mortality, cost, and margin — recorded once, calculated automatically, visible to the right role instantly.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">Access dashboard</a>
                        <a href="#sectors" class="btn btn-outline-dark">Explore sectors</a>
                    </div>
                    <div class="investment-contact">
                        <strong>Investment enquiries:</strong> Call <a href="tel:+2347038687630">+234 703 868 7630</a>
                    </div>
                </div>

                <div class="metrics-panel">
                    <div class="metrics-panel-head">
                        <span>Poultry · Batch B0014</span>
                        <span class="live-dot">Live</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Feed conversion (iFCR)</span>
                        <span class="metric-value up">1.82</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Mortality rate</span>
                        <span class="metric-value">1.4%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Avg. weight this week</span>
                        <span class="metric-value up">1.94 kg</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Weight variation (CV)</span>
                        <span class="metric-value warn">11.2%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Active batches</span>
                        <span class="metric-value">5</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── SECTORS ─── -->
        <section class="section" id="sectors">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Farm sectors</span>
                    <h2>Every production line is tracked with purpose.</h2>
                </div>
                <div class="sectors-grid">
                    <article class="sector-card featured">
                        <div>
                            <span class="sector-tag">Active</span>
                            <h3>Poultry</h3>
                            <p>From chick placement to market sale, BWET tracks flock levels, feed usage, weight variation, mortality, and profitability in real time.</p>
                        </div>
                        <div class="sector-foot">Operational dashboards · live reports</div>
                    </article>
                    <article class="sector-card">
                        <div>
                            <span class="sector-tag">In development</span>
                            <h3>Fishery</h3>
                            <p>Analytical tools for pond health, stocking cycle planning, feed conversion, and better yield-based management.</p>
                        </div>
                        <div class="sector-foot">Next sector rollout</div>
                    </article>
                    <article class="sector-card">
                        <div>
                            <span class="sector-tag">Planned</span>
                            <h3>Livestock</h3>
                            <p>Built to support herd movement, feed planning, production tracking, and integrated farm performance reviews.</p>
                        </div>
                        <div class="sector-foot">Expansion roadmap</div>
                    </article>
                </div>
            </div>
        </section>

        <!-- ─── FEATURES ─── -->
        <section class="section" id="features">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Technology in action</span>
                    <h2>Automated record keeping that improves accuracy.</h2>
                    <p style="margin-top: 16px; color: var(--muted);">
                        We are open for investments. Interested persons should call <strong><a href="tel:+2347038687630" style="color: var(--forest-deep); text-decoration: underline;">+234 703 868 7630</a></strong>.
                    </p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-number">01</div>
                        <h3>Batch intelligence</h3>
                        <p>Track arrival, flock health, weight gain, and remaining birds from a single source of truth.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">02</div>
                        <h3>Daily operations</h3>
                        <p>Log feed, mortality, expenses, inventory consumption, and sample weights without spreadsheet friction.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">03</div>
                        <h3>Smart calculations</h3>
                        <p>Automated cost-per-bird, average weight, sample variation, and performance metrics reduce reporting errors.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">04</div>
                        <h3>Inventory visibility</h3>
                        <p>See what is in stock, what is consumed, and how supply movement affects production cost.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">05</div>
                        <h3>Margin forecasting</h3>
                        <p>Use pricing tools and financial summaries to understand break-even and output performance faster.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">06</div>
                        <h3>Export-ready records</h3>
                        <p>Generate structured, report-based exports for operations reviews, management analysis, and team reporting.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── TEAM ─── -->
        <section class="section" id="team">
            <div class="container">
                <div class="team-block">
                    <div class="section-head">
                        <span class="eyebrow">Built for the farm team</span>
                        <h2>Each role sees the data they need to act.</h2>
                    </div>
                    <div class="roles-grid">
                        <div class="role-card">
                            <h4>Admin</h4>
                            <p>Full oversight of farm performance, approvals, and system-wide operational settings.</p>
                        </div>
                        <div class="role-card">
                            <h4>Manager</h4>
                            <p>Operations visibility and operational decisions without unnecessary financial detail.</p>
                        </div>
                        <div class="role-card">
                            <h4>Staff</h4>
                            <p>Fast recording of daily farm data and field-level operational inputs that feed metrics.</p>
                        </div>
                        <div class="role-card">
                            <h4>Finance</h4>
                            <p>Cost summaries, output value, profitability, and investment visibility across batches.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── CTA ─── -->
        <section class="cta">
            <div class="container">
                <div class="cta-box">
                    <span class="eyebrow">Precision farm management</span>
                    <h2>Modern agriculture, built on trusted operational data.</h2>
                    <p>BWET Farms combines farm operations with analytical accuracy so every batch, expense, and decision is supported by clear, timely information.</p>
                    <div class="cta-actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">Open dashboard</a>
                        <a href="{{ route('register') }}" class="btn btn-outline">Create account</a>
                    </div>
                    <div style="margin-top: 20px; font-size: 0.95rem; color: var(--muted);">
                        Investment enquiries: <a href="tel:+2347038687630" style="color: var(--forest-deep); font-weight: 600; text-decoration: underline;">+234 703 868 7630</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-inner">
            <div class="brand">
                <span class="brand-mark"><img src="/favicon.ico" alt="BWET Farms Logo" /></span>
                <span>BWET Farms</span>
            </div>
            <div class="footer-note">Digital operations for modern agriculture</div>
        </div>
    </footer>

    <!-- ─── Mobile menu toggle ─── -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');

            hamburger.addEventListener('click', function() {
                mobileMenu.classList.toggle('open');
            });

            // Close menu when a link is clicked (optional)
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.remove('open');
                });
            });
        });
    </script>
</body>
</html>