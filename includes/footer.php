        <?php if (isset($_SESSION['member_id']) || isset($_SESSION['superadmin_id']) || isset($_SESSION['admin_id'])): ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Global Footer -->
    <footer class="bg-[#05070f] border-t border-gold/20 py-12 px-6 md:px-16 text-sm text-white/70">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div class="space-y-4">
                <a class="pill logo text-gold font-cinzel font-bold tracking-wider inline-flex items-center gap-2" href="/index.php">
                    <span class="w-5 h-5 rounded-full bg-gold text-[#07080c] font-black text-xs flex items-center justify-center">R</span>
                    REYON GLOBAL IMPACT
                </a>
                <p class="text-xs text-white/60 leading-relaxed">
                    Double Your Path, Empower Your Future. A premier Web3 direct-selling ecosystem powered by automated 3-matrix forced spillover, 60:40 wallet allocation, and USDT BEP-20 blockchain settlement.
                </p>
            </div>

            <div>
                <h4 class="text-gold font-cinzel font-semibold text-xs tracking-widest uppercase mb-4">Quick Links</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="/index.php" class="hover:text-gold transition">Home</a></li>
                    <li><a href="/business_plan.php" class="hover:text-gold transition">Business Plan</a></li>
                    <li><a href="/faq.php" class="hover:text-gold transition">Frequently Asked Questions</a></li>
                    <li><a href="/terms.php" class="hover:text-gold transition">Terms & Conditions</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-gold font-cinzel font-semibold text-xs tracking-widest uppercase mb-4">Portals</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="/login.php" class="hover:text-gold transition">Member Login</a></li>
                    <li><a href="/register.php" class="hover:text-gold transition">Join Reyon Matrix</a></li>
                    <li><a href="/admin_login.php" class="hover:text-gold transition">Admin Portal</a></li>
                    <li><a href="/superadmin_login.php" class="hover:text-neon-cyan transition">Super Admin</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-gold font-cinzel font-semibold text-xs tracking-widest uppercase mb-4">Blockchain Network</h4>
                <p class="text-xs text-white/60 leading-relaxed mb-3">
                    Settlements execute exclusively on <strong>Tether USD (USDT)</strong> on the <strong>BNB Smart Chain (BEP-20)</strong> network.
                </p>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono">
                    <i class="fa-brands fa-ethereum"></i> BSC BEP-20 Active
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto border-t border-gold/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/50">
            <span>&copy; <?php echo date('Y'); ?> Reyon Global Impact. All rights reserved.</span>
            <span>Official Support: <a href="mailto:support@reyonglobal.com" class="text-gold hover:underline">support@reyonglobal.com</a></span>
        </div>
    </footer>

</body>
</html>
