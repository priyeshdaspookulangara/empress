<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4-Level Matrix Tree Print Sheet - <?php echo htmlspecialchars($targetId); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #ffffff;
            color: #111827;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 16px;
        }

        .node-card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-style: solid;
            word-break: break-all;
        }

        /* Dedicated Level-Specific Color Schemes for High Clarity Printouts */
        .node-root { background-color: #fef3c7; border-color: #f59e0b; color: #78350f; }
        .node-l1   { background-color: #e0f2fe; border-color: #0284c7; color: #0c4a6e; }
        .node-l2   { background-color: #f3e8ff; border-color: #9333ea; color: #581c87; }
        .node-l3   { background-color: #ccfbf1; border-color: #0d9488; color: #134e4a; }
        .node-l4   { background-color: #dcfce7; border-color: #16a34a; color: #14532d; }
        .node-empty { background-color: #f9fafb; border-color: #d1d5db; color: #9ca3af; border-style: dashed; }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                background-color: #ffffff !important;
            }
            .print-canvas {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
            @page {
                size: landscape;
                margin: 5mm;
            }
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 5px;
        }
    </style>
</head>
<body class="custom-scrollbar">

    <!-- Top Standalone Header Bar (Controls hidden on print) -->
    <header class="mb-6 p-4 bg-gray-900 text-white rounded-2xl shadow-md no-print flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-cyan-400 text-gray-900 flex items-center justify-center font-black text-xl">
                E3
            </div>
            <div>
                <h1 class="text-lg font-black tracking-wide text-cyan-400">EMPRESS TWO WAY 3.0</h1>
                <p class="text-xs text-gray-300">Super Admin - 4-Level Matrix Printable Structure Console</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form action="print_tree.php" method="GET" class="flex gap-2">
                <input type="text" name="member_id" value="<?php echo htmlspecialchars($targetId); ?>" placeholder="Member ID" class="bg-gray-800 border border-gray-700 rounded-xl px-3 py-1.5 text-xs text-cyan-300 uppercase font-mono focus:outline-none focus:border-cyan-400">
                <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-gray-900 font-bold px-3.5 py-1.5 rounded-xl text-xs transition">
                    Inspect
                </button>
            </form>

            <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-1.5 rounded-xl text-xs flex items-center gap-1.5 shadow transition">
                <i class="fa-solid fa-print"></i> Print Matrix Sheet
            </button>

            <a href="matrix_tree.php?member_id=<?php echo urlencode($targetId); ?>" class="bg-gray-800 border border-gray-700 hover:bg-gray-700 text-gray-200 px-3.5 py-1.5 rounded-xl text-xs transition">
                <i class="fa-solid fa-arrow-left"></i> Back to Inspector
            </a>
        </div>
    </header>

    <!-- Dedicated Print Header (Visible on screen and printed paper) -->
    <div class="border-b-2 border-gray-800 pb-4 mb-6 text-center">
        <h2 class="text-xl font-black text-gray-900 uppercase tracking-wider">Empress Two Way 3.0 - 4-Level 3-Matrix Network Tree</h2>
        <p class="text-xs text-gray-600 font-mono mt-1">
            Target Head Node: <strong class="text-gray-900 underline"><?php echo htmlspecialchars($treeData['name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($targetId); ?>)</strong>
            | Generated: <?php echo date('F j, Y, g:i a'); ?>
        </p>
    </div>

    <!-- Tree View Canvas -->
    <div class="overflow-x-auto print-canvas">
        <?php if (!$treeData): ?>
            <div class="text-center py-12 text-red-600 font-bold text-sm">Target Member ID not found in the matrix network.</div>
        <?php else: ?>
            <div class="min-w-[3400px] mx-auto text-center space-y-6 pb-6">

                <!-- LEVEL 0: TARGET HEAD NODE -->
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-amber-700 mb-1.5 no-print">Root Head Node (Level 0)</div>
                    <div class="inline-block">
                        <div class="node-card node-root px-8 py-3.5 rounded-2xl border-2 text-center min-w-[260px]">
                            <div class="text-sm font-extrabold truncate max-w-[280px] mx-auto"><?php echo htmlspecialchars($treeData['name']); ?></div>
                            <div class="text-xs font-mono font-black mt-0.5"><?php echo htmlspecialchars($treeData['member_id']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- LEVEL 1 (3 Slots) -->
                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-widest text-sky-700 no-print">Level 1 (3 Members)</div>
                    <div class="grid grid-cols-3 gap-6">
                        <?php for ($p1 = 1; $p1 <= 3; $p1++): ?>
                            <?php $nodeL1 = $treeData['children'][$p1] ?? null; ?>
                            <div class="p-3 rounded-2xl border border-gray-300 bg-gray-50/50">
                                <?php if ($nodeL1): ?>
                                    <div class="node-card node-l1 p-3 rounded-xl border text-center mb-3">
                                        <div class="text-xs font-bold truncate"><?php echo htmlspecialchars($nodeL1['name']); ?></div>
                                        <div class="text-[11px] font-mono font-bold mt-0.5"><?php echo htmlspecialchars($nodeL1['member_id']); ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="node-card node-empty p-3 rounded-xl text-center text-xs mb-3">
                                        Open Slot (L1-P<?php echo $p1; ?>)
                                    </div>
                                <?php endif; ?>

                                <!-- LEVEL 2 (9 Slots: 3 under each L1) -->
                                <div class="grid grid-cols-3 gap-3">
                                    <?php for ($p2 = 1; $p2 <= 3; $p2++): ?>
                                        <?php $nodeL2 = $nodeL1['children'][$p2] ?? null; ?>
                                        <div class="p-2 rounded-xl border border-gray-200 bg-white">
                                            <?php if ($nodeL2): ?>
                                                <div class="node-card node-l2 p-2 rounded-lg border text-center mb-2">
                                                    <div class="text-[11px] font-bold truncate" title="<?php echo htmlspecialchars($nodeL2['name']); ?>"><?php echo htmlspecialchars($nodeL2['name']); ?></div>
                                                    <div class="text-[10px] font-mono font-bold mt-0.5"><?php echo htmlspecialchars($nodeL2['member_id']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <div class="node-card node-empty p-2 rounded-lg text-center text-[10px] mb-2">
                                                    Open
                                                </div>
                                            <?php endif; ?>

                                            <!-- LEVEL 3 (27 Slots: 3 under each L2) -->
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php for ($p3 = 1; $p3 <= 3; $p3++): ?>
                                                    <?php $nodeL3 = $nodeL2['children'][$p3] ?? null; ?>
                                                    <div class="p-1.5 rounded-lg border border-gray-200 bg-gray-50">
                                                        <?php if ($nodeL3): ?>
                                                            <div class="node-card node-l3 p-1.5 rounded-md border text-center mb-1.5">
                                                                <div class="text-[10px] font-bold truncate" title="<?php echo htmlspecialchars($nodeL3['name']); ?>"><?php echo htmlspecialchars($nodeL3['name']); ?></div>
                                                                <div class="text-[9px] font-mono font-bold mt-0.5"><?php echo htmlspecialchars($nodeL3['member_id']); ?></div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="node-card node-empty p-1.5 rounded-md text-center text-[9px] mb-1.5">
                                                                -
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- LEVEL 4 (81 Slots: 3 under each L3) -->
                                                        <div class="grid grid-cols-3 gap-1">
                                                            <?php for ($p4 = 1; $p4 <= 3; $p4++): ?>
                                                                <?php $nodeL4 = $nodeL3['children'][$p4] ?? null; ?>
                                                                <?php if ($nodeL4): ?>
                                                                    <div class="node-card node-l4 p-1.5 rounded-md border text-center min-w-[32px]" title="<?php echo htmlspecialchars($nodeL4['name']) . ' (' . htmlspecialchars($nodeL4['member_id']) . ')'; ?>">
                                                                        <div class="text-[9px] font-bold truncate"><?php echo htmlspecialchars($nodeL4['name']); ?></div>
                                                                        <div class="text-[8px] font-mono font-black mt-0.5"><?php echo htmlspecialchars($nodeL4['member_id']); ?></div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="node-card node-empty p-1.5 rounded-md text-center text-[8px]">
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

</body>
</html>
