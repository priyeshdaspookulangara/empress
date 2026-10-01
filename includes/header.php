<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Empress Two Way 4.0' : 'Empress Two Way 4.0 - Direct Selling Ecosystem'; ?></title>
    <!-- Google Font: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        obsidian: '#0f1015',
                        navy: '#060b19',
                        glass: 'rgba(255, 255, 255, 0.04)',
                        glassBorder: 'rgba(255, 255, 255, 0.2)',
                        gold: {
                            DEFAULT: '#c5a059',
                            400: '#d1b16d',
                            600: '#a38140',
                        },
                        champagne: '#f3e5ab',
                        'neon-cyan': '#00f3ff',
                        ice: '#d8f8ff',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            box-sizing: border-box;
            padding-top: env(safe-area-inset-top,0px);
            padding-bottom: env(safe-area-inset-bottom,0px);
            --bg: #000;
            --fg: #fff;
            --muted: rgba(255,255,255,.75);
            --line: rgba(255,255,255,.4);
            --card: rgba(255,255,255,.04);
            --orange: #e8604a;
            --pink: #c8306e;
            --purple: #8b2fa8;
            --blue: #2aa8e6;
        }
        html { scroll-padding-top: env(safe-area-inset-top,0px); scroll-behavior: smooth; }
        *, *::before, *::after { box-sizing: inherit; }
        body {
            margin: 0;
            background: #000;
            color: var(--fg);
            font-family: 'Outfit', 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }
        .blob { position: absolute; z-index: -1; filter: blur(70px); border-radius: 50%; pointer-events: none; }
        .b-blue { width:48vw; height:62vh; right:12vw; top:-8vh; background:radial-gradient(closest-side,#2aa8e6 0%,#2a8fd8 45%,rgba(42,143,216,0) 100%); }
        .b-purple { width:38vw; height:70vh; left:38vw; top:-4vh; background:radial-gradient(closest-side,#8b2fa8 0%,#7a2ea5 50%,rgba(122,46,165,0) 100%); }
        .b-pink { width:44vw; height:60vh; left:14vw; top:24vh; background:radial-gradient(closest-side,#c8306e 0%,#b02d7a 55%,rgba(176,45,122,0) 100%); transform:rotate(-24deg); }
        .b-orange { width:36vw; height:34vh; left:-4vw; bottom:-6vh; background:radial-gradient(closest-side,#e8604a 0%,#d9505a 55%,rgba(217,80,90,0) 100%); transform:rotate(-24deg); }
        .b-shade { width:60vw; height:60vh; left:-14vw; top:-14vh; background:#000; filter:blur(50px); border-radius:50%; }

        .glow { position:absolute; z-index:-1; filter:blur(90px); border-radius:50%; pointer-events:none; }
        .g1 { width:40vw; height:40vh; right:-12vw; top:10%; background:radial-gradient(closest-side,rgba(42,168,230,.55),transparent); }
        .g2 { width:40vw; height:40vh; left:-14vw; bottom:0; background:radial-gradient(closest-side,rgba(217,80,90,.5),transparent); }
        .g3 { width:50vw; height:50vh; left:25vw; top:-10vh; background:radial-gradient(closest-side,rgba(139,47,168,.6),transparent); }

        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 14px;
            color: var(--fg);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 0 28px;
            height: 44px;
            background: rgba(0,0,0,.15);
            transition: background .2s, border-color .2s;
            cursor: pointer;
        }
        .pill:hover, .pill:focus-visible {
            background: rgba(255,255,255,.12);
            border-color: #fff;
        }
        .pill.logo { padding: 0 22px; font-weight: 700; letter-spacing: .02em; }
        .pill.cta { align-self: center; height: 42px; padding: 0 26px; border-radius: 22px; font-size: 12px; min-width: 120px; }

        .glass-card, .card {
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 20px;
            padding: 26px;
            background: rgba(255,255,255,.03);
            backdrop-filter: blur(6px);
        }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 44px; }
        .card i { display: block; width: 34px; height: 34px; border-radius: 50%; margin-bottom: 22px; }
        .card:nth-child(1) i { background: linear-gradient(135deg, #e8604a, #c8306e); }
        .card:nth-child(2) i { background: linear-gradient(135deg, #c8306e, #7a2ea5); }
        .card:nth-child(3) i { background: linear-gradient(135deg, #7a2ea5, #2aa8e6); }

        .sec { position: relative; isolation: isolate; overflow: hidden; padding: clamp(48px,8vh,96px) clamp(20px,6vw,88px); background: #000; }
        .wrap { max-width: 1080px; margin: 0 auto; }
        .gold-gradient-text {
            background: linear-gradient(135deg, #f3e5ab 0%, #c5a059 50%, #e5c175 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gold-button {
            background: linear-gradient(135deg, #c5a059 0%, #a38140 100%);
            color: #0f1015;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .gold-button:hover {
            background: linear-gradient(135deg, #f3e5ab 0%, #c5a059 100%);
            box-shadow: 0 0 15px rgba(197, 160, 89, 0.5);
        }
        .up { color: #5fd7a0; }
        .down { color: #ff7b8a; }
        .tag { display:inline-block; padding:3px 11px; border-radius:12px; border:1px solid var(--line); font-size:11px; }
        .tag.new { border-color:var(--blue); color:var(--blue); }
        .tag.done { border-color:#5fd7a0; color:#5fd7a0; }
        .tag.wait { border-color:var(--orange); color:var(--orange); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #000; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }

        @media (max-width:720px) {
            .cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between custom-scrollbar bg-black text-white">

    <!-- Hero Background Blobs -->
    <div class="blob b-blue"></div>
    <div class="blob b-purple"></div>
    <div class="blob b-pink"></div>
    <div class="blob b-orange"></div>
    <div class="blob b-shade"></div>

    <!-- Navigation Header -->
    <nav class="flex items-center justify-between px-6 md:px-16 py-6 sticky top-0 z-50 bg-black/60 backdrop-blur-md border-b border-white/10">
        <a class="pill logo" href="/index.php">EMPRESS 4.0</a>

        <div class="hidden md:flex items-center gap-8 text-sm font-normal text-white/80">
            <a href="/index.php#about" class="hover:text-white transition">About</a>
            <a href="/index.php#service" class="hover:text-white transition">Services</a>
            <a href="/business_plan.php" class="hover:text-white transition">Business Plan</a>
            <a href="/faq.php" class="hover:text-white transition">FAQ</a>
            <a href="/index.php#contact" class="hover:text-white transition">Contact</a>
        </div>

        <div class="flex items-center gap-3">
            <?php if (isset($_SESSION['member_id'])): ?>
                <a class="pill" href="/customer/dashboard.php">Dashboard</a>
                <a class="pill" href="/logout.php">Logout</a>
            <?php elseif (isset($_SESSION['superadmin_id'])): ?>
                <a class="pill text-neon-cyan" href="/superadmin/index.php">Super Admin</a>
                <a class="pill" href="/logout.php">Logout</a>
            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <a class="pill text-gold" href="/admin/index.php">Admin Panel</a>
                <a class="pill" href="/logout.php">Logout</a>
            <?php else: ?>
                <a class="pill" href="/login.php">Log in</a>
                <a class="pill cta gold-button" href="/register.php">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
    </script>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 lg:px-8 py-4">
        <?php if (isset($_SESSION['member_id']) || isset($_SESSION['superadmin_id']) || isset($_SESSION['admin_id'])): ?>
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <!-- Left Sidebar (<aside>) Navigation Layout -->
                <aside class="w-full md:w-64 glass-card p-5 rounded-3xl border border-gold/30 shrink-0 space-y-6">
                    <?php if (isset($_SESSION['member_id'])): ?>
                        <div class="pb-4 border-b border-gold/20">
                            <span class="text-[10px] uppercase tracking-widest text-gold font-mono block">Member Portal</span>
                            <span class="font-extrabold text-champagne text-sm block truncate mt-0.5"><?php echo htmlspecialchars($_SESSION['member_name'] ?? $_SESSION['member_id']); ?></span>
                            <span class="text-[11px] font-mono text-emerald-400 font-bold"><?php echo htmlspecialchars($_SESSION['member_id']); ?></span>
                        </div>

                        <nav class="space-y-1.5 text-xs font-semibold">
                            <a href="/customer/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-gauge-high text-gold w-4 text-center"></i> Dashboard
                            </a>
                            <a href="/customer/deposit.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-qrcode text-emerald-400 w-4 text-center"></i> USDT Deposit
                            </a>
                            <a href="/customer/wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-wallet text-gold w-4 text-center"></i> Member Wallet
                            </a>
                            <a href="/customer/teams.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-sitemap text-gold w-4 text-center"></i> Matrix Tree
                            </a>
                            <a href="/customer/rebirths.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-rotate text-gold w-4 text-center"></i> Rebirth Positions
                            </a>
                            <a href="/customer/profile.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-id-card text-gold w-4 text-center"></i> Profile & Wallet
                            </a>
                            <a href="/business_plan.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-chart-pie text-gold w-4 text-center"></i> Compensation Plan
                            </a>
                            <a href="/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-red-500/10 text-red-400 transition mt-4 border-t border-gold/10 pt-3">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout
                            </a>
                        </nav>

                    <?php elseif (isset($_SESSION['superadmin_id'])): ?>
                        <div class="pb-4 border-b border-neon-cyan/20">
                            <span class="text-[10px] uppercase tracking-widest text-neon-cyan font-mono block">Super Admin Portal</span>
                            <span class="font-extrabold text-ice text-sm block mt-0.5"><?php echo htmlspecialchars($_SESSION['superadmin_username'] ?? 'Super Admin'); ?></span>
                        </div>

                        <nav class="space-y-1.5 text-xs font-semibold">
                            <a href="/superadmin/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-crown text-neon-cyan w-4 text-center"></i> Executive Overview
                            </a>
                            <a href="/superadmin/welcome.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-brands fa-whatsapp text-emerald-400 w-4 text-center"></i> Welcome & Greet
                            </a>
                            <a href="/superadmin/send_email.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-paper-plane text-neon-cyan w-4 text-center"></i> Dispatch HTML Email
                            </a>
                            <a href="/superadmin/members.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-users text-neon-cyan w-4 text-center"></i> Member List
                            </a>
                            <a href="/superadmin/sql_import.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-database text-neon-cyan w-4 text-center"></i> SQL Member Import
                            </a>
                            <a href="/superadmin/matrix_tree.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-sitemap text-neon-cyan w-4 text-center"></i> Matrix Tree
                            </a>
                            <a href="/superadmin/rebirths.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-rotate text-neon-cyan w-4 text-center"></i> Rebirth Positions
                            </a>
                            <a href="/superadmin/p2p_wallets.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-qrcode text-neon-cyan w-4 text-center"></i> P2P Wallet QR Codes
                            </a>
                            <a href="/superadmin/deposits.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-brands fa-ethereum text-emerald-400 w-4 text-center"></i> USDT Deposits
                            </a>
                            <a href="/superadmin/kyc.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-id-card text-neon-cyan w-4 text-center"></i> KYC Approvals
                            </a>
                            <a href="/superadmin/epins.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-key text-neon-cyan w-4 text-center"></i> ePIN Generator
                            </a>
                            <a href="/superadmin/wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-money-bill-transfer text-neon-cyan w-4 text-center"></i> Payout Requests
                            </a>
                            <a href="/superadmin/financials.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-neon-cyan/10 text-ice hover:text-neon-cyan transition">
                                <i class="fa-solid fa-vault text-neon-cyan w-4 text-center"></i> Audit Ledger
                            </a>
                            <a href="/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-red-500/10 text-red-400 transition mt-4 border-t border-gold/10 pt-3">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout
                            </a>
                        </nav>

                    <?php elseif (isset($_SESSION['admin_id'])): ?>
                        <div class="pb-4 border-b border-gold/20">
                            <span class="text-[10px] uppercase tracking-widest text-gold font-mono block">Admin Portal</span>
                            <span class="font-extrabold text-champagne text-sm block mt-0.5"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator'); ?></span>
                        </div>

                        <nav class="space-y-1.5 text-xs font-semibold">
                            <a href="/admin/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-chart-pie text-gold w-4 text-center"></i> Admin Overview
                            </a>
                            <a href="/admin/welcome.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-brands fa-whatsapp text-emerald-400 w-4 text-center"></i> Welcome & Greet
                            </a>
                            <a href="/admin/send_email.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-paper-plane text-gold w-4 text-center"></i> Dispatch HTML Email
                            </a>
                            <a href="/admin/members.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-users text-gold w-4 text-center"></i> Member List
                            </a>
                            <a href="/admin/sql_import.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-database text-gold w-4 text-center"></i> SQL Member Import
                            </a>
                            <a href="/admin/matrix_tree.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-sitemap text-gold w-4 text-center"></i> Matrix Tree
                            </a>
                            <a href="/admin/rebirths.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-rotate text-gold w-4 text-center"></i> Rebirth Positions
                            </a>
                            <a href="/admin/p2p_wallets.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-qrcode text-gold w-4 text-center"></i> P2P Wallet QR Codes
                            </a>
                            <a href="/admin/deposits.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-brands fa-ethereum text-emerald-400 w-4 text-center"></i> USDT Deposits
                            </a>
                            <a href="/admin/kyc.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-id-card text-gold w-4 text-center"></i> KYC Approvals
                            </a>
                            <a href="/admin/epins.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-key text-gold w-4 text-center"></i> ePIN Generator
                            </a>
                            <a href="/admin/wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-money-bill-transfer text-gold w-4 text-center"></i> Payout Requests
                            </a>
                            <a href="/admin/financials.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-gold/10 text-champagne hover:text-gold transition">
                                <i class="fa-solid fa-vault text-gold w-4 text-center"></i> Audit Ledger
                            </a>
                            <a href="/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-red-500/10 text-red-400 transition mt-4 border-t border-gold/10 pt-3">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout
                            </a>
                        </nav>
                    <?php endif; ?>
                </aside>

                <!-- Right Main Content Canvas -->
                <div class="flex-grow w-full overflow-hidden">
        <?php endif; ?>
