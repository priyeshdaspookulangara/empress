        <?php if (isset($_SESSION['member_id']) || isset($_SESSION['superadmin_id']) || isset($_SESSION['admin_id'])): ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="glass-card mt-12 border-t border-gold/20 py-8 text-center text-xs text-gold/70">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <span class="font-bold gold-gradient-text text-sm">Empress Two Way 3.0</span>
                <p class="text-champagne/60 mt-1">"Double Your Path, Empower Your Future."</p>
            </div>
            <div class="text-center md:text-right text-champagne/50">
                <p>&copy; <?php echo date('Y'); ?> Empress Two Way 3.0. All Rights Reserved.</p>
                <p class="text-gold/40 text-[10px] mt-1">Powered by BFS Forced 3-Matrix & Smart 50:50 Wallet Ecosystem</p>
            </div>
        </div>
    </footer>
</body>
</html>
