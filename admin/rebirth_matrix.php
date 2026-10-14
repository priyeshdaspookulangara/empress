<?php
// admin/rebirth_matrix.php
$pageTitle = "Dynamic Rebirth Overview & Qualification Matrix";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');

// Fetch all members who either have records in member_rebirths OR have active rebirth nodes
$sqlMembers = "
    SELECT DISTINCT m.member_id, m.name, m.email, m.phone, m.package_type, m.sponsor_id, m.placement_parent_id, m.created_at,
           w.balance as master_balance, w.user_wallet_50 as master_user_wallet
    FROM members m
    LEFT JOIN wallets w ON m.member_id = w.member_id
    WHERE m.member_id IN (
        SELECT DISTINCT member_id FROM member_rebirths
        UNION
        SELECT DISTINCT sponsor_id FROM members WHERE used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%'
    )
";
$params = [];

if (!empty($search)) {
    $sqlMembers .= " AND (m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sqlMembers .= " ORDER BY m.id DESC";

$stmtM = $pdo->prepare($sqlMembers);
$stmtM->execute($params);
$rebirthMembers = $stmtM->fetchAll();

// For each member, attach detailed rebirth breakdown & qualification criteria data
foreach ($rebirthMembers as &$rm) {
    $mId = $rm['member_id'];

    // Total rebirths awarded by schedule
    $rm['total_rewards_scheduled'] = getMemberTotalRebirths($pdo, $mId);

    // Active placed rebirth nodes
    $rm['rebirth_nodes'] = getMemberRebirthNodes($pdo, $mId);

    // Aggregated financial earnings across rebirth nodes
    $rm['rebirth_financials'] = getAggregateRebirthEarnings($pdo, $mId);

    // Level completion breakdown from member_rebirths with detailed qualification info
    $stmtLevels = $pdo->prepare("SELECT completed_level, rebirth_count, created_at FROM member_rebirths WHERE member_id = ? ORDER BY completed_level ASC");
    $stmtLevels->execute([$mId]);
    $achievements = $stmtLevels->fetchAll();

    foreach ($achievements as &$ach) {
        $lvl = $ach['completed_level'];
        $cap = MATRIX_LEVEL_CAPACITY[$lvl] ?? 0;
        $ach['required_capacity'] = $cap;
        $ach['qualification_rule'] = "Completed Level {$lvl} by filling {$cap}/{$cap} downline matrix nodes at depth {$lvl}. Granted " . number_format($ach['rebirth_count']) . " Rebirth Positions.";
    }
    unset($ach);

    $rm['level_achievements'] = $achievements;
}
unset($rm);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/30">Dynamic Portal</span>
                <span class="text-xs text-champagne/60">Reyon Global Impact</span>
            </div>
            <h1 class="text-2xl font-extrabold gold-gradient-text mt-2">Dynamic Rebirth Qualification Matrix</h1>
            <p class="text-xs text-champagne/70 mt-1">Detailed breakdown of members with rebirth positions, explicit level qualification criteria, achievement dates, and associated node earnings.</p>
        </div>

        <!-- Search Bar & Direct Links -->
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <form action="/admin/rebirth_matrix.php" method="GET" class="flex gap-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Member ID, Name..." class="bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne focus:outline-none focus:border-gold w-56">
                <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold">Search</button>
            </form>
            <a href="/admin/rebirths.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-list-check"></i> Rebirth Queue
            </a>
        </div>
    </div>

    <!-- Summary KPI Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-3xl border border-gold/20">
            <span class="text-xs font-bold text-champagne/70 uppercase">Qualified Members</span>
            <div class="text-3xl font-extrabold gold-gradient-text font-mono mt-1"><?php echo count($rebirthMembers); ?></div>
            <span class="text-[11px] text-champagne/50">Primary account holders with rebirths</span>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-emerald-500/30 bg-emerald-500/5">
            <span class="text-xs font-bold text-emerald-400 uppercase">Total Placed Rebirth Nodes</span>
            <div class="text-3xl font-extrabold text-emerald-400 font-mono mt-1">
                <?php
                $totNodes = 0;
                foreach ($rebirthMembers as $rm) $totNodes += count($rm['rebirth_nodes']);
                echo number_format($totNodes);
                ?>
            </div>
            <span class="text-[11px] text-champagne/50">Active matrix nodes created</span>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-purple-500/30 bg-purple-500/5">
            <span class="text-xs font-bold text-purple-300 uppercase">Combined Rebirth Wallet Share</span>
            <div class="text-3xl font-extrabold text-purple-300 font-mono mt-1">
                <?php
                $totRebirthEarnings = 0;
                foreach ($rebirthMembers as $rm) $totRebirthEarnings += $rm['rebirth_financials']['total_balance'];
                echo '$' . number_format($totRebirthEarnings, 2) . ' USD';
                ?>
            </div>
            <span class="text-[11px] text-champagne/50">Accumulated across rebirth nodes</span>
        </div>
    </div>

    <!-- Member Rebirth Cards List -->
    <div class="space-y-6">
        <?php if (empty($rebirthMembers)): ?>
            <div class="glass-card p-12 text-center rounded-3xl border border-gold/20 text-champagne/50">
                <i class="fa-solid fa-users-slash text-4xl mb-3 block text-gold/40"></i>
                No members with rebirth qualification records found in database.
            </div>
        <?php else: ?>
            <?php foreach ($rebirthMembers as $m): ?>
                <div class="glass-card p-6 rounded-3xl border border-gold/30 space-y-6">
                    <!-- Primary Member Header Row -->
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-gold/20 pb-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center font-extrabold text-xl">
                                <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-bold text-champagne"><?php echo htmlspecialchars($m['name']); ?></h2>
                                    <span class="font-mono bg-gold/10 px-2 py-0.5 rounded text-gold border border-gold/20 text-xs font-bold"><?php echo htmlspecialchars($m['member_id']); ?></span>
                                </div>
                                <p class="text-xs text-champagne/70 mt-0.5">
                                    Email: <span class="text-champagne font-mono"><?php echo htmlspecialchars($m['email']); ?></span> |
                                    Phone: <span class="text-champagne font-mono"><?php echo htmlspecialchars($m['phone']); ?></span> |
                                    Sponsor: <span class="text-gold font-mono"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'RGI100000'); ?></span> |
                                    Joined: <span class="text-champagne/60 font-mono"><?php echo date('M d, Y', strtotime($m['created_at'])); ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs font-mono">
                            <div class="bg-obsidian/80 px-3 py-2 rounded-xl border border-gold/20 text-center">
                                <span class="text-[10px] text-champagne/60 block uppercase">Primary Balance</span>
                                <strong class="text-gold font-bold">$<?php echo number_format($m['master_balance'], 2); ?> USD</strong>
                            </div>
                            <div class="bg-emerald-500/10 px-3 py-2 rounded-xl border border-emerald-500/30 text-center">
                                <span class="text-[10px] text-emerald-300 block uppercase">Rebirth Earnings</span>
                                <strong class="text-emerald-400 font-bold">$<?php echo number_format($m['rebirth_financials']['total_balance'], 2); ?> USD</strong>
                            </div>
                            <div class="bg-purple-500/10 px-3 py-2 rounded-xl border border-purple-500/30 text-center">
                                <span class="text-[10px] text-purple-300 block uppercase">Total Group Income</span>
                                <strong class="text-purple-300 font-bold">$<?php echo number_format($m['master_balance'] + $m['rebirth_financials']['total_balance'], 2); ?> USD</strong>
                            </div>
                            <a href="/admin/matrix_tree.php?member_id=<?php echo $m['member_id']; ?>" class="gold-button px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1">
                                <i class="fa-solid fa-sitemap"></i> Tree
                            </a>
                        </div>
                    </div>

                    <!-- Explicit Rebirth Qualification Breakdown Card -->
                    <div class="bg-slate-900/60 p-4 rounded-2xl border border-gold/20 space-y-3">
                        <h3 class="text-xs font-bold text-gold uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-award"></i> How Member Qualified For Rebirths (Level Milestone Records)
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <?php foreach ($m['level_achievements'] as $ach): ?>
                                <div class="p-3 bg-gold/10 border border-gold/30 rounded-xl space-y-1">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-gold flex items-center gap-1">
                                            <i class="fa-solid fa-trophy text-amber-400"></i> Level <?php echo $ach['completed_level']; ?> Qualified
                                        </span>
                                        <span class="font-mono text-[10px] text-champagne/60"><?php echo date('M d, Y', strtotime($ach['created_at'])); ?></span>
                                    </div>
                                    <p class="text-[11px] text-champagne/80 font-mono leading-tight">
                                        <?php echo htmlspecialchars($ach['qualification_rule']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Associated Rebirth Nodes Table -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xs font-bold text-gold uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-network-wired"></i> Placed Rebirth Nodes List (<?php echo count($m['rebirth_nodes']); ?> Nodes Placed)
                            </h3>
                            <span class="text-[11px] text-champagne/50">Placed into 3-matrix tree via global BFS spillover</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                                        <th class="p-2.5">Rebirth ID</th>
                                        <th class="p-2.5">Node Label</th>
                                        <th class="p-2.5">Placement Parent</th>
                                        <th class="p-2.5">Pos</th>
                                        <th class="p-2.5">Sponsor</th>
                                        <th class="p-2.5 text-emerald-400">Node Income</th>
                                        <th class="p-2.5">Created Date</th>
                                        <th class="p-2.5">Inspect</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gold/10 text-champagne font-mono">
                                    <?php if (empty($m['rebirth_nodes'])): ?>
                                        <tr>
                                            <td colspan="8" class="p-3 text-center text-champagne/50">No active rebirth nodes placed yet for this member.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($m['rebirth_nodes'] as $rn): ?>
                                            <tr class="hover:bg-gold/5">
                                                <td class="p-2.5 font-bold text-gold"><?php echo htmlspecialchars($rn['member_id']); ?></td>
                                                <td class="p-2.5 font-sans font-semibold text-champagne"><?php echo htmlspecialchars($rn['name']); ?></td>
                                                <td class="p-2.5 text-champagne/80"><?php echo htmlspecialchars($rn['placement_parent_id'] ?: 'RGI100000'); ?></td>
                                                <td class="p-2.5 text-center text-gold font-bold">#<?php echo $rn['matrix_position'] ?: 1; ?></td>
                                                <td class="p-2.5">
                                                    <span class="<?php echo $rn['sponsor_id'] === 'RGI100000' ? 'text-amber-400' : 'text-emerald-400'; ?>">
                                                        <?php echo htmlspecialchars($rn['sponsor_id'] ?: 'RGI100000'); ?>
                                                    </span>
                                                </td>
                                                <td class="p-2.5 text-emerald-400 font-bold">$<?php echo number_format($rn['wallet']['balance'] ?? 0, 2); ?> USD</td>
                                                <td class="p-2.5 text-champagne/50 text-[11px]"><?php echo $rn['created_at']; ?></td>
                                                <td class="p-2.5 font-sans">
                                                    <a href="/admin/matrix_tree.php?member_id=<?php echo $rn['member_id']; ?>" class="text-[10px] bg-gold/10 hover:bg-gold/20 text-gold border border-gold/30 px-2 py-0.5 rounded font-bold inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-sitemap"></i> Tree
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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
