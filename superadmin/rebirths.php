<?php
// superadmin/rebirths.php
$pageTitle = "System Rebirth Positions Inspector & Estimation Engine";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$error = '';
$search = trim($_GET['search'] ?? '');

// Handle Actions: Delete Rebirth, Manual Add, Recalculate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'clear_all_rebirths') {
        $res = clearAllRebirths($pdo);
        if ($res['success']) {
            $msg = $res['message'];
        } else {
            $error = $res['message'];
        }
    } elseif ($action === 'delete_rebirth_node') {
        $rebirthMemberId = trim($_POST['rebirth_member_id'] ?? '');
        if (!empty($rebirthMemberId)) {
            $res = deleteMemberSafely($pdo, $rebirthMemberId);
            if ($res['success']) {
                $msg = $res['message'];
            } else {
                $error = $res['message'];
            }
        }
    } elseif ($action === 'delete_queue_item') {
        $queueId = (int)($_POST['queue_id'] ?? 0);
        if ($queueId > 0) {
            $pdo->prepare("DELETE FROM queued_rebirths WHERE id = ?")->execute([$queueId]);
            $msg = "Queued rebirth item #{$queueId} deleted successfully.";
        }
    } elseif ($action === 'manual_trigger_rebirth') {
        $targetMemberId = strtoupper(trim($_POST['target_member_id'] ?? ''));
        $count = (int)($_POST['rebirth_count'] ?? 10);
        $level = (int)($_POST['level'] ?? 3);

        if (!empty($targetMemberId) && $count > 0 && $level >= 3) {
            $stmtM = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
            $stmtM->execute([$targetMemberId]);
            if ($stmtM->fetch()) {
                scheduleRebirthPositions($pdo, $targetMemberId, $count, $level);
                $msg = "Successfully scheduled {$count} manual rebirth positions for member {$targetMemberId} at Level {$level}.";
            } else {
                $error = "Target member ID {$targetMemberId} not found in database.";
            }
        } else {
            $error = "Invalid target member ID, count, or level.";
        }
    } elseif ($action === 'recalculate_rebirths') {
        $targetMemberId = strtoupper(trim($_POST['target_member_id'] ?? ''));
        if (!empty($targetMemberId)) {
            for ($lvl = 3; $lvl <= 7; $lvl++) {
                checkAndGrantLevelRebirths($pdo, $targetMemberId, $lvl);
            }
            $msg = "Matrix completion recalculated and eligible rebirths granted for member {$targetMemberId}.";
        }
    } elseif ($action === 'grant_batch_rebirths') {
        $approvedItems = $_POST['approved_items'] ?? [];
        $parsedItems = [];
        foreach ($approvedItems as $val) {
            $parts = explode('_', $val);
            if (count($parts) === 2) {
                $parsedItems[] = [
                    'member_id' => $parts[0],
                    'completed_level' => (int)$parts[1]
                ];
            }
        }
        if (!empty($parsedItems)) {
            $res = grantBatchApprovedRebirths($pdo, $parsedItems);
            if ($res['success']) {
                $msg = $res['message'];
            } else {
                $error = $res['message'];
            }
        } else {
            $error = "No eligible members selected for rebirth approval.";
        }
    }
}

// System-wide Rebirth Estimation Scan
$systemEligible = [];
$isScanTriggered = (isset($_GET['scan_eligible']) && $_GET['scan_eligible'] == '1');
if ($isScanTriggered) {
    $systemEligible = getSystemEligibleRebirthEstimates($pdo);
}

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

// Fetch Queue items
$stmtQ = $pdo->query("SELECT q.*, m.name as member_name FROM queued_rebirths q LEFT JOIN members m ON q.member_id = m.member_id ORDER BY q.id DESC LIMIT 50");
$queueItems = $stmtQ->fetchAll();

// Rebirth Estimate Console Data for a selected member
$estimateMemberId = strtoupper(trim($_GET['estimate_member_id'] ?? 'RGI100000'));
$stmtEstM = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmtEstM->execute([$estimateMemberId]);
$estMember = $stmtEstM->fetch();

