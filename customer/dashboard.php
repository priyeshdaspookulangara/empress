<?php
$pageTitle = "Customer Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

// Fetch Member Details
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    header("Location: /login.php");
    exit();
}

// Fetch Wallet Details
$stmtW = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmtW->execute([$memberId]);
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_50' => 0.00, 'burfee_cart_wallet' => 0.00, 'charity_wallet' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

// Count Direct Referrals
$stmtRef = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmtRef->execute([$memberId]);
$directReferralsCount = (int)$stmtRef->fetchColumn();

// Sum Direct Referral Commission ($ USD)
$stmtRefInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND status = 'Credit'");
$stmtRefInc->execute([$memberId]);
$directReferralCommission = (float)$stmtRefInc->fetchColumn();

// Count Downline (up to 7 levels, customer-facing 6 levels)
$downline = getMemberDownline6Levels($pdo, $memberId);
$downlineCount = count($downline);

// Downline Counts by Level (Levels 1 to 6)
$downlineCountsByLevel = array_fill(1, 6, 0);
foreach ($downline as $d) {
    $lvl = (int)($d['matrix_level'] ?? 0);
    if ($lvl >= 1 && $lvl <= 6) {
        $downlineCountsByLevel[$lvl]++;
    }
}

// Sum Matrix Level Commissions ($ USD) - Gross & Net (50% Withdrawable User Cash)
$stmtMatInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE member_id = ? AND type LIKE 'Matrix_Income%' AND status = 'Credit'");
$stmtMatInc->execute([$memberId]);
$totalLevelCommissionGross = (float)$stmtMatInc->fetchColumn();
$totalLevelCommissionNet = round($totalLevelCommissionGross * 0.50, 2);

// Per-level breakdown (Levels 1 to 6)
$stmtLvlBreakdown = $pdo->prepare("SELECT type, COALESCE(SUM(amount), 0) as total FROM transactions WHERE member_id = ? AND type LIKE 'Matrix_Income_L%' AND status = 'Credit' GROUP BY type");
$stmtLvlBreakdown->execute([$memberId]);
$levelCommissionsMap = $stmtLvlBreakdown->fetchAll(PDO::FETCH_KEY_PAIR);

$levelCommissionsByLevel = [];
for ($i = 1; $i <= 6; $i++) {
    $grossLvl = (float)($levelCommissionsMap["Matrix_Income_L$i"] ?? 0.00);
    $levelCommissionsByLevel[$i] = [
        'gross' => $grossLvl,
        'net' => round($grossLvl * 0.50, 2)
    ];
}

// Total Approved Withdrawals ($ USD)
$stmtWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE member_id = ? AND status = 'Approved'");
$stmtWd->execute([$memberId]);
$totalWithdrawn = (float)$stmtWd->fetchColumn();

// Total Rebirths & Aggregated Rebirth Income
$totalRebirths = getMemberTotalRebirths($pdo, $memberId);
$rebirthData = getAggregateRebirthEarnings($pdo, $memberId);
$combinedTotalIncome = $wallet['balance'] + $rebirthData['total_balance'];
$combinedCustomerWallet = ($wallet['user_wallet_50'] ?? $wallet['user_wallet_60']) + $rebirthData['user_wallet_50'];

