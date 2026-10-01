        <?php if (isset($_SESSION['member_id']) || isset($_SESSION['superadmin_id']) || isset($_SESSION['admin_id'])): ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer with Template Layout -->
    <footer class="mt-16 bg-black border-t border-white/15 px-6 md:px-16 py-12">
        <div class="foot max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 text-xs text-white/70">
            <div>
                <a class="pill logo text-white font-bold" href="/index.php">EMPRESS 4.0</a>
                <p class="mt-4 text-white/60 text-xs max-w-xs leading-relaxed">
                    Double Your Path, Empower Your Future. Single-phase 3-matrix direct selling platform.
                </p>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-sm">Navigation</h4>
                <a href="/index.php#about" class="block mb-2 hover:text-white transition">About Us</a>
                <a href="/index.php#service" class="block mb-2 hover:text-white transition">Services</a>
                <a href="/business_plan.php" class="block mb-2 hover:text-white transition">Business Plan</a>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-sm">Support & Legal</h4>
                <a href="/faq.php" class="block mb-2 hover:text-white transition">Help & FAQ</a>
                <a href="/terms.php" class="block mb-2 hover:text-white transition">Terms & Conditions</a>
                <a href="/index.php#contact" class="block mb-2 hover:text-white transition">Contact Us</a>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 text-sm">Member Portals</h4>
                <a href="/login.php" class="block mb-2 hover:text-white transition">Customer Login</a>
                <a href="/register.php" class="block mb-2 hover:text-white transition">New Registration</a>
                <a href="/admin_login.php" class="block mb-2 hover:text-gold transition">Admin Portal</a>
            </div>
        </div>
        <div class="copy max-w-6xl mx-auto mt-8 pt-6 border-t border-white/10 flex justify-between items-center flex-wrap gap-4 text-xs text-white/50">
            <span>&copy; <?php echo date('Y'); ?> Empress Two Way 4.0. All rights reserved.</span>
            <span>Official Support: <a href="mailto:info@empress2way.com" class="text-gold hover:underline">info@empress2way.com</a></span>
        </div>
    </footer>
</body>
</html>
