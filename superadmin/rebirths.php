<?php
$pageTitle = "Super Admin Rebirth Positions Inspector";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');

// Base Query
$sql = "
    SELECT m.*, mr.completed_level, mr.rebirth_count as reward_count
    FROM members m
    LEFT JOIN member_rebirths mr ON m.sponsor_id = mr.member_id
    WHERE (m.used_epin LIKE 'REBIRTH_%' OR m.name LIKE '%Rebirth%')
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.member_id LIKE ? OR m.sponsor_id LIKE ? OR m.placement_parent_id LIKE ? OR m.name LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rebirthNodes = $stmt->fetchAll();

// Total rebirth count system-wide
$totalRebirthsCount = count($rebirthNodes);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-neon-cyan bg-neon-cyan/10 px-3 py-1 rounded-full border border-neon-cyan/30">Super Admin Audit</span>
                <span class="text-xs text-ice/60">Empress Two Way 3.0</span>
            </div>
            <h1 class="text-2xl font-extrabold neon-gradient-text mt-2">Rebirth Positions Inspector</h1>
            <p class="text-xs text-ice/70 mt-1">Master audit of auto-created 3-matrix rebirth positions and 2nd generation sponsor assignments</p>
        </div>

        <!-- Search Bar -->
        <form action="/superadmin/rebirths.php" method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Rebirth ID, Sponsor, Parent..." class="bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2 text-xs text-ice focus:outline-none focus:border-neon-cyan w-64">
            <button type="submit" class="neon-button px-4 py-2 rounded-xl text-xs font-bold">Filter</button>
            <?php if (!empty($search)): ?>
                <a href="/superadmin/rebirths.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3 py-2 rounded-xl text-xs flex items-center">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary Banner -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h3 class="text-lg font-bold text-neon-cyan">Global System Rebirth Counter</h3>
            <p class="text-xs text-ice/70 mt-0.5">Automated positions generated across Levels 3, 4, 5, and 6 matrix completion events</p>
        </div>
        <div class="text-3xl font-extrabold text-neon-cyan font-mono">
            <?php echo $totalRebirthsCount; ?> <span class="text-xs font-normal text-ice/60">Active Nodes</span>
        </div>
    </div>

    <!-- Rebirth Nodes Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Rebirth ID</th>
                        <th class="p-3">Node Label</th>
                        <th class="p-3">Sponsor ID (Referral)</th>
                        <th class="p-3">Placement Parent ID</th>
                        <th class="p-3">Matrix Pos</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Created Date</th>
                        <th class="p-3">Inspect Tree</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($rebirthNodes)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-ice/50">No rebirth positions matching criteria found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rebirthNodes as $rn): ?>
                            <tr>
                                <td class="p-3 font-mono font-bold text-neon-cyan"><?php echo htmlspecialchars($rn['member_id']); ?></td>
                                <td class="p-3 font-semibold text-ice"><?php echo htmlspecialchars($rn['name']); ?></td>
                                <td class="p-3 font-mono">
                                    <span class="<?php echo $rn['sponsor_id'] === 'EMP100000' ? 'text-amber-400 font-bold' : 'text-emerald-400 font-bold'; ?>">
                                        <?php echo htmlspecialchars($rn['sponsor_id'] ?: 'EMP100000'); ?>
                                    </span>
                                    <?php if ($rn['sponsor_id'] === 'EMP100000'): ?>
                                        <span class="text-[9px] bg-amber-500/20 text-amber-300 border border-amber-500/40 px-1 py-0.5 rounded ml-1">2nd Gen (Root)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-ice/80"><?php echo htmlspecialchars($rn['placement_parent_id'] ?: 'EMP100000'); ?></td>
                                <td class="p-3 font-mono font-bold text-center text-neon-cyan">#<?php echo $rn['matrix_position'] ?: 1; ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                        <?php echo $rn['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $rn['created_at']; ?></td>
                                <td class="p-3">
                                    <a href="/superadmin/matrix_tree.php?member_id=<?php echo $rn['member_id']; ?>" class="neon-button px-2.5 py-1 rounded text-[10px] font-bold inline-flex items-center gap-1">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