$estimationData = [];
if ($estMember) {
    for ($lvl = 1; $lvl <= 7; $lvl++) {
        // Count downline members at level depth relative to estimateMemberId
        $currentLevelMembers = [$estimateMemberId];
        for ($l = 1; $l <= $lvl; $l++) {
            if (empty($currentLevelMembers)) break;
            $inClause = implode(',', array_fill(0, count($currentLevelMembers), '?'));
            $stmtL = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id IN ($inClause)");
            $stmtL->execute($currentLevelMembers);
            $currentLevelMembers = $stmtL->fetchAll(PDO::FETCH_COLUMN);
        }

        $capacity = MATRIX_LEVEL_CAPACITY[$lvl];
        $actualNodes = count($currentLevelMembers);
        $completionPct = min(100, round(($actualNodes / $capacity) * 100, 1));
        $rewardRebirths = REBIRTH_REWARDS[$lvl] ?? 0;
        $spec = MATRIX_LEVEL_SPECS[$lvl];
        $grossPotential = $capacity * $spec['gross_per_node'];
        $earnedGross = $actualNodes * $spec['gross_per_node'];

        $estimationData[$lvl] = [
            'capacity' => $capacity,
            'actual_nodes' => $actualNodes,
            'completion_pct' => $completionPct,
            'reward_rebirths' => $rewardRebirths,
            'gross_potential' => $grossPotential,
            'earned_gross' => $earnedGross,
            'spec' => $spec
        ];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-neon-cyan bg-neon-cyan/10 px-3 py-1 rounded-full border border-neon-cyan/30">System Audit</span>
                <span class="text-xs text-ice/60">Reyon Global Impact</span>
            </div>
            <h1 class="text-2xl font-extrabold neon-gradient-text mt-2">Rebirth Positions Inspector & Estimation Engine</h1>
            <p class="text-xs text-ice/70 mt-1">Audit auto-created 3-matrix rebirth positions, delete/recalculate nodes, and estimate rebirth earnings</p>
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

    <?php if (!empty($msg)): ?>
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-300 text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-base"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="glass-card p-4 rounded-2xl border border-red-500/40 bg-red-500/10 text-red-300 text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-base"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Rebirth Estimation Console -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-gold/20 pb-4">
            <div>
                <h3 class="text-lg font-bold text-gold flex items-center gap-2">
                    <i class="fa-solid fa-calculator"></i> Rebirth & Matrix Earnings Estimation Console
                </h3>
                <p class="text-xs text-champagne/70 mt-0.5">Select a member to estimate 7-level matrix completion percentage, gross payouts, and pending rebirth rewards.</p>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <a href="/superadmin/rebirths.php?scan_eligible=1" class="neon-button px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Estimate & Add Rebirths (System-Wide)
                </a>
                <form action="/superadmin/rebirths.php" method="GET" class="flex gap-2">
                    <input type="text" name="estimate_member_id" value="<?php echo htmlspecialchars($estimateMemberId); ?>" placeholder="e.g. RGI100000" required class="bg-obsidian border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne uppercase font-mono focus:outline-none focus:border-gold">
                    <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap">Estimate Member</button>
                </form>
            </div>
        </div>

        <?php if ($isScanTriggered): ?>
            <div class="glass-card p-6 rounded-3xl border border-neon-cyan/40 bg-neon-cyan/5 space-y-4">
                <div class="flex justify-between items-center border-b border-neon-cyan/20 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-neon-cyan flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-gold"></i> System-Wide Eligible Rebirth Approval Console
                        </h3>
                        <p class="text-xs text-ice/70 mt-0.5">Scanned matrix downline trees for all active members across Levels 3–7 eligible for ungranted rebirth positions.</p>
                    </div>
                    <a href="/superadmin/rebirths.php" class="text-xs text-ice/60 hover:text-ice underline">Close Approval Panel</a>
                </div>

                <?php if (empty($systemEligible)): ?>
                    <div class="p-4 bg-navy/60 rounded-2xl border border-neon-cyan/20 text-center text-ice/70 text-xs font-semibold">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-lg mb-1 block"></i>
                        All eligible rebirth positions across all active member accounts are currently up-to-date! No pending ungranted rebirths found.
                    </div>
                <?php else: ?>
                    <form action="/superadmin/rebirths.php" method="POST" onsubmit="return confirm('Approve and schedule rebirth positions for selected members?');">
                        <input type="hidden" name="action" value="grant_batch_rebirths">

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                                        <th class="p-3 text-center w-10">
                                            <input type="checkbox" id="selectAllRebirths" checked onclick="toggleAllRebirthCheckboxes(this);" class="rounded border-neon-cyan/30 bg-navy text-neon-cyan focus:ring-neon-cyan">
                                        </th>
                                        <th class="p-3">Member ID</th>
                                        <th class="p-3">Member Name</th>
                                        <th class="p-3">Completed Level</th>
                                        <th class="p-3">Actual Downline</th>
                                        <th class="p-3 text-purple-400">Rebirth Reward</th>
                                        <th class="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neon-cyan/10 text-ice font-mono">
                                    <?php foreach ($systemEligible as $eItem): ?>
                                        <?php $valKey = $eItem['member_id'] . '_' . $eItem['completed_level']; ?>
                                        <tr class="hover:bg-neon-cyan/5">
                                            <td class="p-3 text-center">
                                                <input type="checkbox" name="approved_items[]" value="<?php echo htmlspecialchars($valKey); ?>" checked class="rebirth-checkbox rounded border-neon-cyan/30 bg-navy text-neon-cyan focus:ring-neon-cyan">
                                            </td>
                                            <td class="p-3 font-bold text-neon-cyan"><?php echo htmlspecialchars($eItem['member_id']); ?></td>
                                            <td class="p-3 font-semibold text-ice font-sans"><?php echo htmlspecialchars($eItem['name']); ?></td>
                                            <td class="p-3 font-bold text-gold">Level <?php echo $eItem['completed_level']; ?></td>
                                            <td class="p-3 text-emerald-400 font-bold"><?php echo $eItem['actual_nodes']; ?> / <?php echo $eItem['capacity']; ?> Nodes</td>
                                            <td class="p-3 text-purple-400 font-bold"><?php echo number_format($eItem['rebirth_count']); ?> Rebirths</td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/40">Eligible (Pending Approval)</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t border-neon-cyan/20">
                            <span class="text-xs text-ice/70">Found <strong class="text-gold"><?php echo count($systemEligible); ?></strong> eligible level completion event(s).</span>
                            <button type="submit" class="gold-button px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                                <i class="fa-solid fa-circle-check"></i> Approve & Add Selected Rebirths
                            </button>
                        </div>
                    </form>

                    <script>
                    function toggleAllRebirthCheckboxes(master) {
                        var checkboxes = document.querySelectorAll('.rebirth-checkbox');
                        for (var i = 0; i < checkboxes.length; i++) {
                            checkboxes[i].checked = master.checked;
                        }
                    }
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($estMember): ?>
            <div class="flex items-center gap-4 bg-gold/10 p-4 rounded-2xl border border-gold/20 text-xs text-champagne">
                <div class="w-10 h-10 rounded-full bg-gold/20 text-gold flex items-center justify-center font-bold text-base border border-gold/40">
                    <?php echo strtoupper(substr($estMember['name'], 0, 1)); ?>
                </div>
                <div>
                    <h4 class="font-bold text-gold text-sm"><?php echo htmlspecialchars($estMember['name']); ?> (<span class="font-mono"><?php echo htmlspecialchars($estMember['member_id']); ?></span>)</h4>
                    <p class="text-[11px] text-champagne/70 mt-0.5">Sponsor: <span class="font-mono text-gold"><?php echo htmlspecialchars($estMember['sponsor_id'] ?: 'RGI100000'); ?></span> | Placement: <span class="font-mono text-gold"><?php echo htmlspecialchars($estMember['placement_parent_id'] ?: 'Root'); ?></span></p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                            <th class="p-3">Level</th>
                            <th class="p-3">Capacity</th>
                            <th class="p-3">Actual Downline</th>
                            <th class="p-3">Completion %</th>
                            <th class="p-3">Gross Potential</th>
                            <th class="p-3 text-emerald-400">Earned Gross</th>
                            <th class="p-3 text-purple-400">Rebirth Reward</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/10 text-champagne font-mono">
                        <?php foreach ($estimationData as $lvl => $e): ?>
                            <tr class="hover:bg-gold/5">
                                <td class="p-3 font-bold text-gold">Level <?php echo $lvl; ?></td>
                                <td class="p-3"><?php echo number_format($e['capacity']); ?> Nodes</td>
                                <td class="p-3 font-bold text-emerald-300"><?php echo number_format($e['actual_nodes']); ?> Nodes</td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-emerald-400 h-1.5 rounded-full" style="width: <?php echo $e['completion_pct']; ?>%"></div>
                                        </div>
                                        <span><?php echo $e['completion_pct']; ?>%</span>
                                    </div>
                                </td>
                                <td class="p-3">$<?php echo number_format($e['gross_potential'], 2); ?></td>
                                <td class="p-3 text-emerald-400 font-bold">$<?php echo number_format($e['earned_gross'], 2); ?></td>
                                <td class="p-3 text-purple-400 font-bold">
                                    <?php echo $e['reward_rebirths'] > 0 ? number_format($e['reward_rebirths']) . ' Rebirths' : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Action Form for Selected Member -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <form action="/superadmin/rebirths.php?estimate_member_id=<?php echo htmlspecialchars($estimateMemberId); ?>" method="POST" class="p-4 bg-slate-900/60 rounded-2xl border border-gold/20 flex items-center justify-between gap-3">
                    <input type="hidden" name="action" value="recalculate_rebirths">
                    <input type="hidden" name="target_member_id" value="<?php echo htmlspecialchars($estimateMemberId); ?>">
                    <div>
                        <h4 class="font-bold text-gold text-xs">Recalculate & Grant Rebirths</h4>
                        <p class="text-[10px] text-champagne/60">Check matrix completion and auto-grant eligible rebirth rewards.</p>
                    </div>
                    <button type="submit" class="gold-button px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap">Recalculate</button>
                </form>

                <form action="/superadmin/rebirths.php?estimate_member_id=<?php echo htmlspecialchars($estimateMemberId); ?>" method="POST" class="p-4 bg-slate-900/60 rounded-2xl border border-gold/20 flex items-center gap-2">
                    <input type="hidden" name="action" value="manual_trigger_rebirth">
                    <input type="hidden" name="target_member_id" value="<?php echo htmlspecialchars($estimateMemberId); ?>">
                    <div class="flex-1">
                        <h4 class="font-bold text-gold text-xs">Manual Add Rebirths</h4>
                        <div class="flex gap-2 mt-1">
                            <input type="number" name="rebirth_count" value="10" min="1" max="6561" class="w-20 bg-obsidian border border-gold/30 rounded px-2 py-1 text-xs text-champagne font-mono">
                            <select name="level" class="bg-obsidian border border-gold/30 rounded px-2 py-1 text-xs text-champagne font-mono">
                                <option value="3">Level 3 (10)</option>
                                <option value="4">Level 4 (20)</option>
                                <option value="5">Level 5 (70)</option>
                                <option value="6">Level 6 (100)</option>
                                <option value="7">Level 7 (6,561)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="gold-button px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap self-end">Add Rebirths</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Drip Queue Inspector Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold text-neon-cyan flex items-center gap-2">
                <i class="fa-solid fa-clock"></i> Scheduled Rebirth Drip Queue (<?php echo REBIRTH_INTERVAL_MINUTES; ?> Min Gap)
            </h3>
            <span class="text-xs text-ice/60 font-mono">Top 50 Queue Items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Queue ID</th>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Member Name</th>
                        <th class="p-3">Level</th>
                        <th class="p-3">Rebirth Index</th>
                        <th class="p-3">Scheduled Time</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($queueItems)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-ice/50">No queued rebirth items.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($queueItems as $qi): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan/70">#Q-<?php echo $qi['id']; ?></td>
                                <td class="p-3 font-mono font-bold text-neon-cyan"><?php echo htmlspecialchars($qi['member_id']); ?></td>
                                <td class="p-3 text-ice font-semibold"><?php echo htmlspecialchars($qi['member_name'] ?: 'Unknown'); ?></td>
                                <td class="p-3 font-bold text-gold">Level <?php echo $qi['completed_level']; ?></td>
                                <td class="p-3 font-mono font-bold text-emerald-400">#<?php echo $qi['rebirth_index']; ?></td>
                                <td class="p-3 font-mono text-neon-cyan/90"><?php echo $qi['scheduled_at']; ?></td>
                                <td class="p-3">
                                    <?php if ($qi['status'] === 'Processed'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">Processed</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-400 border border-amber-500/40 animate-pulse">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <form action="/superadmin/rebirths.php" method="POST" onsubmit="return confirm('Delete queue item #<?php echo $qi['id']; ?>?');">
                                        <input type="hidden" name="action" value="delete_queue_item">
                                        <input type="hidden" name="queue_id" value="<?php echo $qi['id']; ?>">
                                        <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-1 rounded text-[10px] font-bold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Banner -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h3 class="text-lg font-bold text-neon-cyan">Global System Rebirth Counter</h3>
            <p class="text-xs text-ice/70 mt-0.5">Automated positions generated across Levels 3, 4, 5, 6, and 7 matrix completion events</p>
        </div>
        <div class="flex items-center gap-4">
            <form action="/superadmin/rebirths.php" method="POST" onsubmit="return confirm('DANGER: Are you sure you want to CLEAR ALL REBIRTH POSITIONS and QUEUES system-wide? This will delete all rebirth nodes and queue logs while preserving primary customer accounts.');">
                <input type="hidden" name="action" value="clear_all_rebirths">
                <button type="submit" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/40 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-trash-can"></i> Clear All Rebirths
                </button>
            </form>
            <div class="text-3xl font-extrabold text-neon-cyan font-mono">
                <?php echo $totalRebirthsCount; ?> <span class="text-xs font-normal text-ice/60">Active Nodes</span>
            </div>
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
                        <th class="p-3">Action</th>
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
                                    <span class="<?php echo $rn['sponsor_id'] === 'RGI100000' ? 'text-amber-400 font-bold' : 'text-emerald-400 font-bold'; ?>">
                                        <?php echo htmlspecialchars($rn['sponsor_id'] ?: 'RGI100000'); ?>
                                    </span>
                                    <?php if ($rn['sponsor_id'] === 'RGI100000'): ?>
                                        <span class="text-[9px] bg-amber-500/20 text-amber-300 border border-amber-500/40 px-1 py-0.5 rounded ml-1">2nd Gen (Root)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-ice/80"><?php echo htmlspecialchars($rn['placement_parent_id'] ?: 'RGI100000'); ?></td>
                                <td class="p-3 font-mono font-bold text-center text-neon-cyan">#<?php echo $rn['matrix_position'] ?: 1; ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                        <?php echo $rn['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $rn['created_at']; ?></td>
                                <td class="p-3 flex items-center gap-2">
                                    <a href="/superadmin/matrix_tree.php?member_id=<?php echo $rn['member_id']; ?>" class="neon-button px-2.5 py-1 rounded text-[10px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-sitemap"></i> Tree
                                    </a>
                                    <form action="/superadmin/rebirths.php" method="POST" onsubmit="return confirm('Delete rebirth position <?php echo $rn['member_id']; ?>? Matrix children will be safely re-parented.');">
                                        <input type="hidden" name="action" value="delete_rebirth_node">
                                        <input type="hidden" name="rebirth_member_id" value="<?php echo $rn['member_id']; ?>">
                                        <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-1 rounded text-[10px] font-bold">
                                            Delete
                                        </button>
                                    </form>
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
