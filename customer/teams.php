<?php
$pageTitle = "My Network & 3-Matrix Tree";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

$treeData = getMemberMatrixTree($pdo, $memberId);
$downline = getMemberDownline6Levels($pdo, $memberId);
$totalRebirths = getMemberTotalRebirths($pdo, $memberId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">My 3-Matrix Downline Tree</h1>
            <p class="text-xs text-champagne/70 mt-1">Visual breakdown of your 3 direct slots and 6-level forced matrix downline</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-neon-cyan bg-neon-cyan/10 px-3 py-1.5 rounded-xl border border-neon-cyan/20 font-mono">
                Rebirth Positions: <?php echo $totalRebirths; ?>
            </span>
            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded-xl border border-emerald-500/20 font-mono">
                Total Downline: <?php echo count($downline); ?> Members
            </span>
        </div>
    </div>

    <!-- Visual 3x3 Tree Component -->
    <div class="glass-card p-6 md:p-10 rounded-3xl border border-gold/30 text-center overflow-x-auto custom-scrollbar">
        <h3 class="text-sm font-bold text-gold uppercase tracking-widest mb-8">Visual Matrix Tree Structure</h3>

        <div class="inline-block min-w-[700px] text-center">
            <!-- Root / Current User Node -->
            <div class="inline-block mb-8">
                <div class="w-24 h-24 rounded-2xl bg-gold/10 border-2 border-gold mx-auto flex flex-col items-center justify-center p-2 shadow-lg shadow-gold/20">
                    <i class="fa-solid fa-user-tie text-2xl text-gold mb-1"></i>
                    <span class="text-xs font-bold text-champagne truncate max-w-full"><?php echo htmlspecialchars($treeData['name']); ?></span>
                    <span class="text-[10px] text-gold/80 font-mono"><?php echo htmlspecialchars($treeData['member_id']); ?></span>
                </div>
                <div class="text-[10px] text-gold/60 mt-1 uppercase font-mono">You (Level 0)</div>
            </div>

            <!-- Connector Line Down -->
            <div class="w-1/2 mx-auto border-t-2 border-gold/40 h-6 relative -mt-4 mb-4">
                <div class="absolute -top-6 left-1/2 w-0.5 h-6 bg-gold/40 -translate-x-1/2"></div>
                <div class="absolute top-0 left-0 w-0.5 h-4 bg-gold/40"></div>
                <div class="absolute top-0 left-1/2 w-0.5 h-4 bg-gold/40 -translate-x-1/2"></div>
                <div class="absolute top-0 right-0 w-0.5 h-4 bg-gold/40"></div>
            </div>

            <!-- Level 1 Nodes (3 Slots) -->
            <div class="grid grid-cols-3 gap-6">
                <?php for ($pos = 1; $pos <= 3; $pos++): ?>
                    <?php $child = $treeData['children'][$pos] ?? null; ?>
                    <div class="flex flex-col items-center">
                        <?php if ($child): ?>
                            <div class="w-20 h-20 rounded-xl bg-obsidian border border-gold/50 flex flex-col items-center justify-center p-1.5 hover:border-gold transition">
                                <i class="fa-solid fa-user text-xl text-emerald-400 mb-1"></i>
                                <span class="text-[11px] font-bold text-champagne truncate w-full text-center"><?php echo htmlspecialchars($child['name']); ?></span>
                                <span class="text-[9px] text-gold/70 font-mono"><?php echo htmlspecialchars($child['member_id']); ?></span>
                            </div>
                            <span class="text-[10px] text-emerald-400 mt-1 font-semibold">Slot <?php echo $pos; ?> Active</span>

                            <!-- Level 2 Sub-Slots (3 under each L1 child) -->
                            <div class="w-full border-t border-gold/30 h-3 relative mt-3 mb-2">
                                <div class="absolute -top-3 left-1/2 w-px h-3 bg-gold/30"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-1 w-full">
                                <?php for ($subPos = 1; $subPos <= 3; $subPos++): ?>
                                    <?php $subChild = $child['children'][$subPos] ?? null; ?>
                                    <div class="text-center">
                                        <?php if ($subChild): ?>
                                            <div class="w-12 h-12 rounded-lg bg-gold/10 border border-gold/40 mx-auto flex flex-col items-center justify-center p-0.5" title="<?php echo htmlspecialchars($subChild['name'] . ' (' . $subChild['member_id'] . ')'); ?>">
                                                <i class="fa-solid fa-user text-xs text-gold"></i>
                                                <span class="text-[8px] text-champagne font-mono truncate w-full text-center"><?php echo htmlspecialchars($subChild['member_id']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg border border-dashed border-gold/20 mx-auto flex items-center justify-center text-[9px] text-gold/40">
                                                Empty
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>

                        <?php else: ?>
                            <div class="w-20 h-20 rounded-xl border border-dashed border-gold/30 bg-obsidian/40 flex flex-col items-center justify-center text-gold/40">
                                <i class="fa-solid fa-user-plus text-lg mb-1"></i>
                                <span class="text-[10px]">Open Slot <?php echo $pos; ?></span>
                            </div>
                            <span class="text-[10px] text-gold/40 mt-1">Available for Spillover</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- 6-Level Downline Member Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-ol"></i> 6-Level Detailed Downline Roster
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Matrix Depth</th>
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Parent Placement</th>
                        <th class="p-3">Sponsor</th>
                        <th class="p-3">Joined Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($downline)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-champagne/50">No downline members registered in your matrix yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($downline as $m): ?>
                            <tr>
                                <td class="p-3">
                                    <span class="bg-gold/10 text-gold px-2 py-0.5 rounded border border-gold/20 font-bold">Level <?php echo $m['matrix_level']; ?></span>
                                </td>
                                <td class="p-3 font-mono text-gold font-bold"><?php echo htmlspecialchars($m['member_id']); ?></td>
                                <td class="p-3 font-semibold text-champagne"><?php echo htmlspecialchars($m['name']); ?></td>
                                <td class="p-3"><?php echo str_replace('_', ' ', $m['package_type']); ?></td>
                                <td class="p-3 font-mono text-champagne/70"><?php echo htmlspecialchars($m['placement_parent_id']); ?></td>
                                <td class="p-3 font-mono text-champagne/70"><?php echo htmlspecialchars($m['sponsor_id']); ?></td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $m['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
