<?php
$pageTitle = "Financial Audit & Revenue Splits";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();

$filter = $_GET['filter'] ?? 'all';

// System Financial Totals in USD ($)
$stmtW = $pdo->query("
    SELECT
        COALESCE(SUM(balance), 0) as total_earnings,
        COALESCE(SUM(user_wallet_50), SUM(user_wallet_60)) as total_user_wallet,
        COALESCE(SUM(burfee_cart_wallet), 0) as total_burfee_cart,
        COALESCE(SUM(charity_wallet), 0) as total_charity,
        COALESCE(SUM(company_wallet_40), 0) as total_company_wallet
    FROM wallets
");
$wTotals = $stmtW->fetch();

$burfeeCartShare = $wTotals['total_burfee_cart'] > 0 ? $wTotals['total_burfee_cart'] : round($wTotals['total_company_wallet'] * 0.60, 2);
$charityShare = $wTotals['total_charity'] > 0 ? $wTotals['total_charity'] : round($wTotals['total_company_wallet'] * 0.40, 2);

// Total Approved Payouts
$totalApprovedPayouts = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'Approved'")->fetchColumn();

// Detailed Member Wallet Breakdown for Verification
$stmtMemberWallets = $pdo->query("
    SELECT w.*, m.name, m.email, m.package_type
    FROM wallets w
    JOIN members m ON w.member_id = m.member_id
    ORDER BY w.balance DESC
");
$memberWallets = $stmtMemberWallets->fetchAll();

// All Transactions Audit Ledger
$stmtTx = $pdo->query("
    SELECT t.*, m.name
    FROM transactions t
    LEFT JOIN members m ON t.member_id = m.member_id
    ORDER BY t.id DESC LIMIT 100
");
$allTransactions = $stmtTx->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">Financial Audit & Reserve Ledger</h1>
            <p class="text-xs text-ice/70 mt-1">Master USD ($) breakdown of 50:50 wallet distributions (50% Customer Wallet / 30% Burfee Cart / 20% Charity). Click any KPI card below to verify member-by-member calculations.</p>
        </div>
    </div>

    <!-- Breakdown Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <a href="?filter=all" class="glass-card p-6 rounded-3xl border transition block group <?php echo $filter === 'all' ? 'border-neon-cyan bg-neon-cyan/10 ring-2 ring-neon-cyan/50' : 'border-neon-cyan/40 hover:border-neon-cyan'; ?>">
            <span class="text-xs font-bold text-neon-cyan uppercase tracking-wider group-hover:underline">Total Commission Distributed</span>
            <div class="text-3xl font-extrabold neon-gradient-text mt-2">$<?php echo number_format($wTotals['total_earnings'], 2); ?> USD</div>
            <p class="text-[11px] text-ice/50 mt-1">Sum of all level payouts credited (Click to verify)</p>
        </a>

        <a href="?filter=user_wallet" class="glass-card p-6 rounded-3xl border transition block group <?php echo $filter === 'user_wallet' ? 'border-emerald-400 bg-emerald-500/10 ring-2 ring-emerald-500/50' : 'border-emerald-500/40 bg-emerald-500/5 hover:border-emerald-400'; ?>">
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider group-hover:underline">Customer Wallet (50%)</span>
            <div class="text-3xl font-extrabold text-emerald-400 mt-2">$<?php echo number_format($wTotals['total_user_wallet'], 2); ?> USD</div>
            <p class="text-[11px] text-ice/50 mt-1">Eligible for member withdrawals (Click to verify)</p>
        </a>

        <a href="?filter=burfee_cart" class="glass-card p-6 rounded-3xl border transition block group <?php echo $filter === 'burfee_cart' ? 'border-amber-400 bg-amber-500/10 ring-2 ring-amber-500/50' : 'border-amber-500/40 bg-amber-500/5 hover:border-amber-400'; ?>">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider group-hover:underline">Burfee Cart Wallet (30%)</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-2">$<?php echo number_format($burfeeCartShare, 2); ?> USD</div>
            <p class="text-[11px] text-ice/50 mt-1">60% of Company 50% allocation (Click to verify)</p>
        </a>

        <a href="?filter=charity" class="glass-card p-6 rounded-3xl border transition block group <?php echo $filter === 'charity' ? 'border-blue-400 bg-blue-500/10 ring-2 ring-blue-500/50' : 'border-blue-500/40 bg-blue-500/5 hover:border-blue-400'; ?>">
            <span class="text-xs font-bold text-blue-400 uppercase tracking-wider group-hover:underline">Empress Charity Fund (20%)</span>
            <div class="text-3xl font-extrabold text-blue-400 mt-2">$<?php echo number_format($charityShare, 2); ?> USD</div>
            <p class="text-[11px] text-ice/50 mt-1">40% of Company 50% allocation (Click to verify)</p>
        </a>
    </div>

    <!-- Calculation Verification Details Inspector -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 bg-slate-900/40">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-extrabold text-neon-cyan flex items-center gap-2">
                    <i class="fa-solid fa-calculator"></i> Calculation Verification Details:
                    <span class="text-ice uppercase">
                        <?php echo match($filter) {
                            'user_wallet' => 'Customer Wallet (50%)',
                            'burfee_cart' => 'Burfee Cart Wallet (30%)',
                            'charity' => 'Empress Charity Fund (20%)',
                            default => 'Total Commission Distributed'
                        }; ?>
                    </span>
                </h3>
                <p class="text-xs text-ice/60 mt-1">Itemized member wallet audit table to verify KPI sum accuracy ($ USD)</p>
            </div>
            <a href="?filter=all" class="text-xs text-neon-cyan hover:underline font-bold">Reset View</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Total Commission</th>
                        <th class="p-3">Customer (50%)</th>
                        <th class="p-3">Burfee Cart (30%)</th>
                        <th class="p-3">Charity (20%)</th>
                        <th class="p-3">Calculation Verification Formula</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($memberWallets)): ?>
                        <tr><td colspan="7" class="p-4 text-center text-ice/50">No member wallet records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($memberWallets as $mw): ?>
                            <?php
                            $b = (float)$mw['balance'];
                            $uw = (float)($mw['user_wallet_50'] > 0 ? $mw['user_wallet_50'] : $mw['user_wallet_60']);
                            $bc = (float)($mw['burfee_cart_wallet'] > 0 ? $mw['burfee_cart_wallet'] : round($mw['company_wallet_40'] * 0.60, 2));
                            $ch = (float)($mw['charity_wallet'] > 0 ? $mw['charity_wallet'] : round($mw['company_wallet_40'] * 0.40, 2));
                            ?>
                            <tr class="<?php echo ($filter === 'user_wallet' ? 'bg-emerald-500/5' : ($filter === 'burfee_cart' ? 'bg-amber-500/5' : ($filter === 'charity' ? 'bg-blue-500/5' : ''))); ?>">
                                <td class="p-3 font-mono font-bold text-neon-cyan"><?php echo htmlspecialchars($mw['member_id']); ?></td>
                                <td class="p-3 font-semibold text-ice"><?php echo htmlspecialchars($mw['name']); ?></td>
                                <td class="p-3 font-mono font-bold text-neon-gradient-text">$<?php echo number_format($b, 2); ?></td>
                                <td class="p-3 font-mono font-bold text-emerald-400">$<?php echo number_format($uw, 2); ?></td>
                                <td class="p-3 font-mono font-bold text-amber-400">$<?php echo number_format($bc, 2); ?></td>
                                <td class="p-3 font-mono font-bold text-blue-400">$<?php echo number_format($ch, 2); ?></td>
                                <td class="p-3 font-mono text-[11px] text-ice/70">
                                    $<?php echo number_format($b, 2); ?> × [50% ($<?php echo number_format($uw, 2); ?>) + 30% ($<?php echo number_format($bc, 2); ?>) + 20% ($<?php echo number_format($ch, 2); ?>)]
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approved Payout Metrics Card -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h3 class="text-lg font-bold text-neon-cyan">Total Approved Crypto Transfers</h3>
            <p class="text-xs text-ice/70 mt-0.5">Disbursed to members after mandatory KYC approval</p>
        </div>
        <div class="text-2xl font-extrabold text-emerald-400 font-mono">
            $<?php echo number_format($totalApprovedPayouts, 2); ?> USD
        </div>
    </div>

    <!-- Master Transaction Audit Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <h3 class="text-lg font-bold text-neon-cyan mb-4 flex items-center gap-2">
            <i class="fa-solid fa-book"></i> Master System Transaction Ledger
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Member ID & Name</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount ($ USD)</th>
                        <th class="p-3">Wallet</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($allTransactions)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-ice/50">No transactions recorded in system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allTransactions as $tx): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan/80">#TX-<?php echo $tx['id']; ?></td>
                                <td class="p-3">
                                    <div class="font-bold text-neon-cyan font-mono"><?php echo htmlspecialchars($tx['member_id']); ?></div>
                                    <div class="text-ice/70 text-[11px]"><?php echo htmlspecialchars($tx['name'] ?? ''); ?></div>
                                </td>
                                <td class="p-3 font-semibold text-neon-cyan"><?php echo str_replace('_', ' ', $tx['type']); ?></td>
                                <td class="p-3 font-bold <?php echo $tx['status'] === 'Credit' ? 'text-emerald-400' : 'text-red-400'; ?>">
                                    <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 2); ?>
                                </td>
                                <td class="p-3 font-mono text-ice/70"><?php echo $tx['wallet_type']; ?></td>
                                <td class="p-3 font-semibold"><?php echo $tx['status']; ?></td>
                                <td class="p-3 text-ice/80 max-w-xs"><?php echo htmlspecialchars($tx['description']); ?></td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $tx['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
