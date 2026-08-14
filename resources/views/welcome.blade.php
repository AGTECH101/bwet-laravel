<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BWET Farms — Farm operations, tracked to the last bird</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --paper:#EDEFE1;
    --paper-dim:#E3E6D4;
    --ink:#182417;
    --forest:#26402C;
    --forest-deep:#1B2E1F;
    --moss:#6B8F71;
    --amber:#D9A03C;
    --amber-deep:#B9822A;
    --teal:#3E6B63;
    --line: rgba(24,36,23,0.14);
    --display: 'Fraunces', serif;
    --body: 'Inter', sans-serif;
    --mono: 'IBM Plex Mono', monospace;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html{scroll-behavior:smooth;}
  body{
    background:var(--paper);
    color:var(--ink);
    font-family:var(--body);
    line-height:1.5;
    -webkit-font-smoothing:antialiased;
  }
  a{color:inherit; text-decoration:none;}
  img{max-width:100%; display:block;}
  .wrap{max-width:1180px; margin:0 auto; padding:0 28px;}
  .eyebrow{
    font-family:var(--mono);
    font-size:12.5px;
    letter-spacing:0.14em;
    text-transform:uppercase;
    color:var(--forest);
    display:inline-flex;
    align-items:center;
    gap:10px;
  }
  .eyebrow::before{
    content:"";
    width:7px; height:7px; border-radius:50%;
    background:var(--amber);
    display:inline-block;
  }
  h1,h2,h3{font-family:var(--display); font-weight:600; color:var(--forest-deep); letter-spacing:-0.01em;}
  :focus-visible{outline:2px solid var(--teal); outline-offset:3px;}

  /* ---------- NAV ---------- */
  header{
    position:sticky; top:0; z-index:50;
    background:rgba(237,239,225,0.88);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
  }
  nav{
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 28px; max-width:1180px; margin:0 auto;
  }
  .brand{
    font-family:var(--display); font-weight:700; font-size:20px;
    display:flex; align-items:center; gap:10px; color:var(--forest-deep);
  }
  .brand .mark{
    width:30px; height:30px; border-radius:7px;
    background:linear-gradient(155deg, var(--forest) 0%, var(--teal) 100%);
    display:flex; align-items:center; justify-content:center;
    color:var(--paper); font-family:var(--mono); font-size:13px; font-weight:600;
  }
  .nav-links{display:flex; align-items:center; gap:32px; font-size:14.5px; font-weight:500;}
  .nav-links a{color:var(--forest); opacity:0.82; transition:opacity .15s;}
  .nav-links a:hover{opacity:1;}
  .nav-cta{display:flex; align-items:center; gap:14px;}
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    font-family:var(--body); font-weight:600; font-size:14.5px;
    padding:10px 20px; border-radius:8px;
    cursor:pointer; border:1px solid transparent; transition:transform .15s, background .15s, border-color .15s;
  }
  .btn:hover{transform:translateY(-1px);}
  .btn-ghost{color:var(--forest-deep); border-color:var(--line);}
  .btn-ghost:hover{border-color:var(--forest);}
  .btn-solid{background:var(--forest-deep); color:var(--paper);}
  .btn-solid:hover{background:var(--forest);}
  .menu-toggle{display:none; background:none; border:none; cursor:pointer; padding:8px;}
  .menu-toggle span{display:block; width:22px; height:2px; background:var(--forest-deep); margin:5px 0;}

  /* ---------- HERO ---------- */
  .hero{padding:76px 0 40px;}
  .hero-grid{
    display:grid; grid-template-columns:1.05fr 0.95fr; gap:56px; align-items:center;
  }
  .hero h1{
    font-size:clamp(36px, 4.6vw, 58px); line-height:1.06; margin:18px 0 22px;
  }
  .hero h1 em{font-style:normal; color:var(--amber-deep);}
  .hero p.lede{
    font-size:17.5px; color:rgba(24,36,23,0.72); max-width:480px; margin-bottom:32px;
  }
  .hero-actions{display:flex; gap:14px; flex-wrap:wrap;}
  .btn-lg{padding:13px 26px; font-size:15px;}

  /* signature: live batch ledger card */
  .ledger-card{
    background:var(--forest-deep);
    border-radius:16px;
    padding:26px 26px 20px;
    color:var(--paper);
    box-shadow:0 30px 60px -22px rgba(27,46,31,0.45);
    position:relative;
    overflow:hidden;
  }
  .ledger-card::before{
    content:"";
    position:absolute; inset:0;
    background:radial-gradient(circle at 88% -10%, rgba(217,160,60,0.22), transparent 55%);
    pointer-events:none;
  }
  .ledger-top{display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;}
  .ledger-label{font-family:var(--mono); font-size:12px; letter-spacing:0.1em; text-transform:uppercase; opacity:0.6;}
  .ledger-id{font-family:var(--mono); font-size:13px; background:rgba(255,255,255,0.08); padding:5px 10px; border-radius:6px;}
  .ledger-status{display:flex; align-items:center; gap:7px; font-family:var(--mono); font-size:12px; color:#A7D6A0;}
  .ledger-status .dot{width:6px; height:6px; border-radius:50%; background:#8FCB86;}
  .ledger-rows{display:grid; grid-template-columns:1fr 1fr; gap:18px 22px; margin-bottom:22px;}
  .ledger-rows .full{grid-column:1 / -1;}
  .ledger-rows .metric-label{font-size:12px; opacity:0.58; margin-bottom:5px;}
  .ledger-rows .metric-value{font-family:var(--mono); font-size:21px; font-weight:500;}
  .ledger-rows .metric-value small{font-size:12px; opacity:0.5; font-weight:400; margin-left:2px;}
  .ledger-rows .metric-value.up{color:#8FCB86;}
  .ledger-bar-track{height:6px; border-radius:4px; background:rgba(255,255,255,0.1); overflow:hidden; margin-top:6px;}
  .ledger-bar-fill{height:100%; background:linear-gradient(90deg, var(--amber), #E9C273); border-radius:4px; width:64%;}
  .ledger-foot{
    display:flex; justify-content:space-between; align-items:center;
    padding-top:16px; border-top:1px solid rgba(255,255,255,0.1);
    font-family:var(--mono); font-size:11.5px; opacity:0.55;
  }
  @media (prefers-reduced-motion: no-preference){
    .metric-value.pulse{animation:pulseVal 2.4s ease-in-out infinite;}
  }
  @keyframes pulseVal{0%,100%{opacity:1;} 50%{opacity:0.72;}}

  /* ---------- SECTORS ---------- */
  .section{padding:88px 0;}
  .section-head{max-width:600px; margin-bottom:48px;}
  .section-head h2{font-size:clamp(28px,3vw,38px); margin:14px 0 14px;}
  .section-head p{color:rgba(24,36,23,0.68); font-size:16px;}

  .sector-grid{display:grid; grid-template-columns:repeat(3, 1fr); gap:20px;}
  .sector-card{
    background:var(--paper-dim); border:1px solid var(--line); border-radius:14px;
    padding:26px; position:relative; min-height:220px; display:flex; flex-direction:column;
  }
  .sector-card.live{background:var(--forest-deep); color:var(--paper); border-color:var(--forest-deep);}
  .sector-tag{
    font-family:var(--mono); font-size:11px; letter-spacing:0.08em; text-transform:uppercase;
    padding:4px 10px; border-radius:20px; display:inline-block; width:fit-content; margin-bottom:16px;
  }
  .sector-card.live .sector-tag{background:rgba(217,160,60,0.2); color:var(--amber);}
  .sector-card:not(.live) .sector-tag{background:rgba(24,36,23,0.08); color:var(--forest);}
  .sector-card h3{font-size:21px; margin-bottom:8px;}
  .sector-card:not(.live) h3{color:var(--forest-deep);}
  .sector-card p{font-size:14px; opacity:0.75; flex-grow:1;}
  .sector-card .sector-foot{font-family:var(--mono); font-size:12px; opacity:0.55; margin-top:18px;}

  /* ---------- FEATURES ---------- */
  .feature-grid{display:grid; grid-template-columns:repeat(3, 1fr); gap:2px; background:var(--line); border:1px solid var(--line); border-radius:14px; overflow:hidden;}
  .feature-card{background:var(--paper); padding:30px 26px;}
  .feature-num{font-family:var(--mono); font-size:12px; color:var(--amber-deep); margin-bottom:14px;}
  .feature-card h3{font-size:17.5px; margin-bottom:8px; font-weight:600;}
  .feature-card p{font-size:14px; color:rgba(24,36,23,0.68);}

  /* ---------- ROLES ---------- */
  .role-strip{
    background:var(--forest-deep); border-radius:20px; padding:52px 44px; color:var(--paper);
  }
  .role-strip .section-head p{color:rgba(237,239,225,0.68);}
  .role-strip .section-head h2{color:var(--paper);}
  .role-grid{display:grid; grid-template-columns:repeat(4, 1fr); gap:24px; margin-top:8px;}
  .role-card h4{font-family:var(--mono); font-size:12.5px; text-transform:uppercase; letter-spacing:0.08em; color:var(--amber); margin-bottom:10px;}
  .role-card p{font-size:14px; opacity:0.78;}

  /* ---------- CTA ---------- */
  .cta-band{
    text-align:center; padding:90px 0 100px;
  }
  .cta-band h2{font-size:clamp(30px,4vw,44px); margin-bottom:16px;}
  .cta-band p{color:rgba(24,36,23,0.68); font-size:16.5px; max-width:480px; margin:0 auto 32px;}
  .cta-actions{display:flex; justify-content:center; gap:14px;}

  footer{
    border-top:1px solid var(--line); padding:36px 0; 
  }
  .foot-grid{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;}
  .foot-grid .brand{font-size:16px;}
  footer .foot-note{font-family:var(--mono); font-size:12px; color:rgba(24,36,23,0.5);}

  @media (max-width: 880px){
    .hero-grid{grid-template-columns:1fr;}
    .nav-links, .nav-cta .btn-ghost{display:none;}
    .menu-toggle{display:block;}
    .sector-grid, .feature-grid, .role-grid{grid-template-columns:1fr;}
    .role-strip{padding:36px 24px;}
  }
</style>
</head>
<body>

<header>
  <nav>
    <div class="brand"><span class="mark">BF</span>BWET Farms</div>
    <div class="nav-links">
      <a href="#sectors">Sectors</a>
      <a href="#features">Platform</a>
      <a href="#roles">Roles</a>
    </div>
    <div class="nav-cta">
      <a href="#" class="btn btn-ghost">Log in</a>
      <a href="#" class="btn btn-solid">Register</a>
    </div>
    <button class="menu-toggle" aria-label="Menu"><span></span><span></span><span></span></button>
  </nav>
</header>

<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow">Poultry sector — live now</span>
      <h1>Farm operations, tracked to the <em>last bird.</em></h1>
      <p class="lede">BWET Farms turns feed logs, weighings, and mortality counts into a live cost-per-bird figure — so you always know what a batch is worth before it leaves the pen. Built sector by sector, starting with poultry.</p>
      <div class="hero-actions">
        <a href="#" class="btn btn-solid btn-lg">Register your farm</a>
        <a href="#features" class="btn btn-ghost btn-lg">See what it tracks</a>
      </div>
    </div>

    <div class="ledger-card" role="img" aria-label="Live batch summary card showing remaining flock, cost per bird, feed conversion ratio, and cost recovery progress">
      <div class="ledger-top">
        <span class="ledger-label">Batch summary</span>
        <span class="ledger-id">B0042</span>
      </div>
      <div class="ledger-rows">
        <div>
          <div class="metric-label">Remaining flock</div>
          <div class="metric-value pulse">1,284 <small>birds</small></div>
        </div>
        <div>
          <div class="metric-label">Cost / bird</div>
          <div class="metric-value pulse">₦3,940</div>
        </div>
        <div>
          <div class="metric-label">Feed conversion</div>
          <div class="metric-value">1.82 <small>FCR</small></div>
        </div>
        <div>
          <div class="metric-label">Days active</div>
          <div class="metric-value">31 <small>/ 42</small></div>
        </div>
        <div class="full">
          <div class="metric-label">Cost recovered at market</div>
          <div class="ledger-bar-track"><div class="ledger-bar-fill"></div></div>
        </div>
      </div>
      <div class="ledger-foot">
        <span>Updated on last weighing</span>
        <span>Sector: Poultry</span>
      </div>
    </div>
  </div>
</section>

<section class="section" id="sectors">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">One farm, many ledgers</span>
      <h2>Every sector runs on its own books.</h2>
      <p>Poultry, fishery, and livestock don't share a cost structure — so they don't share code. Each sector gets its own metrics, forms, and dashboards, while the farm's overall performance rolls up in one place.</p>
    </div>
    <div class="sector-grid">
      <div class="sector-card live">
        <span class="sector-tag">Active</span>
        <h3>Poultry</h3>
        <p>Batch tracking, feed &amp; weight logging, mortality, inventory consumption, and cost-plus pricing — fully live.</p>
        <div class="sector-foot">Started tracking · in production</div>
      </div>
      <div class="sector-card">
        <span class="sector-tag">In development</span>
        <h3>Fishery</h3>
        <p>Pond cycles, stocking density, and feed-to-yield tracking built for aquaculture's own rhythm.</p>
        <div class="sector-foot">Coming to your dashboard next</div>
      </div>
      <div class="sector-card">
        <span class="sector-tag">Planned</span>
        <h3>Goat &amp; livestock</h3>
        <p>Herd records, breeding cycles, and grazing costs — scoped once fishery is settled.</p>
        <div class="sector-foot">On the roadmap</div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="features">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">The poultry ledger</span>
      <h2>What it tracks, from chick to sale.</h2>
      <p>No spreadsheets, no guesswork on what a batch actually costs to raise.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-num">01</div>
        <h3>Batches &amp; pens</h3>
        <p>Every batch gets an ID, a pen assignment, and a running record from arrival to sale.</p>
      </div>
      <div class="feature-card">
        <div class="feature-num">02</div>
        <h3>Feed &amp; weight logs</h3>
        <p>Daily entries build a live growth curve and feed conversion ratio, no manual math required.</p>
      </div>
      <div class="feature-card">
        <div class="feature-num">03</div>
        <h3>Mortality &amp; flock counts</h3>
        <p>Losses and culls update the remaining flock and every downstream figure automatically.</p>
      </div>
      <div class="feature-card">
        <div class="feature-num">04</div>
        <h3>Inventory &amp; consumption</h3>
        <p>Feed and supplies drawn against stock update cost per bird the moment they're logged.</p>
      </div>
      <div class="feature-card">
        <div class="feature-num">05</div>
        <h3>Price calculator</h3>
        <p>Set a target margin and get a selling price that never dips below break-even.</p>
      </div>
      <div class="feature-card">
        <div class="feature-num">06</div>
        <h3>Alerts &amp; triggers</h3>
        <p>Flags a batch when profit tolerance, FCR, or weight trends slip outside your set limits.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="roles">
  <div class="wrap">
    <div class="role-strip">
      <div class="section-head">
        <span class="eyebrow" style="color:var(--amber);">One login, the right view</span>
        <h2>Built for every role on the farm.</h2>
        <p>Everyone sees exactly what their job needs — nothing they don't.</p>
      </div>
      <div class="role-grid">
        <div class="role-card">
          <h4>Admin</h4>
          <p>Full financials, sector overview, user approvals, and system configuration.</p>
        </div>
        <div class="role-card">
          <h4>Manager</h4>
          <p>Operations at a glance — batches, inventory, and observations, without cost figures.</p>
        </div>
        <div class="role-card">
          <h4>Staff</h4>
          <p>Fast form entry for daily logs, plus the price calculator when it's needed.</p>
        </div>
        <div class="role-card">
          <h4>Investor</h4>
          <p>A read-only view of their own stake and its return, nothing more.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2>Start with the sector you run today.</h2>
    <p>Poultry is live. Register your farm and start logging batches this week.</p>
    <div class="cta-actions">
      <a href="#" class="btn btn-solid btn-lg">Register your farm</a>
      <a href="#" class="btn btn-ghost btn-lg">Log in</a>
    </div>
  </div>
</section>

<footer>
  <div class="wrap foot-grid">
    <div class="brand"><span class="mark">BF</span>BWET Farms</div>
    <span class="foot-note">BWET Farms · Poultry sector, built for what's next</span>
  </div>
</footer>

<script>
  // Mobile menu toggle
  const toggle = document.querySelector('.menu-toggle');
  const links = document.querySelector('.nav-links');
  toggle?.addEventListener('click', () => {
    links.style.display = links.style.display === 'flex' ? 'none' : 'flex';
    links.style.cssText += 'position:absolute; top:64px; left:0; right:0; flex-direction:column; background:var(--paper); padding:20px 28px; border-bottom:1px solid var(--line);';
  });
</script>

</body>
</html>