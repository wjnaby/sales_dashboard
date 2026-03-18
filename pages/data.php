<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pageTitle = 'Sales Data - Sales Dashboard';
$currentPage = 'sales';

$pdo = getDBConnection();
$error = '';
$success = '';
$editId = null;

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Record deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete record.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $product_id = (int)($_POST['product_id'] ?? 0);
    $period = (int)($_POST['period'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($product_id <= 0 || $period <= 0 || $year <= 0) {
        $error = 'Product, period and year are required.';
        $editId = $id ?: null;
    } elseif ($amount < 0 || $quantity < 0) {
        $error = 'Amount and quantity must be non-negative.';
        $editId = $id ?: null;
    } elseif (!$pdo) {
        $error = 'Database connection failed.';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE sales SET product_id=?, period=?, year=?, amount=?, quantity=? WHERE id=?");
                $stmt->execute([$product_id, $period, $year, $amount, $quantity, $id]);
                $success = 'Record updated successfully.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO sales (product_id, period, year, amount, quantity) VALUES (?,?,?,?,?)");
                $stmt->execute([$product_id, $period, $year, $amount, $quantity]);
                $success = 'Record added successfully.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'A record for this product/period/year already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
            $editId = $id ?: null;
        }
    }
}

$products = [];
$sales = [];
$editRow = null;

if ($pdo) {
    try {
        $products = $pdo->query("SELECT p.id, p.name, pg.name as group_name FROM products p JOIN product_groups pg ON p.product_group_id = pg.id ORDER BY pg.name, p.name")->fetchAll();

        $stmt = $pdo->query("
            SELECT s.id, s.period, s.year, s.amount, s.quantity, p.name as product_name, pg.name as group_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            JOIN product_groups pg ON p.product_group_id = pg.id
            ORDER BY s.year DESC, s.period DESC, p.name
        ");
        $sales = $stmt->fetchAll();

        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $editId = (int)$_GET['edit'];
            $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->execute([$editId]);
            $editRow = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error = $error ?: 'Failed to load data.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    important: '#tw-sales',
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

  #tw-sales {
    position: relative;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }
  #tw-sales::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none; z-index: 0;
  }
  #tw-sales > * { position: relative; z-index: 1; }

  .card-glass {
    background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
  }

  .sales-row { transition: background 0.15s; }
  .sales-row:hover { background: rgba(59,130,246,0.05) !important; }

  .input-dark, .select-dark {
    background-color: #12152d;
    border: 1px solid rgba(255,255,255,0.08);
    color: #dde0ea;
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    width: 100%;
  }
  .input-dark::placeholder { color: #636f96; }
  .input-dark:focus, .select-dark:focus {
    outline: none;
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    background-color: #0f1228;
  }
  .select-dark {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%238f9ab8' viewBox='0 0 20 20'%3E%3Cpath d='M7 7l3 3 3-3'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
  }
  .select-dark option { background: #12152d; }

  .group-badge {
    font-size: 0.68rem;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(139,92,246,0.15);
    color: #a78bfa;
    border: 1px solid rgba(139,92,246,0.2);
    white-space: nowrap;
  }

  #modalOverlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(10,13,30,0.85);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #modalOverlay.is-open {
    display: flex;
  }
  @keyframes slide-in {
    0%   { opacity: 0; transform: scale(0.97) translateY(10px); }
    100% { opacity: 1; transform: scale(1)    translateY(0);    }
  }
  #modalPanel {
    background: linear-gradient(145deg, rgba(30,34,64,0.97), rgba(18,21,45,0.99));
    border: 1px solid rgba(255,255,255,0.07);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 8px 40px rgba(10,13,30,0.7);
    animation: slide-in 0.28s ease both;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }

  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: #12152d; }
  ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 3px; }
</style>

