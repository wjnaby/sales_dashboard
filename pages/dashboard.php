<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pageTitle    = 'Dashboard - Sales Dashboard';
$currentPage  = 'dashboard';
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    '../assets/js/chart-config.js',
];

$pdo = getDBConnection();
$summary = ['total_records' => 0, 'total_sales' => 0, 'total_quantity' => 0];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(DISTINCT id) as total_records,
            COALESCE(SUM(amount), 0) as total_sales,
            COALESCE(SUM(quantity), 0) as total_quantity
            FROM sales");
        $summary = $stmt->fetch();
    } catch (PDOException $e) {
        $summary = ['total_records' => 0, 'total_sales' => 0, 'total_quantity' => 0];
    }
}

$periods       = [];
$productGroups = [];
$products      = [];

if ($pdo) {
    try {
        $periods       = $pdo->query("SELECT DISTINCT period FROM sales ORDER BY period")->fetchAll(PDO::FETCH_COLUMN);
        $productGroups = $pdo->query("SELECT id, name FROM product_groups ORDER BY name")->fetchAll();
        $products      = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
    }
}

include __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    important: '#tw-dashboard',
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
          'fade-up': {
            '0%':   { opacity: '0', transform: 'translateY(16px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          },
          'pulse-dot': {
            '0%, 100%': { opacity: '1' },
            '50%':      { opacity: '0.3' },
          },
        },
        animation: {
          'fade-up':   'fade-up 0.5s ease both',
          'fade-up-1': 'fade-up 0.5s 0.08s ease both',
          'fade-up-2': 'fade-up 0.5s 0.16s ease both',
          'fade-up-3': 'fade-up 0.5s 0.24s ease both',
          'fade-up-4': 'fade-up 0.5s 0.32s ease both',
          'fade-up-5': 'fade-up 0.5s 0.40s ease both',
          'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
        },
        boxShadow: {
          'glow-blue':  '0 0 20px rgba(59,130,246,0.3)',
          'glow-green': '0 0 20px rgba(34,197,94,0.2)',
          card:         '0 4px 24px rgba(10,13,30,0.5)',
        },
      }
    }
  }
</script>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>

<style>
  body { background-color: #0a0d1e !important; }

  #tw-dashboard {
    position: relative;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }
  #tw-dashboard::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none; z-index: 0;
  }
  #tw-dashboard > * { position: relative; z-index: 1; }

  .card-glass {
    background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
  }

  .select-dark {
    background-color: #12152d;
    border: 1px solid rgba(255,255,255,0.08);
    color: #dde0ea;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%238f9ab8' viewBox='0 0 20 20'%3E%3Cpath d='M7 7l3 3 3-3'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .select-dark:focus {
    outline: none;
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
  }
  .select-dark option { background: #12152d; }

  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: #12152d; }
  ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 3px; }
</style>

