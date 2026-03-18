<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $pdo = getDBConnection();
        if (!$pdo) {
            $error = 'Database connection failed. Please try again later.';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                setUserSession([
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'role'     => $user['role']
                ]);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Sales Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
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
            rose:   { 400: '#fb7185', 500: '#f43f5e' },
            violet: { 400: '#a78bfa', 500: '#8b5cf6' },
          },
          keyframes: {
            'fade-up': {
              '0%':   { opacity: '0', transform: 'translateY(20px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' },
            },
            'fade-in': {
              '0%':   { opacity: '0' },
              '100%': { opacity: '1' },
            },
            'pulse-dot': {
              '0%, 100%': { opacity: '1' },
              '50%':      { opacity: '0.3' },
            },
            'float': {
              '0%, 100%': { transform: 'translateY(0px)' },
              '50%':      { transform: 'translateY(-8px)' },
            },
            'spin-slow': {
              '0%':   { transform: 'rotate(0deg)' },
              '100%': { transform: 'rotate(360deg)' },
            },
          },
          animation: {
            'fade-up':   'fade-up 0.6s ease both',
            'fade-up-1': 'fade-up 0.6s 0.1s ease both',
            'fade-up-2': 'fade-up 0.6s 0.2s ease both',
            'fade-up-3': 'fade-up 0.6s 0.3s ease both',
            'fade-in':   'fade-in 1s ease both',
            'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
            'float':     'float 4s ease-in-out infinite',
            'spin-slow': 'spin-slow 18s linear infinite',
          },
          boxShadow: {
            'glow-blue': '0 0 30px rgba(59,130,246,0.35)',
            card:        '0 8px 40px rgba(10,13,30,0.6)',
          },
        }
      }
    }
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: #0a0d1e;
      font-family: 'DM Sans', sans-serif;
      color: #dde0ea;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      pointer-events: none; z-index: 0;
    }

    .orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      pointer-events: none;
      z-index: 0;
    }

    .ring {
      position: fixed;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.04);
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      animation: spin-slow 18s linear infinite;
    }
    .ring-2 {
      animation-duration: 25s;
      animation-direction: reverse;
      border-color: rgba(255,255,255,0.03);
    }

    .card-glass {
      background: linear-gradient(145deg, rgba(30,34,64,0.92), rgba(18,21,45,0.96));
      border: 1px solid rgba(255,255,255,0.07);
      backdrop-filter: blur(20px);
    }

    .input-dark {
      background-color: #12152d;
      border: 1px solid rgba(255,255,255,0.08);
      color: #dde0ea;
      width: 100%;
      border-radius: 12px;
      padding: 11px 14px 11px 40px;
      font-size: 0.875rem;
      font-family: 'DM Sans', sans-serif;
      transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    }
    .input-dark::placeholder { color: #636f96; }
    .input-dark:focus {
      outline: none;
      border-color: rgba(59,130,246,0.5);
      box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
      background-color: #0f1228;
    }

    .accent-bar {
      height: 2px;
      background: linear-gradient(90deg, #2563eb, #22c55e, #8b5cf6);
    }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #12152d; }
    ::-webkit-scrollbar-thumb { background: #3a4066; border-radius: 2px; }
  </style>
</head>
<body>

  <div class="orb w-[500px] h-[500px] bg-azure-600/20 top-[-150px] left-[-150px]"></div>
  <div class="orb w-[400px] h-[400px] bg-volt-500/10 bottom-[-100px] right-[-100px]"></div>
  <div class="orb w-[300px] h-[300px] bg-violet-500/10 top-[40%] left-[40%]"></div>

  <div class="ring w-[700px] h-[700px]"></div>
  <div class="ring ring-2 w-[500px] h-[500px]"></div>

  <div class="relative z-10 w-full max-w-sm px-4 animate-fade-up">
    <div class="card-glass rounded-2xl shadow-card overflow-hidden">

      <!-- Accent bar -->
      <div class="accent-bar"></div>

      <div class="px-8 py-10">

        <div class="flex flex-col items-center mb-8 animate-fade-up-1">
          <div class="relative mb-4 animate-float">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-azure-600 to-violet-500 flex items-center justify-center shadow-glow-blue">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-volt-400 border-2 border-ink-950 animate-pulse-dot"></span>
          </div>
          <h1 class="font-display text-2xl font-bold text-white tracking-tight">Sales Dashboard</h1>
          <p class="text-ink-400 text-sm mt-1">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-rose-500/10 border border-rose-500/25 text-rose-400 text-sm mb-6 animate-fade-up">
          <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-volt-500/10 border border-volt-500/25 text-volt-400 text-sm mb-6 animate-fade-up">
          <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-5 animate-fade-up-2">

          <div>
            <label for="username" class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">
              Username
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-500 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </span>
              <input
                type="text"
                id="username"
                name="username"
                class="input-dark"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                placeholder="Enter username"
                required
                autofocus
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-xs font-medium text-ink-300 mb-2 uppercase tracking-widest">
              Password
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-500 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </span>
              <input
                type="password"
                id="password"
                name="password"
                class="input-dark"
                style="padding-right: 44px;"
                placeholder="Enter password"
                required
              />
              <button type="button" id="togglePassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-ink-500 hover:text-ink-300 transition-colors"
                style="line-height:0; background:none; border:none; cursor:pointer;">
                <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="pt-2 animate-fade-up-3">
            <button type="submit"
              class="w-full py-3 rounded-xl bg-azure-600 hover:bg-azure-500 text-white font-semibold text-sm transition-all duration-200 hover:shadow-glow-blue active:scale-[0.98] flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
              Sign In
            </button>
          </div>

        </form>

        <div class="mt-8 animate-fade-up-3">
          <div class="flex items-center gap-3 mb-3">
            <div class="h-px flex-1 bg-ink-800"></div>
            <span class="text-xs text-ink-600 font-mono">demo credentials</span>
            <div class="h-px flex-1 bg-ink-800"></div>
          </div>
          <div class="flex items-center justify-center gap-3">
            <span class="font-mono text-xs px-3 py-1.5 rounded-lg bg-ink-900 border border-ink-800 text-ink-400">admin</span>
            <span class="text-ink-700 text-xs">/</span>
            <span class="font-mono text-xs px-3 py-1.5 rounded-lg bg-ink-900 border border-ink-800 text-ink-400">password</span>
          </div>
        </div>

      </div>
    </div>

    <p class="text-center text-ink-700 text-xs mt-6 font-mono animate-fade-in">
      © <?= date('Y') ?> Sales Dashboard. All rights reserved.
    </p>
  </div>

  <script>
    const toggle  = document.getElementById('togglePassword');
    const pwInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    const eyeOpen  = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    const eyeSlash = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

    toggle.addEventListener('click', () => {
      const isPassword = pwInput.type === 'password';
      pwInput.type     = isPassword ? 'text' : 'password';
      eyeIcon.innerHTML = isPassword ? eyeSlash : eyeOpen;
    });
  </script>

</body>
</html>