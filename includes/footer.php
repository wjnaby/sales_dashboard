</main>

    <!-- ── Footer ─────────────────────────────────────────────── -->
    <footer style="
        background: linear-gradient(180deg, rgba(14,17,38,0.0), rgba(14,17,38,0.98));
        border-top: 1px solid rgba(255,255,255,0.06);
        font-family: 'DM Sans', sans-serif;
        padding: 20px 0;
        margin-top: auto;
    ">
        <div style="max-width:1600px; margin:0 auto; padding:0 24px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">

            <!-- Left: brand -->
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:24px; height:24px; border-radius:6px; background:linear-gradient(135deg,#2563eb,#8b5cf6); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="12" height="12" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <span style="font-family:'Syne',sans-serif; font-size:0.8rem; font-weight:700; color:#3a4066;">
                    Sales Dashboard
                </span>
            </div>

            <!-- Center: copyright -->
            <p style="font-size:0.72rem; color:#485079; margin:0; font-family:'JetBrains Mono',monospace; letter-spacing:0.02em;">
                &copy; <?= date('Y') ?> Sales Dashboard. All rights reserved.
            </p>

            <!-- Right: live indicator -->
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:6px; height:6px; border-radius:50%; background:#22c55e; display:inline-block; animation:pulse-dot 2s ease-in-out infinite;"></span>
                <span style="font-size:0.7rem; color:#485079; font-family:'JetBrains Mono',monospace;">System online</span>
            </div>

        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>