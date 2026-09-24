<?php
$pageTitle = "Executive Admin Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();

// System Metrics
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$activeMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'Active'")->fetchColumn();

// Total Company Inflow in USD ($)
$totalInflow = 0;
$usedEpins = $pdo->query("SELECT package_type FROM epins WHERE status = 'Used'")->fetchAll();
foreach ($usedEpins as $ep) {
    if ($ep['package_type'] === 'Empress_15000') {
        $totalInflow += 150;
    } elseif ($ep['package_type'] === 'Starter_5000') {
        $totalInflow += 50;
    } else {
        $totalInflow += 10;
    }
}

// Pending KYC count
$pendingKyc = $pdo->query("SELECT COUNT(*) FROM members WHERE kyc_status = 'Submitted'")->fetchColumn();

// Pending Withdrawals count & sum
$stmtW = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total FROM withdrawals WHERE status = 'Pending'");
$wData = $stmtW->fetch();
$pendingWithdrawalsCount = $wData['cnt'];
$pendingWithdrawalsSum = $wData['total'];

// Unused ePINs count
$unusedEpinsCount = $pdo->query("SELECT COUNT(*) FROM epins WHERE status = 'Unused'")->fetchColumn();

// Total Rebirths System Wide
$totalSystemRebirths = $pdo->query("SELECT COALESCE(SUM(rebirth_count), 0) FROM member_rebirths")->fetchColumn();

