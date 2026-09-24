<?php
$pageTitle = "My Rebirth Positions";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

// Fetch Member Details
$stmtM = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmtM->execute([$memberId]);
$member = $stmtM->fetch();

if (!$member) {
    session_destroy();
    header("Location: /login.php");
    exit();
}

// Fetch Level Rebirth Rewards Earned
$stmtRewards = $pdo->prepare("SELECT * FROM member_rebirths WHERE member_id = ? ORDER BY completed_level ASC");
$stmtRewards->execute([$memberId]);
$rebirthRewards = $stmtRewards->fetchAll();

$totalRebirthsEarned = getMemberTotalRebirths($pdo, $memberId);

// Fetch All Rebirth Member Nodes created in Matrix for this user (by email/phone matching or sponsor_id/rebirth name pattern)
$stmtNodes = $pdo->prepare("
    SELECT * FROM members
    WHERE (email = ? AND (used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%'))
       OR (sponsor_id = ?)
    ORDER BY id ASC
");
$stmtNodes->execute([$member['email'], $memberId]);
$rebirthNodes = $stmtNodes->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/30">Matrix Auto-Multiplier</span>
                <span class="text-xs text-champagne/60">Empress Two Way 3.0</span>
            </div>
            <h1 class="text-2xl font-extrabold gold-gradient-text mt-2">My Rebirth Positions</h1>
            <p class="text-xs text-champagne/70 mt-1">Track automated rebirth positions generated from level matrix completions</p>
        </div>

        <a href="/customer/dashboard.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Rebirth KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-center">
        <div class="glass-card p-5 rounded-2xl border border-gold/30 md:col-span-1">
            <span class="text-[10px] uppercase font-bold text-gold tracking-wider block">Total Rebirths</span>
            <div class="text-3xl font-extrabold text-gold mt-1"><?php echo $totalRebirthsEarned; ?></div>
            <p class="text-[10px] text-champagne/50 mt-1">Active matrix positions</p>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-gold/20">
            <span class="text-[10px] uppercase font-semibold text-champagne/70 block">Level 3 Completion</span>
            <div class="text-xl font-bold text-emerald-400 mt-1">+10 Rebirths</div>
            <span class="text-[10px] text-champagne/40">27 Nodes Completed</span>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-gold/20">
            <span class="text-[10px] uppercase font-semibold text-champagne/70 block">Level 4 Completion</span>
            <div class="text-xl font-bold text-emerald-400 mt-1">+20 Rebirths</div>
            <span class="text-[10px] text-champagne/40">81 Nodes Completed</span>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-gold/20">
            <span class="text-[10px] uppercase font-semibold text-champagne/70 block">Level 5 Completion</span>
            <div class="text-xl font-bold text-emerald-400 mt-1">+70 Rebirths</div>
            <span class="text-[10px] text-champagne/40">243 Nodes Completed</span>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-gold/20">
            <span class="text-[10px] uppercase font-semibold text-champagne/70 block">Level 6 Completion</span>
            <div class="text-xl font-bold text-emerald-400 mt-1">+100 Rebirths</div>
            <span class="text-[10px] text-champagne/40">729 Nodes Completed</span>
        </div>
    </div>

    <!-- Rebirth Level Completion Log Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-base font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-award"></i> Level Completion Rebirth Rewards
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Completed Level</th>
                        <th class="p-3">Rebirth Count Granted</th>
                        <th class="p-3">Referral ID (Sponsor)</th>
                        <th class="p-3">Completion Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($rebirthRewards)): ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-champagne/50">No level completion rebirth rewards triggered yet. Complete Level 3 (27 downline nodes) to unlock 10 free rebirth positions!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rebirthRewards as $rr): ?>
                            <tr>
                                <td class="p-3 font-bold text-gold">Level <?php echo $rr['completed_level']; ?></td>
                                <td class="p-3 font-mono font-bold text-emerald-400">+<?php echo $rr['rebirth_count']; ?> Positions</td>
                                <td class="p-3 font-mono text-champagne/70"><?php echo htmlspecialchars($memberId); ?></td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $rr['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Rebirth Nodes Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-gold flex items-center gap-2">
                <i class="fa-solid fa-rotate"></i> Active Rebirth Nodes in Global Matrix Tree
            </h3>
            <span class="text-xs text-champagne/60 font-mono">Total Positions: <?php echo count($rebirthNodes); ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Rebirth ID</th>
                        <th class="p-3">Node Label</th>
                        <th class="p-3">Sponsor ID</th>
                        <th class="p-3">Placement Parent ID</th>
                        <th class="p-3">Matrix Position</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Created Date</th>
                        <th class="p-3">Tree Link</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($rebirthNodes)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-champagne/50">No rebirth positions active in tree yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rebirthNodes as $rn): ?>
                            <tr>
                                <td class="p-3 font-mono font-bold text-gold"><?php echo htmlspecialchars($rn['member_id']); ?></td>
                                <td class="p-3 font-semibold text-champagne"><?php echo htmlspecialchars($rn['name']); ?></td>
                                <td class="p-3 font-mono text-emerald-400 font-bold"><?php echo htmlspecialchars($rn['sponsor_id'] ?: 'EMP100000'); ?></td>
                                <td class="p-3 font-mono text-champagne/80"><?php echo htmlspecialchars($rn['placement_parent_id'] ?: 'EMP100000'); ?></td>
                                <td class="p-3 font-mono font-bold text-center text-gold">Pos #<?php echo $rn['matrix_position'] ?: 1; ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                        <?php echo $rn['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $rn['created_at']; ?></td>
                                <td class="p-3">
                                    <a href="/customer/teams.php?inspect=<?php echo $rn['member_id']; ?>" class="gold-button px-2.5 py-1 rounded text-[10px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-sitemap"></i> View Tree
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
