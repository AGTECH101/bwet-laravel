<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BWET Farms | Digitalized Farm Operations</title>
    <meta name="description" content="BWET Farms uses digital records, automated calculations, and intelligent reporting to improve farm operations and decision-making across poultry, fishery, and livestock." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style>
        :root {
            --paper: #f4f5ef;
            --panel: #f9f8f3;
            --forest: #1d392c;
            --forest-deep: #132b20;
            --moss: #7e9e67;
            --amber: #d7a948;
            --olive: #dfe8d3;
            --line: rgba(19, 43, 32, 0.12);
            --text: #1e2a25;
            --muted: #57645f;
            --white: #ffffff;
            --shadow: 0 24px 60px rgba(19, 43, 32, 0.12);
            --display: 'Fraunces', serif;
            --body: 'Inter', sans-serif;
            --mono: 'IBM Plex Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--body);
            background: linear-gradient(180deg, #f4f5ef 0%, #ecf0e6 100%);
            color: var(--text);
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--forest);
        }
        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            background: var(--amber);
            border-radius: 50%;
            display: inline-block;
        }

        h1, h2, h3, h4 { font-family: var(--display); color: var(--forest-deep); letter-spacing: -0.03em; }
        p { color: var(--muted); }

        header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(244, 245, 239, 0.88);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }

        nav {
            max-width: 1180px;
            margin: 0 auto;
            padding: 18px 24px;
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
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--forest) 0%, #285041 100%);
            color: var(--white);
            font-family: var(--mono);
            font-size: 12px;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            font-size: 0.96rem;
            font-weight: 500;
            color: var(--forest);
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-weight: 600;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-outline {
            border-color: var(--line);
            color: var(--forest-deep);
            background: rgba(255,255,255,0.2);
        }
        .btn-primary {
            background: var(--forest-deep);
            color: var(--white);
        }
        .btn-gold {
            background: var(--amber);
            color: var(--forest-deep);
        }

        .hero {
            padding: 72px 0 54px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 52px;
            align-items: center;
        }

        .hero h1 {
            font-size: clamp(3rem, 5vw, 5rem);
            line-height: 0.96;
            margin: 18px 0 20px;
        }

        .hero h1 .highlight {
            color: var(--amber);
        }

        .hero-copy {
            max-width: 590px;
            font-size: 1.08rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 30px;
        }

        .hero-panel {
            background: linear-gradient(160deg, #122a21 0%, #1f3f34 100%);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 24px;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(215,169,73,0.22), transparent 40%);
        }

        .hero-panel > * { position: relative; z-index: 1; }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .panel-badge {
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.8;
        }

        .panel-tag {
            font-family: var(--mono);
            font-size: 11px;
            color: #dff4d8;
            background: rgba(255,255,255,0.08);
            padding: 6px 10px;
            border-radius: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 20px;
            margin-top: 8px;
        }

        .stat-label {
            font-family: var(--mono);
            color: rgba(255,255,255,0.72);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .stat-value {
            font-family: var(--mono);
            font-size: clamp(1.3rem, 2vw, 2rem);
            font-weight: 600;
        }

        .progress-wrap {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.14);
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            font-family: var(--mono);
            font-size: 11px;
            opacity: 0.7;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            width: 78%;
            height: 100%;
            background: linear-gradient(90deg, var(--amber) 0%, #e8c26d 100%);
            border-radius: inherit;
        }

        .section {
            padding: 88px 0;
        }

        .section-head {
            max-width: 640px;
            margin-bottom: 34px;
        }
        .section-head h2 {
            font-size: clamp(2.1rem, 3vw, 3rem);
            margin-top: 16px;
            line-height: 1.08;
        }

        .sectors-grid, .features-grid, .roles-grid {
            display: grid;
            gap: 22px;
        }

        .sectors-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .sector-card {
            background: rgba(255,255,255,0.5);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 26px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sector-card.featured {
            background: linear-gradient(180deg, #18392f 0%, #122b22 100%);
            border-color: transparent;
        }

        .sector-card.featured h3, .sector-card.featured p, .sector-card.featured .sector-foot {
            color: var(--white);
        }

        .sector-tag {
            display: inline-flex;
            width: fit-content;
            padding: 5px 10px;
            border-radius: 999px;
            font-family: var(--mono);
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: rgba(19, 43, 32, 0.08);
            color: var(--forest);
        }

        .sector-card.featured .sector-tag {
            background: rgba(215,169,73,0.18);
            color: #f2d38f;
        }

        .sector-card h3 {
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

        .features-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .feature-card {
            background: rgba(255,255,255,0.45);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 28px 24px;
        }

        .feature-number {
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--amber);
            margin-bottom: 12px;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .team-block {
            background: linear-gradient(180deg, #122b22 0%, #173a2d 100%);
            border-radius: 28px;
            padding: 48px 36px;
            color: var(--white);
        }

        .team-block .section-head h2, .team-block .section-head p, .team-block .eyebrow {
            color: var(--white);
        }

        .roles-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 24px;
        }

        .role-card h4 {
            font-family: var(--mono);
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #f2d38f;
            margin-bottom: 12px;
        }

        .role-card p {
            color: rgba(255,255,255,0.78);
        }

        .cta {
            text-align: center;
            padding: 30px 0 90px;
        }

        .cta-box {
            max-width: 760px;
            margin: 0 auto;
            background: rgba(255,255,255,0.45);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 48px 36px;
        }

        .cta-box h2 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            margin-bottom: 12px;
        }

        .cta-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 24px;
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

        @media (max-width: 900px) {
            .hero-grid, .sectors-grid, .features-grid, .roles-grid {
                grid-template-columns: 1fr;
            }
            .nav-links { display: none; }
            .hero { padding-top: 48px; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="brand">
                <span class="brand-mark">BF</span>
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
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <span class="eyebrow">Digital farm operations</span>
                    <h1>Farm intelligence for <span class="highlight">accurate decisions</span>.</h1>
                    <p class="hero-copy">BWET Farms brings technology into every step of the production cycle: track birds, monitor feed and weight, calculate profitability, and automate reporting with a digital system designed for modern farm management.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">Access dashboard</a>
                        <a href="#sectors" class="btn btn-outline">Explore sectors</a>
                    </div>
                </div>

                <div class="hero-panel" aria-label="Digital farm summary card">
                    <div class="panel-header">
                        <span class="panel-badge">Batch summary</span>
                        <span class="panel-tag">B0042</span>
                    </div>

                    <div class="stats-grid">
                        <div>
                            <div class="stat-label">Remaining flock</div>
                            <div class="stat-value">1,284</div>
                        </div>
                        <div>
                            <div class="stat-label">Cost / bird</div>
                            <div class="stat-value">₦3,940</div>
                        </div>
                        <div>
                            <div class="stat-label">FCR</div>
                            <div class="stat-value">1.82</div>
                        </div>
                        <div>
                            <div class="stat-label">Days active</div>
                            <div class="stat-value">31</div>
                        </div>
                    </div>

                    <div class="progress-wrap">
                        <div class="progress-meta">
                            <span>Cost recovered</span>
                            <span>78%</span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill"></div></div>
                    </div>
                </div>
            </div>
        </section>

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

        <section class="section" id="features">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Technology in action</span>
                    <h2>Automated record keeping that improves accuracy.</h2>
                    <p style="margin-top: 16px; color: var(--muted);">We are open for investments. Interested persons should call <strong>+234 703 868 7630</strong>.</p>
                </div>

                <div class="features-grid">
                    <article class="feature-card">
                        <div class="feature-number">01</div>
                        <h3>Batch intelligence</h3>
                        <p>Track arrival, flock health, weight gain, and remaining birds from a single source of truth.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-number">02</div>
                        <h3>Daily operations</h3>
                        <p>Log feed, mortality, expenses, inventory consumption, and sample weights without spreadsheet friction.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-number">03</div>
                        <h3>Smart calculations</h3>
                        <p>Automated cost-per-bird, average weight, sample variation, and performance metrics reduce reporting errors.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-number">04</div>
                        <h3>Inventory visibility</h3>
                        <p>See what is in stock, what is consumed, and how supply movement affects production cost.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-number">05</div>
                        <h3>Margin forecasting</h3>
                        <p>Use pricing tools and financial summaries to understand break-even and output performance faster.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-number">06</div>
                        <h3>Export-ready records</h3>
                        <p>Generate structured, report-based exports for operations reviews, management analysis, and team reporting.</p>
                    </article>
                </div>
            </div>
        </section>

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

        <section class="cta">
            <div class="container">
                <div class="cta-box">
                    <span class="eyebrow">Precision farm management</span>
                    <h2>Modern agriculture, built on trusted operational data.</h2>
                    <p>BWET Farms combines farm operations with analytical accuracy so every batch, expense, and decision is supported by clear, timely information.</p>
                    <div class="cta-actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">Open dashboard</a>
                        <a href="{{ route('register') }}" class="btn btn-gold">Create account</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-inner">
            <div class="brand">
                <span class="brand-mark">BF</span>
                <span>BWET Farms</span>
            </div>
            <div class="footer-note">Digital operations for modern agriculture</div>
        </div>
    </footer>
</body>
</html>
