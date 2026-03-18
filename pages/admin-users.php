<?php
/**
 * Admin - User Management
 * List users, show counts, create new users, and write audit logs
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDBConnection();
$error   = '';
$success = '';

// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] === 'admin' ? 'admin' : 'user';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!$pdo) {
        $error = 'Database connection failed. Please try again later.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username is already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, $hash, $email, $role]);

                $current = getCurrentUser();
                if ($current && $pdo) {
                    $logStmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)');
                    $logStmt->execute([
                        $current['id'],
                        'create_user',
                        sprintf('Created user "%s" with role "%s"', $username, $role)
                    ]);
                }

                $success = 'User created successfully.';
                $_POST['username'] = $_POST['email'] = '';
            }
        } catch (PDOException $e) {
            $error = 'Failed to create user. Please try again.';
        }
    }
}

// Fetch stats and users list
$totalUsers = 0;
$adminCount = 0;
$userCount  = 0;
$users      = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(role = 'admin') as admins,
            SUM(role = 'user')  as users
        FROM users");
        $row = $stmt->fetch();
        if ($row) {
            $totalUsers = (int)$row['total'];
            $adminCount = (int)$row['admins'];
            $userCount  = (int)$row['users'];
        }

        $stmt = $pdo->query('SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY created_at DESC');
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = $error ?: 'Failed to load users.';
    }
}

$pageTitle   = 'Admin Users - Sales Dashboard';
$currentPage = 'admin_users';

include __DIR__ . '/../includes/header.php';
?>

<!-- =====================================================
     TAILWIND + FONTS  (scoped to #tw-admin-users)
     Bootstrap navbar from header.php is untouched above
     ===================================================== -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    important: '#tw-admin-users',
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

  #tw-admin-users {
    position: relative;
    font-family: 'DM Sans', sans-serif;
    color: #dde0ea;
  }
  #tw-admin-users::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none; z-index: 0;
  }
  #tw-admin-users > * { position: relative; z-index: 1; }

  .card-glass {
    background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
  }

  .input-dark {
    background-color: #12152d;
    border: 1px solid rgba(255,255,255,0.08);
    color: #dde0ea;
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    width: 100%;
  }
  .input-dark::placeholder { color: #636f96; }
  .input-dark:focus {
    outline: none;
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    background-color: #0f1228;
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
    width: 100%;
  }
  .select-dark:focus {
    outline: none;
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
  }
  .select-dark option { background: #12152d; }

  .user-row { transition: background 0.15s; }
  .user-row:hover { background: rgba(59,130,246,0.05) !important; }

  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: #12152d; }
  ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 3px; }
</style>

<div id="tw-admin-users" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- ══ HEADER ══ -->
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
    <div>
      <p class="text-xs font-mono uppercase tracking-[0.2em] text-volt-400 mb-1">Admin</p>
      <h1 class="font-display text-3xl lg:text-4xl font-bold text-white tracking-tight">User Management</h1>
      <p class="text-ink-300 text-sm mt-1">Track team members and create new logins.</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg card-glass text-sm">
        <span class="w-2 h-2 rounded-full bg-volt-400 animate-pulse-dot"></span>
        <span class="text-ink-200 font-medium font-mono text-xs">
          <?= $adminCount ?> admin · <?= $userCount ?> user
        </span>
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

  <!-- ══ STAT CARDS ══ -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 animate-fade-up-1">

    <!-- Total -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-azure-600/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-azure-500/5 -translate-y-6 translate-x-6 group-hover:bg-azure-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-azure-500/15 text-azure-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-volt-400 bg-volt-400/10 px-2 py-1 rounded-full">Total</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">All Users</p>
        <p class="font-display text-3xl font-bold text-white"><?= $totalUsers ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-azure-600 to-azure-400" style="width:100%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Registered accounts</p>
      </div>
    </div>

    <!-- Admins -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-amber-500/5 -translate-y-6 translate-x-6 group-hover:bg-amber-500/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-amber-500/15 text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-amber-400 bg-amber-400/10 px-2 py-1 rounded-full">Admin</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Administrators</p>
        <p class="font-display text-3xl font-bold text-white"><?= $adminCount ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-amber-500 to-amber-400"
               style="width:<?= $totalUsers > 0 ? round(($adminCount / $totalUsers) * 100) : 0 ?>%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Full access accounts</p>
      </div>
    </div>

    <!-- Standard users -->
    <div class="card-glass rounded-2xl p-6 relative overflow-hidden group">
      <div class="absolute inset-0 bg-gradient-to-br from-volt-500/10 to-transparent pointer-events-none"></div>
      <div class="absolute top-0 right-0 w-28 h-28 rounded-full bg-volt-400/5 -translate-y-6 translate-x-6 group-hover:bg-volt-400/10 transition-colors duration-500"></div>
      <div class="relative">
        <div class="flex items-center justify-between mb-4">
          <div class="p-2.5 rounded-xl bg-volt-500/15 text-volt-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-xs font-mono text-volt-400 bg-volt-400/10 px-2 py-1 rounded-full">Standard</span>
        </div>
        <p class="text-ink-300 text-xs uppercase tracking-widest font-medium mb-1">Standard Users</p>
        <p class="font-display text-3xl font-bold text-white"><?= $userCount ?></p>
        <div class="mt-4 h-1 rounded-full bg-ink-800">
          <div class="h-1 rounded-full bg-gradient-to-r from-volt-500 to-volt-400"
               style="width:<?= $totalUsers > 0 ? round(($userCount / $totalUsers) * 100) : 0 ?>%"></div>
        </div>
        <p class="text-xs text-ink-400 mt-1.5">Read &amp; write access</p>
      </div>
    </div>

  </div>

  <!-- ══ CREATE FORM + TABLE ══ -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 animate-fade-up-2">

    <!-- Create user form -->
    <div class="lg:col-span-1 card-glass rounded-2xl p-6 flex flex-col gap-5">
      <div>
        <p class="font-display text-sm font-bold text-white mb-0.5">Add User</p>
        <p class="text-ink-400 text-xs">Create a new login for your team.</p>
      </div>

      <form method="POST" class="flex flex-col gap-4 flex-1">
        <input type="hidden" name="action" value="create_user"/>

        <div>
          <label class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">Username <span class="text-rose-400">*</span></label>
          <input type="text" name="username" class="input-dark rounded-xl px-3 py-2.5 text-sm"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            placeholder="e.g. john_doe" required/>
        </div>

        <div>
          <label class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">Email <span class="text-rose-400">*</span></label>
          <input type="email" name="email" class="input-dark rounded-xl px-3 py-2.5 text-sm"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            placeholder="john@example.com" required/>
        </div>

        <div>
          <label class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">Password <span class="text-rose-400">*</span></label>
          <input type="password" name="password" class="input-dark rounded-xl px-3 py-2.5 text-sm"
            placeholder="Min. 8 characters" required/>
        </div>

        <div>
          <label class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">Role</label>
          <select name="role" class="select-dark rounded-xl px-3 py-2.5 text-sm">
            <option value="user"  <?= (($_POST['role'] ?? '') === 'admin') ? '' : 'selected' ?>>User</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

        <div class="pt-1 mt-auto">
          <button type="submit"
            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-azure-600 hover:bg-azure-500 text-white text-sm font-semibold transition-all duration-200 hover:shadow-glow-blue active:scale-[0.98]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Create User
          </button>
        </div>
      </form>
    </div>

    <!-- Users table -->
    <div class="lg:col-span-3 card-glass rounded-2xl overflow-hidden">

      <!-- Toolbar -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-ink-800/60">
        <div class="flex items-center gap-3">
          <p class="font-display text-sm font-bold text-white">All Users</p>
          <span class="font-mono text-xs px-2.5 py-1 rounded-full bg-ink-800 text-ink-300 border border-ink-700">
            <?= count($users) ?> rows
          </span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-volt-400 animate-pulse-dot"></span>
          <span class="text-xs text-ink-400 font-mono">Newest first</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-ink-800/60">
              <th class="text-left px-6 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">#</th>
              <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">User</th>
              <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium hidden sm:table-cell">Email</th>
              <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium">Role</th>
              <th class="text-left px-4 py-3.5 text-xs font-mono uppercase tracking-widest text-ink-400 font-medium hidden md:table-cell">Created</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-ink-800/40">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="5" class="text-center py-16">
                  <div class="flex flex-col items-center gap-3">
                    <svg class="w-10 h-10 text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-ink-400 text-sm">No users found.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
              <tr class="user-row">
                <td class="px-6 py-4">
                  <span class="font-mono text-xs text-ink-500">#<?= (int)$u['id'] ?></span>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <!-- Avatar tile -->
                    <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#fff;flex-shrink:0;">
                      <?= strtoupper(substr($u['username'], 0, 1)) ?>
                    </div>
                    <div>
                      <p class="text-ink-100 font-medium text-sm"><?= htmlspecialchars($u['username']) ?></p>
                      <p class="text-ink-500 text-xs sm:hidden"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 hidden sm:table-cell">
                  <span class="text-ink-400 text-xs font-mono"><?= htmlspecialchars($u['email']) ?></span>
                </td>
                <td class="px-4 py-4">
                  <?php if ($u['role'] === 'admin'): ?>
                    <span style="font-size:0.65rem;padding:2px 8px;border-radius:999px;background:rgba(245,158,11,0.15);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);font-weight:600;font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;">
                      Admin
                    </span>
                  <?php else: ?>
                    <span style="font-size:0.65rem;padding:2px 8px;border-radius:999px;background:rgba(30,34,64,0.8);color:#8f9ab8;border:1px solid rgba(255,255,255,0.08);font-weight:600;font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;">
                      User
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 hidden md:table-cell">
                  <span class="font-mono text-xs text-ink-500"><?= htmlspecialchars($u['created_at']) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div><!-- /#tw-admin-users -->

<script>
setTimeout(() => {
  document.getElementById('alertError')?.remove();
  document.getElementById('alertSuccess')?.remove();
}, 4000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>