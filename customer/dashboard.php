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
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

// Count Direct Referrals
$stmtRef = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmtRef->execute([$memberId]);
$directReferralsCount = $stmtRef->fetchColumn();

// Count Downline (up to 6 levels)
$downline = getMemberDownline6Levels($pdo, $memberId);
$downlineCount = count($downline);

// Total Rebirths
$totalRebirths = getMemberTotalRebirths($pdo, $memberId);

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

    <!-- Mandatory KYC Warning Banner if not approved -->
    <?php if ($member['kyc_status'] !== 'Approved'): ?>
        <div class="glass-card p-5 rounded-2xl border border-amber-500/40 bg-amber-500/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-shield-triangle-exclamation"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-300">Mandatory KYC Verification Required</h4>
                    <p class="text-xs text-champagne/80 mt-0.5">
                        Status: <strong class="uppercase text-amber-400"><?php echo htmlspecialchars($member['kyc_status']); ?></strong>. Submit address, PAN, Aadhaar & Bank Details to enable wallet withdrawals.
                    </p>
                </div>
            </div>
            <a href="/customer/profile.php" class="gold-button px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap">
                Complete KYC <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
            <span class="text-xs text-emerald-300 font-semibold">Your KYC is Fully Approved! Payout withdrawals are enabled.</span>
        </div>
    <?php endif; ?>

    <!-- Overview Financial Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Earnings -->
        <div class="glass-card p-6 rounded-3xl border border-gold/30">
            <div class="flex justify-between items-center text-gold mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Total Income</span>
                <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center border border-gold/20">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold gold-gradient-text">$<?php echo number_format($wallet['balance'], 2); ?></div>
            <div class="text-[11px] text-champagne/50 mt-2">Cumulative Matrix Commissions</div>
        </div>

        <!-- User Wallet (50%) -->
        <div class="glass-card p-6 rounded-3xl border border-emerald-500/30">
            <div class="flex justify-between items-center text-emerald-400 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Customer Wallet (50%)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400">$<?php echo number_format($wallet['user_wallet_60'], 2); ?></div>
            <div class="text-[11px] text-champagne/50 mt-2">Eligible for Payout Withdrawal</div>
        </div>

        <!-- Burfee Cart & Charity Allocation -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20">
            <div class="flex justify-between items-center text-gold mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70">Burfee Cart / Charity</span>
                <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center border border-gold/20">
                    <i class="fa-solid fa-vault"></i>
                </div>
            </div>
            <div class="text-xl font-extrabold text-gold">
                Cart: $<?php echo number_format($wallet['burfee_cart_wallet'] ?? 0, 2); ?>
            </div>
            <div class="text-[11px] text-champagne/70 mt-1">Charity: <span class="text-emerald-400 font-bold">$<?php echo number_format($wallet['charity_wallet'] ?? 0, 2); ?></span></div>
        </div>

        <!-- Team Downline Count & Rebirth Badges -->
        <a href="/customer/rebirths.php" class="glass-card p-6 rounded-3xl border border-gold/30 hover:border-neon-cyan transition block group">
            <div class="flex justify-between items-center text-gold mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-champagne/70 group-hover:text-neon-cyan transition">Matrix Network</span>
                <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center border border-gold/20 group-hover:border-neon-cyan transition">
                    <i class="fa-solid fa-rotate text-neon-cyan group-hover:rotate-180 transition duration-500"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-champagne"><?php echo $downlineCount; ?> <span class="text-xs font-normal text-gold">Members</span></div>
            <div class="text-[11px] text-neon-cyan font-bold mt-2 flex items-center gap-1">
                <i class="fa-solid fa-arrows-spin text-neon-cyan"></i> Total Rebirths Earned: <span class="text-emerald-400 font-mono text-sm"><?php echo $totalRebirths; ?></span>
            </div>
        </a>
    </div>

    <!-- Quick Quick Links Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/customer/teams.php" class="glass-card p-6 rounded-2xl border border-gold/20 hover:border-gold/50 flex items-center justify-between group transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div>
                    <h3 class="font-bold text-champagne">Visual 3-Matrix Tree</h3>
                    <p class="text-xs text-champagne/60">Inspect your 6-level downline nodes</p>
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
                    <h3 class="font-bold text-champagne">KYC & Bank Details</h3>
                    <p class="text-xs text-champagne/60">Manage account & verification</p>
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
                                <td class="p-3 text-champagne/80"><?php echo htmlspecialchars($tx['description']); ?></td>
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