<div id="tw-sales" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
    <div>
      <p class="text-xs font-mono uppercase tracking-[0.2em] text-volt-400 mb-1">Records</p>
      <h1 class="font-display text-3xl lg:text-4xl font-bold text-white tracking-tight">Sales Data</h1>
      <p class="text-ink-300 text-sm mt-1">Add, edit and manage all sales records.</p>
    </div>
    <button type="button" onclick="openAddModal()"
      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-azure-600 hover:bg-azure-500 text-white text-sm font-semibold transition-all duration-200 hover:shadow-glow-blue active:scale-[0.98] shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
      </svg>
      Add Sale
    </button>
  </header>

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

  <?php if ($success): ?>
  <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl bg-volt-500/10 border border-volt-500/25 text-volt-400 text-sm animate-fade-up" id="alertSuccess">
    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span class="flex-1"><?= htmlspecialchars($success) ?></span>
    <button onclick="this.parentElement.remove()" class="text-volt-400/60 hover:text-volt-400 transition-colors shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <?php endif; ?>

  <div class="card-glass rounded-2xl overflow-hidden animate-fade-up-1">

    <div class="flex items-center justify-between px-6 py-4 border-b border-ink-800/60">
      <div class="flex items-center gap-3">
        <p class="font-display text-sm font-bold text-white">All Records</p>
        <span class="font-mono text-xs px-2.5 py-1 rounded-full bg-ink-800 text-ink-300 border border-ink-700">
          <?= count($sales) ?> rows
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
            <th class="text-left px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium w-16">#ID</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Product</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Group</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Period</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Year</th>
            <th class="text-right px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Amount</th>
            <th class="text-right px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Qty</th>
            <th class="text-center px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium w-28">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink-800/40">
          <?php if (empty($sales)): ?>
            <tr>
              <td colspan="8" class="text-center py-16">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-10 h-10 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  <p class="text-ink-400 text-sm">No sales data found.</p>
                  <button type="button" onclick="openAddModal()" class="text-xs text-azure-400 hover:text-azure-300 underline underline-offset-2 transition-colors">
                    Add your first record
                  </button>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sales as $row): ?>
            <tr class="sales-row">
              <td class="px-6 py-4"><span class="font-mono text-xs text-ink-500">#<?= (int)$row['id'] ?></span></td>
              <td class="px-4 py-4"><span class="text-ink-100 font-medium"><?= htmlspecialchars($row['product_name']) ?></span></td>
              <td class="px-4 py-4"><span class="group-badge"><?= htmlspecialchars($row['group_name']) ?></span></td>
              <td class="px-4 py-4"><span class="font-mono text-ink-300"><?= (int)$row['period'] ?></span></td>
              <td class="px-4 py-4"><span class="font-mono text-ink-300"><?= (int)$row['year'] ?></span></td>
              <td class="px-4 py-4 text-right"><span class="font-mono text-volt-400 font-medium"><?= number_format((float)$row['amount'], 2) ?></span></td>
              <td class="px-4 py-4 text-right"><span class="font-mono text-azure-400"><?= number_format((int)$row['quantity']) ?></span></td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <a href="?edit=<?= (int)$row['id'] ?>"
                     class="p-1.5 rounded-lg bg-azure-500/10 text-azure-400 hover:bg-azure-500/20 hover:text-azure-300 transition-all duration-150" title="Edit">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                  </a>
                  <a href="?delete=<?= (int)$row['id'] ?>"
                     onclick="return confirm('Delete this record?')"
                     class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-all duration-150" title="Delete">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<div id="modalOverlay">
  <div id="modalPanel">
    <form method="POST" action="">
      <input type="hidden" name="id" id="formId" value=""/>

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,0.06);">
        <div>
          <p id="modalTitle" style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;margin:0;">Add Sale</p>
          <p style="color:#636f96;font-size:0.72rem;margin:3px 0 0;">Fill in the details below</p>
        </div>
        <button type="button" onclick="closeModal()"
          style="padding:7px;border-radius:9px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);color:#636f96;cursor:pointer;line-height:0;transition:all 0.15s;"
          onmouseover="this.style.color='#fff';this.style.background='rgba(255,255,255,0.08)'"
          onmouseout="this.style.color='#636f96';this.style.background='rgba(255,255,255,0.04)'">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;">

        <!-- Product -->
        <div>
          <label for="product_id" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
            Product <span style="color:#fb7185;">*</span>
          </label>
          <select name="product_id" id="product_id" class="select-dark" required
            style="border-radius:11px;padding:10px 36px 10px 13px;font-size:0.875rem;">
            <option value="">Select product…</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= (int)$p['id'] ?>">
                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['group_name']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Period + Year -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div>
            <label for="period" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
              Period <span style="color:#fb7185;">*</span>
            </label>
            <input type="number" name="period" id="period" class="input-dark"
              style="border-radius:11px;padding:10px 13px;font-size:0.875rem;font-family:'JetBrains Mono',monospace;"
              min="1" max="12" placeholder="1 – 12" required/>
          </div>
          <div>
            <label for="year" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
              Year <span style="color:#fb7185;">*</span>
            </label>
            <input type="number" name="year" id="year" class="input-dark"
              style="border-radius:11px;padding:10px 13px;font-size:0.875rem;font-family:'JetBrains Mono',monospace;"
              min="2020" max="2030" placeholder="2024" required/>
          </div>
        </div>

        <!-- Amount + Quantity -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div>
            <label for="amount" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">Amount</label>
            <div style="position:relative;">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#636f96;font-size:0.875rem;font-family:'JetBrains Mono',monospace;pointer-events:none;">$</span>
              <input type="number" name="amount" id="amount" class="input-dark"
                style="border-radius:11px;padding:10px 13px 10px 27px;font-size:0.875rem;font-family:'JetBrains Mono',monospace;"
                step="0.01" min="0" value="0"/>
            </div>
          </div>
          <div>
            <label for="quantity" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="input-dark"
              style="border-radius:11px;padding:10px 13px;font-size:0.875rem;font-family:'JetBrains Mono',monospace;"
              min="0" value="0"/>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid rgba(255,255,255,0.06);">
        <button type="button" onclick="closeModal()"
          style="padding:9px 18px;border-radius:10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);color:#8f9ab8;font-size:0.875rem;font-weight:500;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.15s;"
          onmouseover="this.style.color='#fff';this.style.background='rgba(255,255,255,0.08)'"
          onmouseout="this.style.color='#8f9ab8';this.style.background='rgba(255,255,255,0.04)'">
          Cancel
        </button>
        <button type="submit"
          style="padding:9px 18px;border-radius:10px;background:#2563eb;border:none;color:#fff;font-size:0.875rem;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;"
          onmouseover="this.style.background='#3b82f6';this.style.boxShadow='0 0 20px rgba(59,130,246,0.3)'"
          onmouseout="this.style.background='#2563eb';this.style.boxShadow='none'">
          Save Record
        </button>
      </div>

    </form>
  </div>
