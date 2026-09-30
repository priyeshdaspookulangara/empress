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
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Empress Two Way 3.0' : 'Empress Two Way 3.0 - Direct Selling Ecosystem'; ?></title>
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
            --fg: #fff;
            --muted: rgba(255,255,255,.7);
            --line: rgba(255,255,255,.2);
            --card: rgba(255,255,255,.04);
            --orange: #e8604a;
            --pink: #c8306e;
            --purple: #8b2fa8;
            --blue: #2aa8e6;
        }
        body {
            margin: 0;
            background: #000;
            color: var(--fg);
            font-family: 'Outfit', 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }
        .bg-glow {
            position: fixed;
            inset: 0;
            z-index: -1;
            overflow: hidden;
            background: #000;
        }
        .bg-glow i {
            position: absolute;
            filter: blur(110px);
            border-radius: 50%;
            opacity: .55;
        }
        .bg-glow i:nth-child(1) { width:45vw; height:45vh; right:-10vw; top:-12vh; background:var(--blue); }
        .bg-glow i:nth-child(2) { width:35vw; height:40vh; right:22vw; top:-14vh; background:var(--purple); }
        .bg-glow i:nth-child(3) { width:40vw; height:36vh; left:-14vw; bottom:-14vh; background:var(--orange); }

        .glass-card, .card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }
        .glass-card:hover, .card:hover {
            border-color: rgba(255, 255, 255, 0.4);
        }
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.4);
            border-radius: 14px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 0 20px;
            height: 40px;
            background: rgba(0,0,0,.2);
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .pill:hover, .pill:focus-visible {
            background: rgba(255,255,255,.12);
            border-color: #fff;
        }
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
            transform: translateY(-1px);
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
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between custom-scrollbar">

    <!-- Background Glow Blobs -->
    <div class="bg-glow"><i></i><i></i><i></i></div>

    <!-- Navigation Header -->
    <nav class="glass-card sticky top-0 z-50 px-4 lg:px-8 py-3 mb-6">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="/index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full border border-gold/40 flex items-center justify-center bg-obsidian text-gold shadow-lg shadow-gold/10">
                    <i class="fa-solid font-bold text-xl">E3</i>
                </div>
                <div>
                    <span class="text-xl font-extrabold gold-gradient-text tracking-wider uppercase">Empress Two Way</span>
                    <span class="text-xs text-gold/70 block tracking-widest font-mono">VERSION 3.0</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-6 text-sm font-medium">
                <a href="/index.php" class="hover:text-gold transition">Home</a>
                <a href="/business_plan.php" class="hover:text-gold transition">Business Plan</a>
                <a href="/faq.php" class="hover:text-gold transition">FAQ</a>
                <?php if (isset($_SESSION['member_id'])): ?>
                    <a href="/customer/dashboard.php" class="text-gold font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                    <a href="/logout.php" class="text-red-400 hover:text-red-300">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php elseif (isset($_SESSION['superadmin_id'])): ?>
                    <a href="/superadmin/index.php" class="text-neon-cyan font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-crown"></i> Super Admin
                    </a>
                    <a href="/logout.php" class="text-red-400 hover:text-red-300">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <a href="/admin/index.php" class="text-gold font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-user-shield"></i> Admin Panel
                    </a>
                    <a href="/logout.php" class="text-red-400 hover:text-red-300">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="hover:text-gold transition">Member Login</a>
                    <a href="/admin_login.php" class="text-gold/70 hover:text-gold transition">Admin</a>
                    <a href="/superadmin_login.php" class="text-neon-cyan/80 hover:text-neon-cyan transition font-semibold">Super Admin</a>
                    <a href="/register.php" class="gold-button px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Join Now
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu trigger -->
            <button id="mobileMenuBtn" class="md:hidden text-gold text-2xl focus:outline-none">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobileMenu" class="hidden md:hidden mt-4 pt-4 border-t border-gold/20 flex flex-col gap-3 text-sm">
            <a href="/index.php" class="hover:text-gold py-1">Home</a>
            <a href="/business_plan.php" class="hover:text-gold py-1">Business Plan</a>
            <a href="/faq.php" class="hover:text-gold py-1">FAQ</a>
            <a href="/terms.php" class="hover:text-gold py-1">Terms & Conditions</a>
            <?php if (isset($_SESSION['member_id'])): ?>
                <a href="/customer/dashboard.php" class="text-gold font-bold py-1">Customer Dashboard</a>
                <a href="/logout.php" class="text-red-400 py-1">Logout</a>
            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <a href="/admin/index.php" class="text-gold font-bold py-1">Admin Panel</a>
                <a href="/logout.php" class="text-red-400 py-1">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="hover:text-gold py-1">Member Login</a>
                <a href="/admin_login.php" class="text-gold/70 hover:text-gold py-1">Admin Portal</a>
                <a href="/register.php" class="gold-button text-center py-2 rounded-lg font-bold">Join Now</a>
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