// Recent 5 Members Joined
$recentMembers = $pdo->query("SELECT * FROM members ORDER BY id DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Executive Title & Subnav -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-neon-cyan bg-neon-cyan/10 px-3 py-1 rounded-full border border-neon-cyan/30">Executive Dashboard</span>
                <span class="text-xs text-ice/60">Empress Two Way 3.0</span>
            </div>
            <h1 class="text-3xl font-extrabold neon-gradient-text mt-2">Master Administration Console</h1>
        </div>

        <!-- Quick Admin Nav Links -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="/superadmin/members.php" class="neon-button px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-users"></i> Members
            </a>
            <a href="/superadmin/matrix_tree.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-sitemap"></i> Matrix Tree
            </a>
            <a href="/superadmin/kyc.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3.5 py-2 rounded-xl flex items-center gap-1.5 relative">
                <i class="fa-solid fa-id-card"></i> KYC
                <?php if ($pendingKyc > 0): ?>
                    <span class="bg-red-500 text-white text-[9px] px-1.5 py-0.5 rounded-full font-bold"><?php echo $pendingKyc; ?></span>
                <?php endif; ?>
            </a>
            <a href="/superadmin/epins.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-key"></i> ePINs
            </a>
            <a href="/superadmin/wallet.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-money-bill-transfer"></i> Payouts
            </a>
            <a href="/superadmin/financials.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-chart-line"></i> Financials
            </a>
        </div>
    </div>

    <!-- Master Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Inflow -->
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/40">
            <div class="flex justify-between items-center text-neon-cyan mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70">Total Platform Inflow</span>
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/10 flex items-center justify-center border border-neon-cyan/30 text-xl">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold neon-gradient-text">$<?php echo number_format($totalInflow, 2); ?> USD</div>
            <p class="text-[11px] text-ice/50 mt-2">Gross subscription activation inflow</p>
        </div>

        <!-- Total Registered Members -->
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30">
            <div class="flex justify-between items-center text-neon-cyan mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70">Total Network Members</span>
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/10 flex items-center justify-center border border-neon-cyan/30 text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-ice"><?php echo $totalMembers; ?></div>
            <p class="text-[11px] text-ice/50 mt-2">Active: <span class="text-emerald-400 font-semibold"><?php echo $activeMembers; ?></span></p>
        </div>

        <!-- Pending KYC Approvals -->
        <div class="glass-card p-6 rounded-3xl border border-amber-500/40 bg-amber-500/5">
            <div class="flex justify-between items-center text-amber-400 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70">Pending KYC Submissions</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center border border-amber-500/30 text-xl">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-400"><?php echo $pendingKyc; ?></div>
            <p class="text-[11px] text-ice/50 mt-2"><a href="/superadmin/kyc.php" class="text-neon-cyan underline">Inspect pending KYC applications</a></p>
        </div>

        <!-- Pending Payout Requests -->
        <a href="/superadmin/wallet.php" class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-500/5 hover:border-emerald-400 transition block group">
            <div class="flex justify-between items-center text-emerald-400 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70 group-hover:text-emerald-400 transition">Pending Payout Requests</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30 text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400"><?php echo $pendingWithdrawalsCount; ?></div>
            <p class="text-[11px] text-ice/50 mt-2">Total requested: <span class="text-emerald-300 font-bold">$<?php echo number_format($pendingWithdrawalsSum, 2); ?> USD</span> (Click to Process)</p>
        </a>

        <!-- Total Rebirth Positions Created -->
        <a href="/superadmin/rebirths.php" class="glass-card p-6 rounded-3xl border border-neon-cyan/40 bg-neon-cyan/5 hover:border-neon-cyan transition block group">
            <div class="flex justify-between items-center text-neon-cyan mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70 group-hover:text-neon-cyan transition">Rebirth Positions</span>
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/20 flex items-center justify-center border border-neon-cyan/30 text-xl group-hover:rotate-180 transition duration-500">
                    <i class="fa-solid fa-rotate"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-neon-cyan"><?php echo $totalSystemRebirths; ?></div>
            <p class="text-[11px] text-ice/50 mt-2">Auto-created from L3-L6 completions (Click to Inspect)</p>
        </a>
    </div>

    <!-- Quick Action Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-neon-cyan mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-key"></i> Unused ePIN Inventory
                </h3>
                <p class="text-xs text-ice/70 mb-4">You have <strong class="text-neon-cyan"><?php echo $unusedEpinsCount; ?></strong> unused ePIN codes generated in stock.</p>
            </div>
            <a href="/superadmin/epins.php" class="neon-button py-2.5 rounded-xl text-xs text-center font-bold">
                Generate New ePIN Batch
            </a>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-neon-cyan mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-sitemap"></i> Tree Matrix Inspector
                </h3>
                <p class="text-xs text-ice/70 mb-4">Drill down into any member's forced 3-matrix downline tree up to 6 levels.</p>
            </div>
            <a href="/superadmin/matrix_tree.php" class="glass-card border border-neon-cyan/40 hover:bg-neon-cyan/10 text-neon-cyan py-2.5 rounded-xl text-xs text-center font-bold">
                Open Tree Inspector
            </a>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-neon-cyan mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie"></i> Financial Ledger & Splits
                </h3>
                <p class="text-xs text-ice/70 mb-4">Audit 50:50 smart wallet splits (50% Customer / Company: 60% Burfee Cart & 40% Charity).</p>
            </div>
            <a href="/superadmin/financials.php" class="glass-card border border-neon-cyan/40 hover:bg-neon-cyan/10 text-neon-cyan py-2.5 rounded-xl text-xs text-center font-bold">
                View Audit Ledger
            </a>
        </div>
    </div>

    <!-- Recent Joined Members Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-neon-cyan flex items-center gap-2">
                <i class="fa-solid fa-user-clock"></i> Recent Member Registrations
            </h3>
            <a href="/superadmin/members.php" class="text-xs text-neon-cyan hover:underline font-bold">View All Members →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Email & Phone</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Placement Parent</th>
                        <th class="p-3">KYC Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php foreach ($recentMembers as $m): ?>
                        <?php
                        $waMsg = rawurlencode("Welcome to Empress Two Way 3.0! Your Member ID is {$m['member_id']}. Login at " . $_SERVER['HTTP_HOST'] . "/login.php");
                        $waUrl = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $m['phone']) . "?text=" . $waMsg;
                        ?>
                        <tr>
                            <td class="p-3 font-mono text-neon-cyan font-bold"><?php echo htmlspecialchars($m['member_id']); ?></td>
                            <td class="p-3 font-semibold text-ice"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td class="p-3">
                                <span class="block"><?php echo htmlspecialchars($m['email']); ?></span>
                                <span class="font-mono text-ice/60 text-[11px]"><?php echo htmlspecialchars($m['phone']); ?></span>
                            </td>
                            <td class="p-3"><?php echo str_replace('_', ' ', $m['package_type']); ?></td>
                            <td class="p-3 font-mono text-ice/70"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'ROOT'); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                    echo match($m['kyc_status']) {
                                        'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                                        'Submitted' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                                        'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                                        default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                                    };
                                ?>">
                                    <?php echo $m['kyc_status']; ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <a href="<?php echo $waUrl; ?>" target="_blank" class="text-emerald-400 hover:text-emerald-300 font-bold flex items-center gap-1">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> Welcome
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
