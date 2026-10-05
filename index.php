<?php
$pageTitle = "Home - Empower Your Future";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section matching template layout -->
<section class="hero text-center py-16 px-4">
    <div class="content mx-auto my-auto max-w-2xl flex flex-col items-center">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tight mb-4">
            Empress<br><span class="gold-gradient-text">Two Way 4.0</span>
        </h1>
        <p class="text-sm md:text-base text-white/80 max-w-lg mb-8 leading-relaxed font-light">
            Double Your Path, Empower Your Future. A single-phase direct selling platform with automated 3-matrix forced spillover, 50:50 smart wallet division, and guaranteed 6-level fixed node payouts.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a class="pill cta gold-button" href="/register.php">Get Started</a>
            <a class="pill" href="/business_plan.php">Business Plan</a>
            <a class="pill bg-purple-600/30 border border-purple-500/50 hover:bg-purple-600/50 text-white flex items-center gap-2" href="/EmpressTwoWay4.0.apk" download>
                <i class="fa-brands fa-android text-emerald-400 text-lg"></i> Download Mobile App (.APK)
            </a>
        </div>
    </div>
</section>

<!-- About Section matching template .sec .wrap .about -->
<section class="sec" id="about">
    <div class="glow g1"></div>
    <div class="wrap about">
        <div>
            <h2>We shape financial independence into reality</h2>
            <p class="lead">Empress Two Way 4.0 provides a single-phase direct selling structure engineered for team spillover and sustainable payout division.</p>
        </div>
        <div>
            <p>Our automated engine uses Breadth-First Search (BFS) auto-spillover placement starting from Root (EMP100000) to fill 3-child slots strictly left-to-right across the entire network.</p>
            <div class="stats">
                <div><b>3-Matrix</b><span>Forced Spillover</span></div>
                <div><b>50:50</b><span>Smart Wallet Split</span></div>
                <div><b>6 Levels</b><span>Income Schedule</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Services / Features Section matching template .cards -->
<section class="sec" id="service">
    <div class="glow g2"></div>
    <div class="wrap">
        <h2>What we provide</h2>
        <p class="lead">Everything you need to grow your network and track your earnings transparently.</p>
        <div class="cards">
            <div class="card">
                <i></i>
                <h3>Global BFS Auto-Spillover</h3>
                <p>Strict left-to-right company-wide placement fills downline slots automatically, helping every team member succeed.</p>
            </div>
            <div class="card">
                <i></i>
                <h3>50:50 Smart Wallet Division</h3>
                <p>Commissions split 50% to Customer Wallet (withdrawable) and 50% to Company portion (60% Burfee Cart & 40% Charity).</p>
            </div>
            <div class="card">
                <i></i>
                <h3>Automated Rebirth Engine</h3>
                <p>Completing Levels 3, 4, 5, and 6 grants 10, 20, 70, and 100 rebirth positions to continually recycle earning power.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section matching template .contact -->
<section class="sec contact" id="contact">
    <div class="glow g3"></div>
    <div class="wrap">
        <h2>Let's build something together</h2>
        <p class="lead">Have questions? Send us a message and our support team will reach out promptly.</p>
        <div class="form">
            <input id="em" type="email" placeholder="you@example.com" aria-label="Email address">
            <button class="pill gold-button" id="send" type="button">Get in touch</button>
        </div>
        <div class="msg text-xs text-white/70 mt-4" id="msg" role="status"></div>
    </div>
</section>

<script>
document.getElementById('send')?.addEventListener('click', function(){
    var v = document.getElementById('em').value.trim(), m = document.getElementById('msg');
    m.textContent = /^\S+@\S+\.\S+$/.test(v) ? 'Thanks! We will be in touch soon.' : 'Enter a valid email address.';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
