<?php
/**
 * Invoices Page
 * List invoices and show basic totals, linked to products.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pageTitle   = 'Invoices - Sales Dashboard';
$currentPage = 'invoices';

$pdo     = getDBConnection();
$error   = '';
$success = '';

$invoices = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT i.id,
                   i.invoice_number,
                   i.invoice_date,
                   i.customer_name,
                   i.status,
                   COALESCE(SUM(ii.line_total), 0) AS total_amount,
                   COUNT(ii.id) AS items_count
            FROM invoices i
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            GROUP BY i.id, i.invoice_number, i.invoice_date, i.customer_name, i.status
            ORDER BY i.invoice_date DESC, i.id DESC
        ");
        $invoices = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Failed to load invoices.';
    }
}

// Compute summary stats
$totalAmount    = array_sum(array_column($invoices, 'total_amount'));
$paidAmount     = array_sum(array_map(fn($i) => $i['status'] === 'paid'      ? $i['total_amount'] : 0, $invoices));
$pendingAmount  = array_sum(array_map(fn($i) => $i['status'] === 'sent'      ? $i['total_amount'] : 0, $invoices));
$draftCount     = count(array_filter($invoices, fn($i) => $i['status'] === 'draft'));
$paidCount      = count(array_filter($invoices, fn($i) => $i['status'] === 'paid'));
$sentCount      = count(array_filter($invoices, fn($i) => $i['status'] === 'sent'));
$cancelledCount = count(array_filter($invoices, fn($i) => $i['status'] === 'cancelled'));

include __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    important: '#tw-invoices',
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
            '0%':   { opacity: '0', transform: 'translateY(14px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          },
          'pulse-dot': {
            '0%, 100%': { opacity: '1' },
            '50%':      { opacity: '0.3' },
          },
        },
        animation: {
          'fade-up':   'fade-up 0.45s ease both',
          'fade-up-1': 'fade-up 0.45s 0.08s ease both',
          'fade-up-2': 'fade-up 0.45s 0.16s ease both',
          'fade-up-3': 'fade-up 0.45s 0.24s ease both',
          'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
        },
        boxShadow: {
          'glow-blue': '0 0 20px rgba(59,130,246,0.3)',
          card:        '0 4px 24px rgba(10,13,30,0.5)',
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

  #tw-invoices {
    position: relative;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }
  #tw-invoices::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none; z-index: 0;
  }
  #tw-invoices > * { position: relative; z-index: 1; }

  .card-glass {
    background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
  }

  .invoice-row { transition: background 0.15s; }
  .invoice-row:hover { background: rgba(59,130,246,0.05) !important; }

  /* Status pills */
  .status-pill {
    font-size: 0.65rem;
    padding: 2px 9px;
    border-radius: 999px;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    white-space: nowrap;
  }
  .status-paid      { background:rgba(34,197,94,0.15);  color:#4ade80; border:1px solid rgba(34,197,94,0.3); }
  .status-sent      { background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3); }
  .status-draft     { background:rgba(99,111,150,0.2);  color:#8f9ab8; border:1px solid rgba(99,111,150,0.3); }
  .status-cancelled { background:rgba(244,63,94,0.15);  color:#fb7185; border:1px solid rgba(244,63,94,0.3); }

  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: #12152d; }
  ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 3px; }
</style>

<div id="tw-invoices" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- ══ HEADER ══ -->
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
    <div>
      <p class="text-xs font-mono uppercase tracking-[0.2em] text-volt-400 mb-1">Billing</p>
      <h1 class="font-display text-3xl lg:text-4xl font-bold text-white tracking-tight">Invoices</h1>
      <p class="text-ink-300 text-sm mt-1">Overview of issued invoices and amounts.</p>
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

  <!-- ══ ALERTS ══ -->
  <?php if ($error): ?>
  <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl bg-rose-500/10 border border-rose-500/25 text-rose-400 text-sm animate-fade-up" id="alertError">
    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <span class="flex-1"><?= htmlspecialchars($error) ?></span>
    <button onclick="this.parentElement.remove()" class="text-rose-400/60 hover:text-rose-400 transition-colors shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <?php endif; ?>

  <!-- ══ KPI CARDS ══ -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-fade-up-1">

    <!-- Total Invoiced -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-azure-600/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-azure-500/5 -translate-y-6 translate-x-6 group-hover:bg-azure-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-azure-500/15 text-azure-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-azure-400 bg-azure-400/10 px-2 py-1 rounded-full"><?= count($invoices) ?> total</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Total Invoiced</p>
        <p class="font-display text-2xl font-bold text-white"><?= number_format($totalAmount, 2) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-azure-600 to-azure-400" style="width:100%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">All invoices combined</p>
      </div>
    </div>

    <!-- Paid -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-volt-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-volt-400/5 -translate-y-6 translate-x-6 group-hover:bg-volt-400/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-volt-500/15 text-volt-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-volt-400 bg-volt-400/10 px-2 py-1 rounded-full"><?= $paidCount ?> paid</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Paid</p>
        <p class="font-display text-2xl font-bold text-white"><?= number_format($paidAmount, 2) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-volt-500 to-volt-400"
               style="width:<?= $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100) : 0 ?>%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Revenue collected</p>
      </div>
    </div>

    <!-- Outstanding (Sent) -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-amber-500/5 -translate-y-6 translate-x-6 group-hover:bg-amber-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-amber-500/15 text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-amber-400 bg-amber-400/10 px-2 py-1 rounded-full"><?= $sentCount ?> sent</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Outstanding</p>
        <p class="font-display text-2xl font-bold text-white"><?= number_format($pendingAmount, 2) ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-amber-500 to-amber-400"
               style="width:<?= $totalAmount > 0 ? round(($pendingAmount / $totalAmount) * 100) : 0 ?>%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Awaiting payment</p>
      </div>
    </div>

    <!-- Draft / Cancelled -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-violet-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-violet-500/5 -translate-y-6 translate-x-6 group-hover:bg-violet-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-violet-500/15 text-violet-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-violet-400 bg-violet-400/10 px-2 py-1 rounded-full"><?= $draftCount + $cancelledCount ?> other</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Draft / Cancelled</p>
        <p class="font-display text-2xl font-bold text-white"><?= $draftCount ?> / <?= $cancelledCount ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-violet-500 to-violet-400"
               style="width:<?= count($invoices) > 0 ? round((($draftCount + $cancelledCount) / count($invoices)) * 100) : 0 ?>%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Drafts &amp; cancelled</p>
      </div>
    </div>

  </div>

  <!-- ══ TABLE CARD ══ -->
  <div class="card-glass rounded-2xl overflow-hidden animate-fade-up-2">

    <!-- Toolbar -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-ink-800/60">
      <div class="flex items-center gap-3">
        <p class="font-display text-sm font-bold text-white">All Invoices</p>
        <span class="font-mono text-xs px-2.5 py-1 rounded-full bg-ink-800 text-ink-300 border border-ink-700">
          <?= count($invoices) ?> rows
        </span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-volt-400 animate-pulse-dot"></span>
        <span class="text-xs text-ink-400 font-mono">Live</span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-ink-800/60">
            <th class="text-left px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Invoice #</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Date</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Customer</th>
            <th class="text-center px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Items</th>
            <th class="text-right px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Total</th>
            <th class="text-left px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink-800/40">
          <?php if (empty($invoices)): ?>
            <tr>
              <td colspan="6" class="text-center py-16">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-10 h-10 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
                  </svg>
                  <p class="text-ink-400 text-sm">No invoices found.</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($invoices as $inv): ?>
            <tr class="invoice-row">
              <td class="px-6 py-4">
                <span class="font-mono text-xs text-azure-400 font-medium"><?= htmlspecialchars($inv['invoice_number']) ?></span>
              </td>
              <td class="px-4 py-4">
                <span class="font-mono text-xs text-ink-300"><?= htmlspecialchars($inv['invoice_date']) ?></span>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2.5">
                  <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#2563eb,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($inv['customer_name'], 0, 1)) ?>
                  </div>
                  <span class="text-ink-100 font-medium text-sm"><?= htmlspecialchars($inv['customer_name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="font-mono text-xs text-ink-400"><?= (int)$inv['items_count'] ?></span>
              </td>
              <td class="px-4 py-4 text-right">
                <span class="font-mono text-sm text-volt-400 font-medium"><?= number_format((float)$inv['total_amount'], 2) ?></span>
              </td>
              <td class="px-6 py-4">
                <span class="status-pill status-<?= htmlspecialchars($inv['status']) ?>">
                  <?= strtoupper(htmlspecialchars($inv['status'])) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /#tw-invoices -->

<script>
setTimeout(() => {
  document.getElementById('alertError')?.remove();
}, 4000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>