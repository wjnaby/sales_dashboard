<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pageTitle   = 'Products - Sales Dashboard';
$currentPage = 'products';

$pdo     = getDBConnection();
$error   = '';
$success = '';
$editId  = null;

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Product deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete. Product may have sales records.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $product_group_id = (int)($_POST['product_group_id'] ?? 0);
    $name             = trim($_POST['name'] ?? '');
    $sku              = trim($_POST['sku'] ?? '');

    if (empty($name) || $product_group_id <= 0) {
        $error  = 'Product name and group are required.';
        $editId = $id ?: null;
    } elseif (!$pdo) {
        $error = 'Database connection failed.';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET product_group_id=?, name=?, sku=? WHERE id=?");
                $stmt->execute([$product_group_id, $name, $sku ?: null, $id]);
                $success = 'Product updated successfully.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (product_group_id, name, sku) VALUES (?,?,?)");
                $stmt->execute([$product_group_id, $name, $sku ?: null]);
                $success = 'Product added successfully.';
            }
        } catch (PDOException $e) {
            $error  = 'Database error: ' . $e->getMessage();
            $editId = $id ?: null;
        }
    }
}

$productGroups = [];
$products      = [];
$editRow       = null;

if ($pdo) {
    try {
        $productGroups = $pdo->query("SELECT id, name FROM product_groups ORDER BY name")->fetchAll();
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.sku, pg.name as group_name
            FROM products p
            JOIN product_groups pg ON p.product_group_id = pg.id
            ORDER BY pg.name, p.name
        ");
        $products = $stmt->fetchAll();

        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $editId = (int)$_GET['edit'];
            $stmt   = $pdo->prepare("SELECT * FROM products WHERE id = ?");
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
    important: '#tw-products',
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

  #tw-products {
    position: relative;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }
  #tw-products::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none; z-index: 0;
  }
  #tw-products > * { position: relative; z-index: 1; }

  .card-glass {
    background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
  }

  .product-row { transition: background 0.15s; }
  .product-row:hover { background: rgba(59,130,246,0.05) !important; }

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
  .sku-badge {
    font-size: 0.68rem;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(59,130,246,0.1);
    color: #60a5fa;
    border: 1px solid rgba(59,130,246,0.15);
    font-family: 'JetBrains Mono', monospace;
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
    max-width: 440px;
    box-shadow: 0 8px 40px rgba(10,13,30,0.7);
    animation: slide-in 0.28s ease both;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }

  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: #12152d; }
  ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 3px; }
</style>

<div id="tw-products" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
    <div>
      <p class="text-xs font-mono uppercase tracking-[0.2em] text-volt-400 mb-1">Catalogue</p>
      <h1 class="font-display text-3xl lg:text-4xl font-bold text-white tracking-tight">Products</h1>
      <p class="text-ink-300 text-sm mt-1">Add, edit and manage your product catalogue.</p>
    </div>
    <button type="button" onclick="openAddModal()"
      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-azure-600 hover:bg-azure-500 text-white text-sm font-semibold transition-all duration-200 hover:shadow-glow-blue active:scale-[0.98] shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
      </svg>
      Add Product
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
        <p class="font-display text-sm font-bold text-white">All Products</p>
        <span class="font-mono text-xs px-2.5 py-1 rounded-full bg-ink-800 text-ink-300 border border-ink-700">
          <?= count($products) ?> rows
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
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">SKU</th>
            <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Group</th>
            <th class="text-center px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium w-28">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink-800/40">
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="5" class="text-center py-16">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-10 h-10 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  <p class="text-ink-400 text-sm">No products found.</p>
                  <button type="button" onclick="openAddModal()" class="text-xs text-azure-400 hover:text-azure-300 underline underline-offset-2 transition-colors">
                    Add your first product
                  </button>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $row): ?>
            <tr class="product-row">
              <td class="px-6 py-4">
                <span class="font-mono text-xs text-ink-500">#<?= (int)$row['id'] ?></span>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                  </div>
                  <span class="text-ink-100 font-medium"><?= htmlspecialchars($row['name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-4">
                <?php if (!empty($row['sku'])): ?>
                  <span class="sku-badge"><?= htmlspecialchars($row['sku']) ?></span>
                <?php else: ?>
                  <span class="font-mono text-xs text-ink-700">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-4">
                <span class="group-badge"><?= htmlspecialchars($row['group_name']) ?></span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <a href="?edit=<?= (int)$row['id'] ?>"
                     class="p-1.5 rounded-lg bg-azure-500/10 text-azure-400 hover:bg-azure-500/20 hover:text-azure-300 transition-all duration-150"
                     title="Edit">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                  </a>
                  <a href="?delete=<?= (int)$row['id'] ?>"
                     onclick="return confirm('Delete this product?')"
                     class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-all duration-150"
                     title="Delete">
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

      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,0.06);">
        <div>
          <p id="modalTitle" style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;margin:0;">Add Product</p>
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

      <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;">

        <div>
          <label for="product_group_id" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
            Product Group <span style="color:#fb7185;">*</span>
          </label>
          <select name="product_group_id" id="product_group_id" class="select-dark" required
            style="border-radius:11px;padding:10px 36px 10px 13px;font-size:0.875rem;">
            <option value="">Select group…</option>
            <?php foreach ($productGroups as $pg): ?>
              <option value="<?= (int)$pg['id'] ?>"><?= htmlspecialchars($pg['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="name" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
            Product Name <span style="color:#fb7185;">*</span>
          </label>
          <input type="text" name="name" id="name" class="input-dark"
            style="border-radius:11px;padding:10px 13px;font-size:0.875rem;"
            placeholder="e.g. Wireless Headphones" required/>
        </div>

        <div>
          <label for="sku" style="display:block;font-size:0.68rem;font-weight:600;color:#8f9ab8;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.1em;">
            SKU
            <span style="color:#636f96;font-weight:400;text-transform:none;letter-spacing:0;margin-left:4px;">(optional)</span>
          </label>
          <input type="text" name="sku" id="sku" class="input-dark"
            style="border-radius:11px;padding:10px 13px;font-size:0.875rem;font-family:'JetBrains Mono',monospace;"
            placeholder="e.g. SKU-00123"/>
        </div>

      </div>

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
          Save Product
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
  document.getElementById('modalTitle').textContent   = 'Add Product';
  document.getElementById('formId').value             = '';
  document.getElementById('product_group_id').value   = '';
  document.getElementById('name').value               = '';
  document.getElementById('sku').value                = '';
  openModal();
}

<?php if ($editRow): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('modalTitle').textContent   = 'Edit Product';
  document.getElementById('formId').value             = '<?= (int)$editRow['id'] ?>';
  document.getElementById('product_group_id').value   = '<?= (int)$editRow['product_group_id'] ?>';
  document.getElementById('name').value               = '<?= htmlspecialchars($editRow['name'], ENT_QUOTES) ?>';
  document.getElementById('sku').value                = '<?= htmlspecialchars($editRow['sku'] ?? '', ENT_QUOTES) ?>';
  openModal();
});
<?php endif; ?>

setTimeout(() => {
  document.getElementById('alertError')?.remove();
  document.getElementById('alertSuccess')?.remove();
}, 4000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>