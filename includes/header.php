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
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Reyon Global Impact' : 'Reyon Global Impact - Web & Mobile Direct Selling Platform'; ?></title>
    <!-- Google Fonts: Outfit & Cinzel -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        obsidian: '#090a0f',
                        navy: '#05070f',
                        glass: 'rgba(255, 255, 255, 0.03)',
                        glassBorder: 'rgba(197, 160, 89, 0.25)',
                        gold: {
                            DEFAULT: '#c5a059',
                            300: '#edd8a4',
                            400: '#d4b062',
                            500: '#c5a059',
                            600: '#a38140',
                            700: '#80632c',
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
            --bg: #07080c;
            --fg: #ffffff;
            --muted: rgba(255,255,255,.75);
            --line: rgba(197, 160, 89, 0.3);
            --card: rgba(15, 16, 21, 0.7);
        }
        html { scroll-padding-top: env(safe-area-inset-top,0px); scroll-behavior: smooth; }
        *, *::before, *::after { box-sizing: inherit; }
        body {
            margin: 0;
            background: #07080c;
            color: var(--fg);
            font-family: 'Outfit', 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }
        .font-cinzel { font-family: 'Cinzel', serif; }

        .blob { position: absolute; z-index: -1; filter: blur(90px); border-radius: 50%; pointer-events: none; opacity: 0.25; }
        .b-gold { width:45vw; height:55vh; right:10vw; top:-5vh; background:radial-gradient(closest-side, #c5a059 0%, #80632c 60%, transparent 100%); }
        .b-purple { width:35vw; height:65vh; left:35vw; top:-4vh; background:radial-gradient(closest-side, #6b21a8 0%, #3b0764 60%, transparent 100%); }
        .b-shade { width:70vw; height:70vh; left:-10vw; top:-10vh; background:#07080c; filter:blur(60px); border-radius:50%; }

        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 9999px;
            color: var(--fg);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 0 24px;
            height: 42px;
            background: rgba(15,16,21,.6);
            transition: all .25s ease;
            cursor: pointer;
        }
        .pill:hover, .pill:focus-visible {
            background: rgba(197,160,89,.15);
            border-color: #c5a059;
            color: #f3e5ab;
        }
        .pill.logo {
            padding: 0 20px;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            letter-spacing: .08em;
            border-color: rgba(197, 160, 89, 0.4);
            color: #d4b062;
            background: rgba(197, 160, 89, 0.08);
        }
        .pill.logo:hover {
            color: #f3e5ab;
            box-shadow: 0 0 15px rgba(197, 160, 89, 0.3);
        }
        .pill.cta {
            align-self: center;
            height: 42px;
            padding: 0 26px;
            border-radius: 9999px;
            font-size: 13px;
            min-width: 120px;
        }

        .glass-card, .card {
            border: 1px solid rgba(197, 160, 89, 0.22);
            border-radius: 24px;
            padding: 26px;
            background: rgba(15, 17, 23, 0.75);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }

        .sec { position: relative; isolation: isolate; overflow: hidden; padding: clamp(48px,8vh,96px) clamp(20px,6vw,88px); background: #07080c; }
        .wrap { max-width: 1080px; margin: 0 auto; }
        .gold-gradient-text {
            background: linear-gradient(135deg, #f3e5ab 0%, #d4b062 50%, #a38140 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gold-button {
            background: linear-gradient(135deg, #d4b062 0%, #c5a059 50%, #a38140 100%);
            color: #07080c;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 1px solid rgba(243, 229, 171, 0.4);
        }
        .gold-button:hover {
            background: linear-gradient(135deg, #f3e5ab 0%, #d4b062 100%);
            box-shadow: 0 0 20px rgba(197, 160, 89, 0.4);
            transform: translateY(-1px);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #07080c; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(197, 160, 89, 0.3); border-radius: 3px; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between custom-scrollbar bg-[#07080c] text-white">

    <!-- Hero Background Ambient Glows -->
    <div class="blob b-gold"></div>
    <div class="blob b-purple"></div>
    <div class="blob b-shade"></div>

    <!-- Navigation Header -->
    <nav class="flex items-center justify-between px-6 md:px-16 py-5 sticky top-0 z-50 bg-[#07080c]/80 backdrop-blur-md border-b border-gold/20">
        <a class="pill logo flex items-center gap-2.5" href="/index.php">
            <span class="w-6 h-6 rounded-full bg-gradient-to-tr from-gold-600 to-champagne flex items-center justify-center text-[#07080c] text-xs font-black">R</span>
            <span class="font-cinzel tracking-wider text-gold font-bold">REYON GLOBAL IMPACT</span>
        </a>

        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-white/80">
            <a href="/index.php#about" class="hover:text-gold transition">About</a>
            <a href="/index.php#service" class="hover:text-gold transition">Services</a>
            <a href="/business_plan.php" class="hover:text-gold transition">Business Plan</a>
            <a href="/faq.php" class="hover:text-gold transition">FAQ</a>
            <a href="/index.php#contact" class="hover:text-gold transition">Contact</a>
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

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 lg:px-8 py-6">
        <?php if (isset($_SESSION['member_id']) || isset($_SESSION['superadmin_id']) || isset($_SESSION['admin_id'])): ?>
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <!-- Left Sidebar Navigation Layout -->
                <aside class="w-full md:w-64 glass-card p-5 rounded-3xl border border-gold/30 shrink-0 space-y-6">
                    <?php if (isset($_SESSION['member_id'])): ?>
                        <div class="pb-4 border-b border-gold/20">
                            <span class="text-[10px] uppercase tracking-widest text-gold font-mono block">Member Portal</span>
                            <span class="font-bold text-champagne text-sm block truncate mt-0.5"><?php echo htmlspecialchars($_SESSION['member_name'] ?? $_SESSION['member_id']); ?></span>
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
                            <span class="font-bold text-champagne text-sm block mt-0.5"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator'); ?></span>
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