<div id="tw-dashboard" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
    <div>
      <p class="text-xs font-mono uppercase tracking-[0.2em] text-volt-400 mb-1">Admin Panel</p>
      <h1 class="font-display text-3xl lg:text-4xl font-bold text-white tracking-tight">Sales Overview</h1>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg card-glass text-sm">
        <span class="w-2 h-2 rounded-full bg-volt-400 animate-pulse-dot"></span>
        <span class="text-ink-200 font-medium">Live Data</span>
      </div>
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg card-glass text-sm text-ink-300">
        <svg class="w-4 h-4 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Updated today
      </div>
    </div>
  </header>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    <!-- Records -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden animate-fade-up-1 group">
      <div class="absolute inset-0 bg-gradient-to-br from-azure-600/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-azure-500/5 -translate-y-8 translate-x-8 group-hover:bg-azure-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-azure-500/15 text-azure-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-volt-400 bg-volt-400/10 px-2 py-1 rounded-full">Active</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Total Records</p>
        <p class="font-display text-3xl font-bold text-white" id="statRecords"><?= number_format($summary['total_records']) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-azure-600 to-azure-400" style="width:68%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Healthy data volume</p>
      </div>
    </div>

    <div class="card-glass rounded-2xl p-6 relative overflow-hidden animate-fade-up-2 group">
      <div class="absolute inset-0 bg-gradient-to-br from-volt-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-volt-400/5 -translate-y-8 translate-x-8 group-hover:bg-volt-400/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-volt-500/15 text-volt-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-volt-400 bg-volt-400/10 px-2 py-1 rounded-full">+8.1%</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Total Sales</p>
        <p class="font-display text-3xl font-bold text-white" id="statSales"><?= number_format($summary['total_sales'], 2) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-volt-500 to-volt-400" style="width:82%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">All time revenue</p>
      </div>
    </div>

    <div class="card-glass rounded-2xl p-6 relative overflow-hidden animate-fade-up-3 group">
      <div class="absolute inset-0 bg-gradient-to-br from-violet-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-violet-500/5 -translate-y-8 translate-x-8 group-hover:bg-violet-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-violet-500/15 text-violet-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-amber-400 bg-amber-400/10 px-2 py-1 rounded-full">Units</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Total Quantity</p>
        <p class="font-display text-3xl font-bold text-white" id="statQuantity"><?= number_format($summary['total_quantity']) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-violet-500 to-violet-400" style="width:55%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Units sold across products</p>
      </div>
    </div>

  </div>

  <!-- ══ FILTERS BAR (horizontal, between KPIs and charts) ══ -->
  <div class="card-glass rounded-2xl px-5 py-4 animate-fade-up-4">
    <div class="flex flex-wrap items-end gap-4">

      <div class="shrink-0">
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-3.5 h-3.5 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
          </svg>
          <span class="text-xs font-mono uppercase tracking-widest text-ink-400">Filters</span>
        </div>
        <p class="text-ink-600 text-xs">Applies to all charts</p>
      </div>

      <div class="hidden sm:block w-px self-stretch bg-ink-800 mx-1"></div>

      <div class="flex flex-col gap-1.5 min-w-[130px] flex-1 sm:flex-none">
        <label class="text-xs font-medium text-ink-400 uppercase tracking-widest">Period</label>
        <select id="filterPeriod" class="select-dark rounded-xl px-3 py-2 text-sm">
          <option value="">All Periods</option>
          <?php foreach ($periods as $p): ?>
            <option value="<?= (int)$p ?>"><?= (int)$p ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex flex-col gap-1.5 min-w-[160px] flex-1 sm:flex-none">
        <label class="text-xs font-medium text-ink-400 uppercase tracking-widest">Product Group</label>
        <select id="filterProductGroup" class="select-dark rounded-xl px-3 py-2 text-sm">
          <option value="">All Groups</option>
          <?php foreach ($productGroups as $pg): ?>
            <option value="<?= (int)$pg['id'] ?>"><?= htmlspecialchars($pg['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex flex-col gap-1.5 min-w-[160px] flex-1 sm:flex-none">
        <label class="text-xs font-medium text-ink-400 uppercase tracking-widest">Product</label>
        <select id="filterProduct" class="select-dark rounded-xl px-3 py-2 text-sm">
          <option value="">All Products</option>
          <?php foreach ($products as $prod): ?>
            <option value="<?= (int)$prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="hidden sm:block w-px self-stretch bg-ink-800 mx-1"></div>

      <div class="flex items-center gap-2 shrink-0">
        <button type="button" id="btnApplyFilters"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-azure-600 hover:bg-azure-500 text-white text-sm font-semibold transition-all duration-200 hover:shadow-glow-blue active:scale-[0.98]">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
          Apply
        </button>
        <button type="button" id="btnResetFilters"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-ink-800 hover:bg-ink-700 text-ink-300 hover:text-white text-sm font-medium transition-all duration-150 active:scale-[0.98]">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Reset
        </button>
      </div>

    </div>
  </div>

  <div class="card-glass rounded-2xl p-6 animate-fade-up-5">
    <div class="flex items-center justify-between mb-6">
      <div>
        <p class="font-display text-sm font-bold text-white mb-0.5">Sales by Period &amp; Year</p>
        <p class="text-ink-400 text-xs">Compare performance over time</p>
      </div>
      <span class="hidden sm:flex items-center gap-1.5 text-xs font-mono px-3 py-1.5 rounded-full bg-azure-500/10 text-azure-400 border border-azure-500/20">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        Bar view
      </span>
    </div>
    <div class="relative" style="height:360px;">
      <canvas id="salesBarChart"></canvas>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

    <div class="lg:col-span-2 card-glass rounded-2xl p-6 animate-fade-up">
      <div class="mb-6">
        <p class="font-display text-sm font-bold text-white mb-0.5">Sales by Product Group</p>
        <p class="text-ink-400 text-xs">See which categories drive revenue</p>
      </div>
      <div class="relative" style="height:320px;">
        <canvas id="salesPieChart"></canvas>
      </div>
    </div>

    <div class="lg:col-span-3 card-glass rounded-2xl p-6 animate-fade-up-1">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="font-display text-sm font-bold text-white mb-0.5">Sales Trend</p>
          <p class="text-ink-400 text-xs">Track how sales evolve over the selected period</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-volt-400 animate-pulse-dot"></span>
          <span class="text-xs text-ink-300 font-mono">Live</span>
        </div>
      </div>
      <div class="relative" style="height:320px;">
        <canvas id="salesLineChart"></canvas>
      </div>
    </div>
  </div>

  <!-- ══ QUICK INSIGHTS ══ -->
  <div class="card-glass rounded-xl px-5 py-3 animate-fade-up-2 overflow-x-auto">
    <div class="flex items-center gap-4 min-w-max">

      <p class="font-display text-xs font-bold text-ink-400 uppercase tracking-widest shrink-0">Quick Insights</p>

      <div class="w-px h-4 bg-ink-800 shrink-0"></div>

      <div class="flex items-center gap-2 shrink-0">
        <div class="p-1.5 rounded-lg bg-azure-500/15 text-azure-400">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
        </div>
        <span class="text-white text-xs font-semibold whitespace-nowrap">Top Period</span>
        <span class="text-ink-500 text-xs whitespace-nowrap">Hover the bar chart to see the leading period.</span>
      </div>

      <div class="w-px h-4 bg-ink-800 shrink-0"></div>

      <div class="flex items-center gap-2 shrink-0">
        <div class="p-1.5 rounded-lg bg-volt-500/15 text-volt-400">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
        </div>
        <span class="text-white text-xs font-semibold whitespace-nowrap">Best Group</span>
        <span class="text-ink-500 text-xs whitespace-nowrap">Check the pie chart for top product groups.</span>
      </div>

      <div class="w-px h-4 bg-ink-800 shrink-0"></div>

      <div class="flex items-center gap-2 shrink-0">
        <div class="p-1.5 rounded-lg bg-violet-500/15 text-violet-400">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <span class="text-white text-xs font-semibold whitespace-nowrap">Trend Direction</span>
        <span class="text-ink-500 text-xs whitespace-nowrap">Line chart shows upward or downward momentum.</span>
      </div>

    </div>
  </div>

</div><!-- /#tw-dashboard -->

<script>
Chart.defaults.color       = '#8f9ab8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
Chart.defaults.font.family = '"DM Sans", sans-serif';
Chart.defaults.font.size   = 12;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>