<?php
$pageTitle = "Reyon Global Impact - Web & Mobile Ecosystem";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section matching template layout -->
<section class="hero text-center py-20 px-4">
    <div class="content mx-auto my-auto max-w-3xl flex flex-col items-center">
        <span class="px-4 py-1.5 rounded-full bg-gold/10 border border-gold/30 text-gold text-xs font-cinzel font-semibold tracking-widest uppercase mb-6">
            Premier Direct Selling Ecosystem
        </span>
        <h1 class="text-4xl md:text-7xl font-cinzel font-extrabold tracking-tight mb-6 leading-tight">
            Reyon<br><span class="gold-gradient-text">Global Impact</span>
        </h1>
        <p class="text-base md:text-lg text-white/80 max-w-xl mb-10 leading-relaxed font-light">
            Double Your Path, Empower Your Future. A revolutionary platform with automated 3-matrix forced spillover, 50:50 smart wallet division, and guaranteed 6-level fixed node payouts.
        </p>
        <div class="flex items-center gap-5">
            <a class="pill cta gold-button font-cinzel text-sm px-8 py-3 tracking-wider" href="/register.php">JOIN NOW</a>
            <a class="pill font-cinzel text-sm px-6 py-3" href="/business_plan.php">BUSINESS PLAN</a>
        </div>
    </div>
</section>

<!-- About Section matching template .sec .wrap .about -->
<section class="sec" id="about">
    <div class="wrap">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-2xl md:text-4xl font-cinzel font-bold text-champagne mb-4">Shaping Financial Independence Into Reality</h2>
                <p class="text-sm md:text-base text-white/70 leading-relaxed">
                    Reyon Global Impact provides a single-phase direct selling structure engineered for company-wide team spillover, automated rebirth positions, and sustainable payout division.
                </p>
            </div>
            <div>
                <p class="text-xs md:text-sm text-white/60 leading-relaxed mb-6">
                    Our automated engine uses Breadth-First Search (BFS) auto-spillover placement starting from Root (EMP100000) to fill 3-child slots strictly left-to-right across the entire global network.
                </p>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-4 rounded-2xl bg-gold/5 border border-gold/20">
                        <div class="font-cinzel text-xl font-bold text-gold">3-Matrix</div>
                        <span class="text-[10px] text-white/60 uppercase">Auto-Spillover</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-gold/5 border border-gold/20">
                        <div class="font-cinzel text-xl font-bold text-gold">50:50</div>
                        <span class="text-[10px] text-white/60 uppercase">Wallet Split</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-gold/5 border border-gold/20">
                        <div class="font-cinzel text-xl font-bold text-gold">6 Levels</div>
                        <span class="text-[10px] text-white/60 uppercase">Fixed Income</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services / Features Section matching template .cards -->
<section class="sec" id="service">
    <div class="wrap">
        <h2 class="text-2xl md:text-4xl font-cinzel font-bold text-champagne text-center mb-2">Platform Highlights</h2>
        <p class="text-sm text-white/60 text-center max-w-lg mx-auto mb-12">Everything you need to build your global downline and track earnings with total transparency.</p>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="glass-card p-8 hover:border-gold/50 transition">
                <i class="fa-solid fa-network-wired text-3xl text-gold mb-6 block"></i>
                <h3 class="font-cinzel text-lg font-bold text-champagne mb-3">Global BFS Auto-Spillover</h3>
                <p class="text-xs text-white/60 leading-relaxed">Strict left-to-right company-wide placement fills downline slots automatically, helping every team member succeed.</p>
            </div>
            <div class="glass-card p-8 hover:border-gold/50 transition">
                <i class="fa-solid fa-vault text-3xl text-gold mb-6 block"></i>
                <h3 class="font-cinzel text-lg font-bold text-champagne mb-3">50:50 Smart Wallet Division</h3>
                <p class="text-xs text-white/60 leading-relaxed">Commissions split 50% to Customer Wallet (withdrawable) and 50% to Company portion (30% Company & 20% Charity Fund).</p>
            </div>
            <div class="glass-card p-8 hover:border-gold/50 transition">
                <i class="fa-solid fa-arrows-spin text-3xl text-gold mb-6 block"></i>
                <h3 class="font-cinzel text-lg font-bold text-champagne mb-3">Automated Rebirth Engine</h3>
                <p class="text-xs text-white/60 leading-relaxed">Completing Levels 3, 4, 5, and 6 grants 10, 20, 70, and 100 rebirth positions to continually recycle earning power.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section matching template .contact -->
<section class="sec contact" id="contact">
    <div class="wrap text-center max-w-xl mx-auto">
        <h2 class="text-2xl md:text-4xl font-cinzel font-bold text-champagne mb-3">Let's Build Something Great</h2>
        <p class="text-sm text-white/60 mb-8">Have questions? Send us a message and our support team will reach out promptly.</p>
        <div class="flex gap-3">
            <input id="em" type="email" placeholder="you@example.com" aria-label="Email address" class="flex-grow px-5 py-3 rounded-full bg-black/60 border border-gold/30 text-sm text-white focus:outline-none focus:border-gold">
            <button class="pill gold-button font-cinzel font-bold text-xs" id="send" type="button">GET IN TOUCH</button>
        </div>
        <div class="msg text-xs text-gold mt-4" id="msg" role="status"></div>
    </div>
</section>

<script>
document.getElementById('send')?.addEventListener('click', function(){
    var v = document.getElementById('em').value.trim(), m = document.getElementById('msg');
    m.textContent = /^\S+@\S+\.\S+$/.test(v) ? 'Thanks! We will be in touch soon.' : 'Enter a valid email address.';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
