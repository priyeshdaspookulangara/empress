<?php
$pageTitle = "4-Level Matrix Tree (Print View)";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$targetId = trim($_GET['member_id'] ?? 'EMP100000');

// Verify target exists
$stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
$stmt->execute([$targetId]);
if (!$stmt->fetch()) {
    $targetId = 'EMP100000';
}

// Fetch tree data up to 5 levels (Target + 4 Downline Levels: L1, L2, L3, L4)
$treeData = getMemberMatrixTree($pdo, $targetId, 5);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Print-specific optimizations */
@media print {
    /* Hide Navigation Header, Sidebar, and non-printable action controls */
    nav, aside, .no-print {
        display: none !important;
    }

    /* Reset background and canvas for crisp black/white printing */
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 8pt !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    main {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .flex-grow, .w-full, .right-canvas {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .print-container {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    /* Node box print styles */
    .node-card {
        background: #ffffff !important;
        border: 1px solid #333333 !important;
        color: #000000 !important;
        box-shadow: none !important;
    }

    .node-card .node-name {
        color: #000000 !important;
        font-weight: bold !important;
    }

    .node-card .node-id {
        color: #333333 !important;
    }

    .empty-node {
        border: 1px dashed #aaaaaa !important;
        color: #888888 !important;
        background: #fafafa !important;
    }

    /* Force landscape paper orientation with minimum margin */
    @page {
        size: landscape;
        margin: 4mm;
    }
}

/* Custom Tree Node Styling */
.node-card {
    transition: all 0.2s ease;
    word-break: break-all;
}

.custom-scrollbar::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}
</style>

<div class="space-y-4 print-container">
    <!-- Non-printable top action bar -->
    <div class="glass-card p-4 rounded-2xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-xl font-extrabold text-neon-cyan flex items-center gap-2">
                <i class="fa-solid fa-print"></i> 4-Level Matrix Tree Printable View
            </h1>
            <p class="text-xs text-ice/70 mt-0.5">Showing Root + 4 Downline Matrix Levels (121 Max Nodes). Displaying Member Name and ID for all members.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="flex gap-2">
                <input type="text" name="member_id" value="<?php echo htmlspecialchars($targetId); ?>" placeholder="Member ID" class="bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-3 py-1.5 text-xs text-ice font-mono focus:outline-none focus:border-neon-cyan uppercase">
                <button type="submit" class="bg-neon-cyan/20 border border-neon-cyan text-neon-cyan hover:bg-neon-cyan/30 px-3 py-1.5 rounded-xl text-xs font-bold">Inspect</button>
            </form>

            <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-1.5 rounded-xl text-xs flex items-center gap-1.5 shadow-lg transition">
                <i class="fa-solid fa-print"></i> Print Matrix Tree
            </button>

            <a href="matrix_tree.php?member_id=<?php echo urlencode($targetId); ?>" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3 py-1.5 rounded-xl text-xs">
                <i class="fa-solid fa-sitemap"></i> Standard Inspector
            </a>
        </div>
    </div>

    <!-- Tree Layout Canvas -->
    <div class="glass-card p-4 md:p-6 rounded-2xl border border-neon-cyan/30 overflow-x-auto custom-scrollbar bg-obsidian/90">
        <!-- Print Header Title -->
        <div class="text-center mb-6 border-b border-neon-cyan/20 pb-3">
            <h2 class="text-lg font-black text-neon-cyan uppercase tracking-wider">Empress Two Way 3.0 - 4-Level 3-Matrix Structure</h2>
            <p class="text-xs text-ice font-mono">Target Head Node: <span class="text-neon-cyan font-bold"><?php echo htmlspecialchars($treeData['name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($targetId); ?>)</span> | Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <?php if (!$treeData): ?>
            <div class="text-center py-8 text-red-400 font-bold">Member node not found.</div>
        <?php else: ?>
            <div class="min-w-[3400px] mx-auto text-center space-y-6">

                <!-- LEVEL 0: TARGET HEAD NODE -->
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-neon-cyan mb-1.5 no-print">Root Node (Level 0)</div>
                    <div class="inline-block">
                        <div class="node-card px-6 py-3 rounded-2xl bg-neon-cyan/20 border-2 border-neon-cyan shadow-xl shadow-neon-cyan/20 text-center min-w-[240px]">
                            <div class="text-sm font-black text-ice node-name truncate max-w-[280px] mx-auto"><?php echo htmlspecialchars($treeData['name']); ?></div>
                            <div class="text-xs font-mono text-neon-cyan font-bold node-id mt-0.5"><?php echo htmlspecialchars($treeData['member_id']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- LEVEL 1 (3 Slots) -->
                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-widest text-neon-cyan/80 no-print">Level 1 (3 Nodes)</div>
                    <div class="grid grid-cols-3 gap-6">
                        <?php for ($p1 = 1; $p1 <= 3; $p1++): ?>
                            <?php $nodeL1 = $treeData['children'][$p1] ?? null; ?>
                            <div class="p-3 rounded-2xl border border-neon-cyan/30 bg-obsidian/60">
                                <?php if ($nodeL1): ?>
                                    <div class="node-card p-3 rounded-xl bg-neon-cyan/15 border border-neon-cyan/50 text-center mb-3">
                                        <div class="text-xs font-bold text-ice node-name truncate"><?php echo htmlspecialchars($nodeL1['name']); ?></div>
                                        <div class="text-[11px] font-mono text-neon-cyan font-semibold node-id mt-0.5"><?php echo htmlspecialchars($nodeL1['member_id']); ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="node-card empty-node p-3 rounded-xl border border-dashed border-neon-cyan/20 text-center text-xs text-neon-cyan/40 mb-3">
                                        Open Slot (L1-P<?php echo $p1; ?>)
                                    </div>
                                <?php endif; ?>

                                <!-- LEVEL 2 (9 Slots: 3 under each L1) -->
                                <div class="grid grid-cols-3 gap-3">
                                    <?php for ($p2 = 1; $p2 <= 3; $p2++): ?>
                                        <?php $nodeL2 = $nodeL1['children'][$p2] ?? null; ?>
                                        <div class="p-2 rounded-xl border border-neon-cyan/20 bg-black/30">
                                            <?php if ($nodeL2): ?>
                                                <div class="node-card p-2 rounded-lg bg-neon-cyan/10 border border-neon-cyan/40 text-center mb-2">
                                                    <div class="text-[11px] font-bold text-ice node-name truncate" title="<?php echo htmlspecialchars($nodeL2['name']); ?>"><?php echo htmlspecialchars($nodeL2['name']); ?></div>
                                                    <div class="text-[10px] font-mono text-neon-cyan node-id mt-0.5"><?php echo htmlspecialchars($nodeL2['member_id']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <div class="node-card empty-node p-2 rounded-lg border border-dashed border-neon-cyan/15 text-center text-[10px] text-neon-cyan/30 mb-2">
                                                    Open
                                                </div>
                                            <?php endif; ?>

                                            <!-- LEVEL 3 (27 Slots: 3 under each L2) -->
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php for ($p3 = 1; $p3 <= 3; $p3++): ?>
                                                    <?php $nodeL3 = $nodeL2['children'][$p3] ?? null; ?>
                                                    <div class="p-1.5 rounded-lg border border-neon-cyan/15 bg-black/40">
                                                        <?php if ($nodeL3): ?>
                                                            <div class="node-card p-1.5 rounded-md bg-neon-cyan/10 border border-neon-cyan/30 text-center mb-1.5">
                                                                <div class="text-[10px] font-bold text-ice node-name truncate" title="<?php echo htmlspecialchars($nodeL3['name']); ?>"><?php echo htmlspecialchars($nodeL3['name']); ?></div>
                                                                <div class="text-[9px] font-mono text-neon-cyan/90 node-id mt-0.5"><?php echo htmlspecialchars($nodeL3['member_id']); ?></div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="node-card empty-node p-1.5 rounded-md border border-dashed border-neon-cyan/10 text-center text-[9px] text-neon-cyan/30 mb-1.5">
                                                                -
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- LEVEL 4 (81 Slots: 3 under each L3) -->
                                                        <div class="grid grid-cols-3 gap-1">
                                                            <?php for ($p4 = 1; $p4 <= 3; $p4++): ?>
                                                                <?php $nodeL4 = $nodeL3['children'][$p4] ?? null; ?>
                                                                <?php if ($nodeL4): ?>
                                                                    <div class="node-card p-1.5 rounded-md bg-emerald-950/60 border border-emerald-500/50 text-center min-w-[32px]" title="<?php echo htmlspecialchars($nodeL4['name']) . ' (' . htmlspecialchars($nodeL4['member_id']) . ')'; ?>">
                                                                        <div class="text-[9px] font-bold text-emerald-300 node-name truncate font-sans"><?php echo htmlspecialchars($nodeL4['name']); ?></div>
                                                                        <div class="text-[8px] font-mono text-neon-cyan font-semibold node-id mt-0.5"><?php echo htmlspecialchars($nodeL4['member_id']); ?></div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="node-card empty-node p-1.5 rounded-md border border-dashed border-neon-cyan/15 text-center text-[8px] text-neon-cyan/30">
                                                                        .
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </div>

                                                    </div>
                                                <?php endfor; ?>
                                            </div>

                                        </div>
                                    <?php endfor; ?>
                                </div>

                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