</div>

<script>
const overlay = document.getElementById('modalOverlay');

function openModal() {
  overlay.classList.add('is-open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  overlay.classList.remove('is-open');
  document.body.style.overflow = '';
}

overlay.addEventListener('click', function(e) {
  if (e.target === overlay) closeModal();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add Sale';
  document.getElementById('formId').value     = '';
  document.getElementById('product_id').value = '';
  document.getElementById('period').value     = '';
  document.getElementById('year').value       = '<?= date('Y') ?>';
  document.getElementById('amount').value     = '0';
  document.getElementById('quantity').value   = '0';
  openModal();
}

<?php if ($editRow): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('modalTitle').textContent = 'Edit Sale';
  document.getElementById('formId').value           = '<?= (int)$editRow['id'] ?>';
  document.getElementById('product_id').value       = '<?= (int)$editRow['product_id'] ?>';
  document.getElementById('period').value           = '<?= (int)$editRow['period'] ?>';
  document.getElementById('year').value             = '<?= (int)$editRow['year'] ?>';
  document.getElementById('amount').value           = '<?= (float)$editRow['amount'] ?>';
  document.getElementById('quantity').value         = '<?= (int)$editRow['quantity'] ?>';
  openModal();
});
<?php endif; ?>

setTimeout(() => {
  document.getElementById('alertError')?.remove();
  document.getElementById('alertSuccess')?.remove();
}, 4000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>