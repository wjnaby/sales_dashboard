<?php
$currentUser = getCurrentUser();
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    important: '#tw-navbar',
    theme: {
      extend: {
        fontFamily: {
          sans:    ['"DM Sans"', 'sans-serif'],
          display: ['"Syne"', 'sans-serif'],
          mono:    ['"JetBrains Mono"', 'monospace'],
        },
        colors: {
          ink: {
            50:  '#f0f1f5', 100: '#dde0ea', 200: '#bcc2d4',
            300: '#8f9ab8', 400: '#636f96', 500: '#485079',
            600: '#3a4066', 700: '#2c3154', 800: '#1e2240',
            900: '#12152d', 950: '#0a0d1e',
          },
          volt:   { 400: '#4ade80', 500: '#22c55e' },
          azure:  { 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb' },
          amber:  { 400: '#fbbf24', 500: '#f59e0b' },
          rose:   { 400: '#fb7185', 500: '#f43f5e' },
          violet: { 400: '#a78bfa', 500: '#8b5cf6' },
        },
        keyframes: {
          'fade-down': {
            '0%':   { opacity: '0', transform: 'translateY(-6px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          },
          'pulse-dot': {
            '0%, 100%': { opacity: '1' },
            '50%':      { opacity: '0.3' },
          },
        },
        animation: {
          'fade-down': 'fade-down 0.2s ease both',
          'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
        },
      }
    }
  }
</script>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>

<style>
  /* ── Navbar base ───────────────────────────────────────── */
  #tw-navbar {
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(180deg, rgba(18,21,45,0.98), rgba(14,17,38,0.98));
    border-bottom: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(16px);
    position: sticky;
    top: 0;
    z-index: 1000;
  }

  /* Subtle top accent line */
  #tw-navbar::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, #2563eb, #22c55e, #8b5cf6);
  }

  /* Nav link base */
  .nav-link-tw {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #8f9ab8;
    text-decoration: none;
    transition: color 0.15s, background 0.15s;
    white-space: nowrap;
    position: relative;
  }
  .nav-link-tw:hover {
    color: #dde0ea;
    background: rgba(255,255,255,0.05);
    text-decoration: none;
  }
  .nav-link-tw.active {
    color: #fff;
    background: rgba(59,130,246,0.15);
  }
  .nav-link-tw.active::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 50%;
    transform: translateX(-50%);
    width: 20px; height: 2px;
    border-radius: 999px;
    background: #3b82f6;
  }

  /* Dropdown */
  .dropdown-tw {
    position: relative;
  }
  .dropdown-tw .dropdown-menu-tw {
    display: none;
    position: absolute;
    right: 0; top: calc(100% + 8px);
    min-width: 180px;
    background: linear-gradient(145deg, rgba(30,34,64,0.98), rgba(18,21,45,0.99));
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(10,13,30,0.6);
    backdrop-filter: blur(16px);
    padding: 6px;
    z-index: 100;
    animation: fade-down 0.2s ease both;
  }
  .dropdown-tw:hover .dropdown-menu-tw,
  .dropdown-tw.open .dropdown-menu-tw {
    display: block;
  }
  .dropdown-item-tw {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #8f9ab8;
    text-decoration: none;
    transition: color 0.15s, background 0.15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
  }
  .dropdown-item-tw:hover {
    color: #fff;
    background: rgba(255,255,255,0.06);
    text-decoration: none;
  }
  .dropdown-item-tw.danger:hover {
    color: #fb7185;
    background: rgba(244,63,94,0.08);
  }

  /* Mobile menu */
  #mobileMenu {
    display: none;
    flex-direction: column;
    gap: 2px;
    padding: 12px 16px 16px;
    border-top: 1px solid rgba(255,255,255,0.06);
  }
  #mobileMenu.open { display: flex; }
  .mobile-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #8f9ab8;
    text-decoration: none;
    transition: color 0.15s, background 0.15s;
  }
  .mobile-nav-link:hover { color: #dde0ea; background: rgba(255,255,255,0.05); text-decoration: none; }
  .mobile-nav-link.active { color: #fff; background: rgba(59,130,246,0.15); }

  /* Hamburger lines */
  .ham-line {
    display: block;
    width: 18px; height: 2px;
    background: #8f9ab8;
    border-radius: 2px;
    transition: all 0.25s;
  }
  #hamburger.open .ham-line:nth-child(1) { transform: translateY(6px) rotate(45deg); }
  #hamburger.open .ham-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
  #hamburger.open .ham-line:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }
</style>

<nav id="tw-navbar">
  <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
    <div style="display:flex;align-items:center;justify-content:space-between;height:60px;">

      <!-- Brand -->
      <a href="dashboard.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;">
        <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" fill="none" stroke="white" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
        </div>
        <span style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#fff;letter-spacing:-0.01em;">
          Sales Dashboard
        </span>
      </a>

      <!-- Desktop nav links -->
      <div style="display:flex;align-items:center;gap:2px;" class="hidden-mobile">
        <a href="dashboard.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Dashboard
        </a>
        <a href="data.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'sales' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"/>
          </svg>
          Sales Data
        </a>
        <a href="invoices.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'invoices' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
          </svg>
          Invoices
        </a>
        <a href="products.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
          Products
        </a>
        <?php if (isAdmin()): ?>
        <a href="product-groups.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'product_groups' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          Product Groups
        </a>
        <a href="admin-users.php"
           class="nav-link-tw <?= ($currentPage ?? '') === 'admin_users' ? 'active' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H6a4 4 0 01-4-4v-1h5m3-9a4 4 0 110 8 4 4 0 010-8z"/>
          </svg>
          Users
        </a>
        <?php endif; ?>
      </div>

      <!-- Right side: user dropdown + hamburger -->
      <div style="display:flex;align-items:center;gap:10px;">

        <!-- User dropdown (desktop) -->
        <div class="dropdown-tw hidden-mobile">
          <button style="display:flex;align-items:center;gap:8px;padding:6px 12px 6px 6px;border-radius:10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);cursor:pointer;transition:all 0.15s;color:#dde0ea;font-family:'DM Sans',sans-serif;font-size:0.875rem;font-weight:500;"
            onmouseover="this.style.background='rgba(255,255,255,0.08)'"
            onmouseout="this.style.background='rgba(255,255,255,0.04)'"
            onclick="this.closest('.dropdown-tw').classList.toggle('open')">
            <!-- Avatar -->
            <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#2563eb,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#fff;flex-shrink:0;">
              <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
            </div>
            <span style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= htmlspecialchars($currentUser['username'] ?? 'User') ?>
            </span>
            <?php if (isAdmin()): ?>
              <span style="font-size:0.6rem;font-weight:600;padding:2px 6px;border-radius:999px;background:rgba(245,158,11,0.2);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);letter-spacing:0.05em;text-transform:uppercase;font-family:'JetBrains Mono',monospace;">
                Admin
              </span>
            <?php endif; ?>
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#636f96;flex-shrink:0;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <div class="dropdown-menu-tw">
            <!-- User info header -->
            <div style="padding:10px 12px 8px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:4px;">
              <p style="font-size:0.8rem;font-weight:600;color:#dde0ea;margin:0;">
                <?= htmlspecialchars($currentUser['username'] ?? 'User') ?>
              </p>
              <p style="font-size:0.7rem;color:#636f96;margin:2px 0 0;font-family:'JetBrains Mono',monospace;">
                <?= isAdmin() ? 'Administrator' : 'Standard User' ?>
              </p>
            </div>
            <a href="logout.php" class="dropdown-item-tw danger">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
              Logout
            </a>
          </div>
        </div>

        <!-- Hamburger (mobile) -->
        <button id="hamburger"
          style="display:none;flex-direction:column;gap:4px;padding:8px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);cursor:pointer;transition:background 0.15s;"
          onclick="toggleMobileMenu()"
          onmouseover="this.style.background='rgba(255,255,255,0.08)'"
          onmouseout="this.style.background='rgba(255,255,255,0.04)'"
          aria-label="Toggle menu">
          <span class="ham-line"></span>
          <span class="ham-line"></span>
          <span class="ham-line"></span>
        </button>

      </div>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobileMenu">
    <a href="dashboard.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      Dashboard
    </a>
    <a href="data.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'sales' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"/>
      </svg>
      Sales Data
    </a>
    <a href="invoices.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'invoices' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
      </svg>
      Invoices
    </a>
    <a href="products.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      Products
    </a>
    <?php if (isAdmin()): ?>
    <a href="product-groups.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'product_groups' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
      Product Groups
    </a>
    <a href="admin-users.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'admin_users' ? 'active' : '' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H6a4 4 0 01-4-4v-1h5m3-9a4 4 0 110 8 4 4 0 010-8z"/>
      </svg>
      Users
    </a>
    <?php endif; ?>

    <!-- Mobile user section -->
    <div style="margin-top:8px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.06);">
      <div style="display:flex;align-items:center;gap:10px;padding:8px 14px;margin-bottom:4px;">
        <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;">
          <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div>
          <p style="font-size:0.875rem;font-weight:600;color:#dde0ea;margin:0;">
            <?= htmlspecialchars($currentUser['username'] ?? 'User') ?>
          </p>
          <p style="font-size:0.7rem;color:#636f96;margin:0;font-family:'JetBrains Mono',monospace;">
            <?= isAdmin() ? 'Administrator' : 'Standard User' ?>
          </p>
        </div>
        <?php if (isAdmin()): ?>
          <span style="margin-left:auto;font-size:0.6rem;font-weight:600;padding:2px 7px;border-radius:999px;background:rgba(245,158,11,0.2);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);font-family:'JetBrains Mono',monospace;text-transform:uppercase;">
            Admin
          </span>
        <?php endif; ?>
      </div>
      <a href="logout.php" class="mobile-nav-link" style="color:#fb7185;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Logout
      </a>
    </div>
  </div>
</nav>

<style>
  /* Responsive: hide desktop links on mobile, show hamburger */
  @media (max-width: 768px) {
    .hidden-mobile { display: none !important; }
    #hamburger     { display: flex !important; }
  }
</style>

<script>
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn  = document.getElementById('hamburger');
    menu.classList.toggle('open');
    btn.classList.toggle('open');
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    document.querySelectorAll('.dropdown-tw.open').forEach(function(d) {
      if (!d.contains(e.target)) d.classList.remove('open');
    });
  });
</script>