// Recent Transactions
$stmtTx = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY id DESC LIMIT 5");
$stmtTx->execute([$memberId]);
$recentTx = $stmtTx->fetchAll();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$referralLink = $protocol . $_SERVER['HTTP_HOST'] . "/register.php?sponsor=" . $memberId;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Top Welcome Header & Referral Link -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full border border-gold/40 flex items-center justify-center bg-gold/10 text-gold text-xl font-bold">
                    <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold gold-gradient-text"><?php echo htmlspecialchars($member['name']); ?></h1>
                    <div class="flex items-center gap-2 text-xs text-champagne/70 mt-0.5">
                        <span class="font-mono bg-gold/10 px-2 py-0.5 rounded text-gold border border-gold/20"><?php echo htmlspecialchars($member['member_id']); ?></span>
                        <span>•</span>
                        <span class="text-emerald-400 font-semibold"><i class="fa-solid fa-box"></i> <?php echo str_replace('_', ' ', $member['package_type']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-auto glass-card p-3 rounded-2xl border border-gold/20 flex items-center justify-between gap-3 text-xs">
            <div class="overflow-hidden">
                <span class="text-gold/70 text-[10px] block uppercase font-mono">Your Direct Referral Link</span>
                <input type="text" readonly value="<?php echo htmlspecialchars($referralLink); ?>" id="refInput" class="bg-transparent text-champagne font-mono text-xs focus:outline-none w-64 truncate">
            </div>
            <button onclick="copyRefLink()" class="gold-button px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap flex items-center gap-1">
                <i class="fa-solid fa-copy"></i> Copy
            </button>
        </div>
    </div>

    <!-- Account Status Banner -->
    <div class="glass-card p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
            <span class="text-xs text-emerald-300 font-semibold">Account Active! Payout withdrawals are enabled for your BEP-20 wallet.</span>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="text-champagne/70">KYC Status:</span>
            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase border <?php
                echo match($member['kyc_status']) {
                    'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                    'Submitted' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                    'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                    default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                };
            ?>"><?php echo htmlspecialchars($member['kyc_status']); ?></span>
        </div>
    </div>

    <!-- Overview Financial KPI Cards Grid -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-gold uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-chart-pie"></i> Financial & Earnings Overview
            </h2>
            <span class="text-[11px] text-champagne/60 font-mono">USD ($) Currency</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Earnings -->
            <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Total Income</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold">
                            <i class="fa-solid fa-sack-dollar text-base"></i>
                        </div>
                    </div>
                    <b class="text-2xl font-extrabold gold-gradient-text block">$<?php echo number_format($wallet['balance'], 2); ?></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-emerald-400 font-semibold">
                    <?php if ($rebirthData['total_balance'] > 0): ?>
                        Group Total: $<?php echo number_format($combinedTotalIncome, 2); ?> (incl. Rebirths)
                    <?php else: ?>
                        +100% Commission Solvency
                    <?php endif; ?>
                </div>
            </div>

            <!-- Customer Wallet (50%) -->
            <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-500/5 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-emerald-400 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Customer Wallet (50%)</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30 text-emerald-400">
                            <i class="fa-solid fa-wallet text-base"></i>
                        </div>
                    </div>
                    <b class="text-2xl font-extrabold text-emerald-400 block">$<?php echo number_format($wallet['user_wallet_50'] ?? $wallet['user_wallet_60'], 2); ?></b>
                </div>
                <div class="mt-3 pt-3 border-t border-emerald-500/20 text-[11px] text-emerald-300">
                    <?php if ($rebirthData['user_wallet_50'] > 0): ?>
                        Group Withdrawable: $<?php echo number_format($combinedCustomerWallet, 2); ?>
                    <?php else: ?>
                        Eligible for Payout Withdrawal
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rayon Cart & Charity Allocation -->
            <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Rayon Cart & Charity</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold">
                            <i class="fa-solid fa-hand-holding-heart text-base"></i>
                        </div>
                    </div>
                    <b class="text-2xl font-extrabold text-gold block">$<?php echo number_format(($wallet['burfee_cart_wallet'] ?? 0) + ($wallet['charity_wallet'] ?? 0), 2); ?></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/80 flex justify-between">
                    <span>Cart: <strong class="text-gold">$<?php echo number_format($wallet['burfee_cart_wallet'] ?? 0, 2); ?></strong></span>
                    <span>Charity: <strong class="text-emerald-400">$<?php echo number_format($wallet['charity_wallet'] ?? 0, 2); ?></strong></span>
                </div>
            </div>

            <!-- Total Payouts Withdrawn -->
            <a href="/customer/wallet.php" class="glass-card p-6 rounded-3xl border border-gold/30 hover:border-gold/60 transition group flex flex-col justify-between block">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70 group-hover:text-gold transition">Total Withdrawn</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold group-hover:scale-110 transition">
                            <i class="fa-solid fa-money-bill-transfer text-base"></i>
                        </div>
                    </div>
                    <b class="text-2xl font-extrabold text-champagne block group-hover:text-gold transition">$<?php echo number_format($totalWithdrawn, 2); ?></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/60 flex items-center justify-between">
                    <span>Approved Payouts</span>
                    <i class="fa-solid fa-arrow-right text-gold text-xs"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Referral & Level Commission KPI Details Section -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-gold uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-diagram-project"></i> Referral & Matrix Commission KPIs
            </h2>
            <span class="text-[11px] text-champagne/60 font-mono">Performance Metrics</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Direct Referrals Count -->
            <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Direct Referrals</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold">
                            <i class="fa-solid fa-user-plus text-base"></i>
                        </div>
                    </div>
                    <b class="text-3xl font-extrabold gold-gradient-text block"><?php echo $directReferralsCount; ?> <small class="text-xs font-normal text-champagne">Members</small></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/70 flex items-center justify-between">
                    <span>Sponsored by you</span>
                    <span class="font-mono text-emerald-400 font-bold">10% per join</span>
                </div>
            </div>

            <!-- Direct Referral Commission ($ USD) -->
            <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Direct Referral Income</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold">
                            <i class="fa-solid fa-coins text-base"></i>
                        </div>
                    </div>
                    <b class="text-3xl font-extrabold text-emerald-400 block">$<?php echo number_format($directReferralCommission, 2); ?></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/70 flex items-center justify-between">
                    <span>Direct 10% earnings</span>
                    <span class="font-mono text-gold font-bold">$1.00 / join</span>
                </div>
            </div>

            <!-- Level Commission / Matrix Income (Gross & Net 50% Display) -->
            <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Level Commission</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold">
                            <i class="fa-solid fa-layer-group text-base"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <b class="text-3xl font-extrabold gold-gradient-text">$<?php echo number_format($totalLevelCommissionGross, 2); ?></b>
                        <span class="text-[11px] font-mono font-bold uppercase text-champagne/60 bg-gold/10 px-2 py-0.5 rounded border border-gold/20">Gross</span>
                    </div>
                    <div class="text-xs font-semibold text-emerald-400 mt-1 font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-wallet text-[10px]"></i> Net Cash (50%): <strong>$<?php echo number_format($totalLevelCommissionNet, 2); ?></strong>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/70 flex items-center justify-between">
                    <span>Matrix Spillover (L1–L6)</span>
                    <span class="font-mono text-emerald-400 font-bold">50% Withdrawable</span>
                </div>
            </div>

            <!-- Total Matrix Downline & Rebirths -->
            <a href="/customer/rebirths.php" class="glass-card p-6 rounded-3xl border border-gold/30 hover:border-gold/60 transition group flex flex-col justify-between block">
                <div>
                    <div class="flex justify-between items-center text-gold mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-champagne/70 group-hover:text-gold transition">Matrix Network</span>
                        <div class="w-9 h-9 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-gold group-hover:scale-110 transition">
                            <i class="fa-solid fa-sitemap text-base"></i>
                        </div>
                    </div>
                    <b class="text-3xl font-extrabold text-champagne block group-hover:text-gold transition"><?php echo $downlineCount; ?> <small class="text-xs font-normal text-gold">Members</small></b>
                </div>
                <div class="mt-3 pt-3 border-t border-gold/10 text-[11px] text-champagne/70 flex items-center justify-between">
                    <span>Rebirth Nodes: <strong class="text-gold font-mono"><?php echo $totalRebirths; ?></strong></span>
                    <i class="fa-solid fa-arrow-right text-gold text-xs"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Level Commission & Network Performance Breakdown (Levels 1 to 6) -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
            <div>
                <h3 class="text-lg font-bold text-gold flex items-center gap-2">
                    <i class="fa-solid fa-bars-staggered"></i> Level Commission & Network Breakdown
                </h3>
                <p class="text-xs text-champagne/60 mt-0.5">Detailed breakdown of members and commission earnings across matrix Levels 1 through 6</p>
            </div>
            <a href="/customer/teams.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold text-xs px-3.5 py-1.5 rounded-xl font-bold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-sitemap"></i> View 3-Matrix Tree
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php for ($lvl = 1; $lvl <= 6; $lvl++):
                $nodeCount = $downlineCountsByLevel[$lvl];
                $grossEarned = $levelCommissionsByLevel[$lvl]['gross'];
                $netEarned = $levelCommissionsByLevel[$lvl]['net'];
                $maxCap = pow(3, $lvl);
            ?>
                <div class="bg-navy/70 p-4 rounded-2xl border border-gold/20 hover:border-gold/40 transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gold font-mono">Level <?php echo $lvl; ?></span>
                            <span class="text-[10px] text-champagne/50 font-mono">Max <?php echo number_format($maxCap); ?></span>
                        </div>
                        <div class="text-xl font-extrabold text-champagne"><?php echo $nodeCount; ?> <small class="text-[10px] text-champagne/60 font-normal">nodes</small></div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gold/10">
                        <div class="flex items-center justify-between text-[10px] text-champagne/60 mb-0.5">
                            <span class="uppercase">Gross:</span>
                            <span class="font-mono font-bold <?php echo $grossEarned > 0 ? 'text-gold' : 'text-champagne/40'; ?>">
                                +$<?php echo number_format($grossEarned, 2); ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] font-mono font-bold <?php echo $netEarned > 0 ? 'text-emerald-400' : 'text-champagne/40'; ?>">
                            <span class="text-[9px] uppercase tracking-wider text-champagne/70 font-sans">Net Cash:</span>
                            <span>+$<?php echo number_format($netEarned, 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Quick Quick Links Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <a href="/customer/deposit.php" class="glass-card p-6 rounded-2xl border border-emerald-500/30 hover:border-emerald-500/60 flex items-center justify-between group transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <h3 class="font-bold text-champagne">USDT Deposit</h3>
                    <p class="text-xs text-champagne/60">Deposit via BEP-20 network</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-emerald-400 text-sm group-hover:translate-x-1 transition"></i>
        </a>

        <a href="/customer/teams.php" class="glass-card p-6 rounded-2xl border border-gold/20 hover:border-gold/50 flex items-center justify-between group transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div>
                    <h3 class="font-bold text-champagne">Visual 3-Matrix Tree</h3>
                    <p class="text-xs text-champagne/60">Inspect your 7-level downline nodes</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gold text-sm group-hover:translate-x-1 transition"></i>
        </a>

        <a href="/customer/wallet.php" class="glass-card p-6 rounded-2xl border border-gold/20 hover:border-gold/50 flex items-center justify-between group transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
                <div>
                    <h3 class="font-bold text-champagne">Withdrawal & Logs</h3>
                    <p class="text-xs text-champagne/60">Request payout & view statement</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gold text-sm group-hover:translate-x-1 transition"></i>
        </a>

        <a href="/customer/profile.php" class="glass-card p-6 rounded-2xl border border-gold/20 hover:border-gold/50 flex items-center justify-between group transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <h3 class="font-bold text-champagne">Profile & Wallet</h3>
                    <p class="text-xs text-champagne/60">Manage account & payout wallet</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gold text-sm group-hover:translate-x-1 transition"></i>
        </a>
    </div>

    <!-- Recent Earning Transactions Log -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left"></i> Recent Transaction Activity
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">ID</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($recentTx)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-champagne/50">No transaction logs recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTx as $tx): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold/70">#<?php echo $tx['id']; ?></td>
                                <td class="p-3 font-semibold text-gold"><?php echo str_replace('_', ' ', $tx['type']); ?></td>
                                <td class="p-3 font-bold text-emerald-400">+$<?php echo number_format($tx['amount'], 2); ?></td>
                                <td class="p-3 text-champagne/80"><?php echo htmlspecialchars(str_replace('Burfee', 'Rayon', $tx['description'])); ?></td>
                                <td class="p-3 text-champagne/50 font-mono"><?php echo $tx['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyRefLink() {
    var copyText = document.getElementById("refInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Referral link copied to clipboard: " + copyText.value);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
