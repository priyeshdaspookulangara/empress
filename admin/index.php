<?php
$pageTitle = "Executive Admin Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();

// System Metrics
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$activeMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'Active'")->fetchColumn();

// Total Company Inflow calculated from activated ePINs (Starter 1000 = ₹1,000)
$totalInflow = 0;
$usedEpins = $pdo->query("SELECT package_type FROM epins WHERE status = 'Used'")->fetchAll();
foreach ($usedEpins as $ep) {
    if ($ep['package_type'] === 'Empress_15000') {
        $totalInflow += 15000;
    } elseif ($ep['package_type'] === 'Starter_5000') {
        $totalInflow += 5000;
    } else {
        $totalInflow += 1000;
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
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/30">Executive Dashboard</span>
                <span class="text-xs text-champagne/60">Empress Two Way 3.0</span>
            </div>
            <h1 class="text-3xl font-extrabold gold-gradient-text mt-2">Master Administration Console</h1>
        </div>

        <!-- Quick Admin Nav Links -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="/admin/members.php" class="gold-button px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-users"></i> Members
            </a>
            <a href="/admin/matrix_tree.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-sitemap"></i> Matrix Tree
            </a>
            <a href="/admin/kyc.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl flex items-center gap-1.5 relative">
                <i class="fa-solid fa-id-card"></i> KYC
                <?php if ($pendingKyc > 0): ?>
                    <span class="bg-red-500 text-white text-[9px] px-1.5 py-0.5 rounded-full font-bold"><?php echo $pendingKyc; ?></span>
                <?php endif; ?>
            </a>
            <a href="/admin/epins.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-key"></i> ePINs
            </a>
            <a href="/admin/wallet.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-money-bill-transfer"></i> Payouts
            </a>
            <a href="/admin/financials.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                <i class="fa-solid fa-chart-line"></i> Financials
            </a>
        </div>
    </div>

    <!-- Master Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Inflow -->
        <div class="glass-card p-6 rounded-3xl border border-gold/40">
            <div class="flex justify-between items-center text-gold mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Total Platform Inflow</span>
                <div class="w-10 h-10 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-xl">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold gold-gradient-text">₹<?php echo number_format($totalInflow, 2); ?></div>
            <p class="text-[11px] text-champagne/50 mt-2">Gross subscription activation inflow</p>
        </div>

        <!-- Total Registered Members -->
        <div class="glass-card p-6 rounded-3xl border border-gold/30">
            <div class="flex justify-between items-center text-gold mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Total Network Members</span>
                <div class="w-10 h-10 rounded-xl bg-gold/10 flex items-center justify-center border border-gold/30 text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-champagne"><?php echo $totalMembers; ?></div>
            <p class="text-[11px] text-champagne/50 mt-2">Active: <span class="text-emerald-400 font-semibold"><?php echo $activeMembers; ?></span></p>
        </div>

        <!-- Pending KYC Approvals -->
        <div class="glass-card p-6 rounded-3xl border border-amber-500/40 bg-amber-500/5">
            <div class="flex justify-between items-center text-amber-400 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Pending KYC Submissions</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center border border-amber-500/30 text-xl">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-400"><?php echo $pendingKyc; ?></div>
            <p class="text-[11px] text-champagne/50 mt-2"><a href="/admin/kyc.php" class="text-gold underline">Inspect pending KYC applications</a></p>
        </div>

        <!-- Pending Payout Requests -->
        <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-500/5">
            <div class="flex justify-between items-center text-emerald-400 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Pending Payout Requests</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30 text-xl">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400"><?php echo $pendingWithdrawalsCount; ?></div>
            <p class="text-[11px] text-champagne/50 mt-2">Total requested: <span class="text-emerald-300 font-bold">₹<?php echo number_format($pendingWithdrawalsSum, 2); ?></span></p>
        </div>

        <!-- Total Rebirth Positions Created -->
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/40 bg-neon-cyan/5">
            <div class="flex justify-between items-center text-neon-cyan mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-ice/70">Rebirth Positions</span>
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/20 flex items-center justify-center border border-neon-cyan/30 text-xl">
                    <i class="fa-solid fa-rotate"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-neon-cyan"><?php echo $totalSystemRebirths; ?></div>
            <p class="text-[11px] text-ice/50 mt-2">Auto-created from L3-L6 completions</p>
        </div>
    </div>

    <!-- Quick Action Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-gold mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-key"></i> Unused ePIN Inventory
                </h3>
                <p class="text-xs text-champagne/70 mb-4">You have <strong class="text-gold"><?php echo $unusedEpinsCount; ?></strong> unused ePIN codes generated in stock.</p>
            </div>
            <a href="/admin/epins.php" class="gold-button py-2.5 rounded-xl text-xs text-center font-bold">
                Generate New ePIN Batch
            </a>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-gold mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-sitemap"></i> Tree Matrix Inspector
                </h3>
                <p class="text-xs text-champagne/70 mb-4">Drill down into any member's forced 3-matrix downline tree up to 6 levels.</p>
            </div>
            <a href="/admin/matrix_tree.php" class="glass-card border border-gold/40 hover:bg-gold/10 text-gold py-2.5 rounded-xl text-xs text-center font-bold">
                Open Tree Inspector
            </a>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-gold mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie"></i> Financial Ledger & Splits
                </h3>
                <p class="text-xs text-champagne/70 mb-4">Audit 60:40 smart wallet splits and company retains vs 20% charity distribution.</p>
            </div>
            <a href="/admin/financials.php" class="glass-card border border-gold/40 hover:bg-gold/10 text-gold py-2.5 rounded-xl text-xs text-center font-bold">
                View Audit Ledger
            </a>
        </div>
    </div>

    <!-- Recent Joined Members Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gold flex items-center gap-2">
                <i class="fa-solid fa-user-clock"></i> Recent Member Registrations
            </h3>
            <a href="/admin/members.php" class="text-xs text-gold hover:underline font-bold">View All Members →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Email & Phone</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Placement Parent</th>
                        <th class="p-3">KYC Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php foreach ($recentMembers as $m): ?>
                        <?php
                        $waMsg = rawurlencode("Welcome to Empress Two Way 3.0! Your Member ID is {$m['member_id']}. Login at " . $_SERVER['HTTP_HOST'] . "/login.php");
                        $waUrl = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $m['phone']) . "?text=" . $waMsg;
                        ?>
                        <tr>
                            <td class="p-3 font-mono text-gold font-bold"><?php echo htmlspecialchars($m['member_id']); ?></td>
                            <td class="p-3 font-semibold text-champagne"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td class="p-3">
                                <span class="block"><?php echo htmlspecialchars($m['email']); ?></span>
                                <span class="font-mono text-champagne/60 text-[11px]"><?php echo htmlspecialchars($m['phone']); ?></span>
                            </td>
                            <td class="p-3"><?php echo str_replace('_', ' ', $m['package_type']); ?></td>
                            <td class="p-3 font-mono text-champagne/70"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'ROOT'); ?></td>
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
