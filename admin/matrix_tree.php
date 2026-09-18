<?php
$pageTitle = "Matrix Tree Inspector";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
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

$treeData = getMemberMatrixTree($pdo, $targetId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Matrix Tree Inspector Console</h1>
            <p class="text-xs text-champagne/70 mt-1">Drill down into any node's 3-matrix tree across the entire company network</p>
        </div>

        <form action="/admin/matrix_tree.php" method="GET" class="flex gap-2">
            <input type="text" name="member_id" value="<?php echo htmlspecialchars($targetId); ?>" placeholder="EMP100001" class="bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne uppercase font-mono focus:outline-none focus:border-gold">
            <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold">Inspect Tree</button>
            <?php if ($targetId !== 'EMP100000'): ?>
                <a href="/admin/matrix_tree.php?member_id=EMP100000" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3 py-2 rounded-xl text-xs flex items-center">Reset Root</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tree View Card -->
    <div class="glass-card p-8 md:p-12 rounded-3xl border border-gold/30 text-center overflow-x-auto custom-scrollbar">
        <h3 class="text-sm font-bold text-gold uppercase tracking-widest mb-8">Inspecting Node Tree: <?php echo htmlspecialchars($targetId); ?></h3>

        <?php if (!$treeData): ?>
            <p class="text-xs text-red-400">Node not found.</p>
        <?php else: ?>
            <div class="inline-block min-w-[750px] text-center">
                <!-- Target Inspector Head Node -->
                <div class="inline-block mb-8">
                    <div class="w-24 h-24 rounded-2xl bg-gold/15 border-2 border-gold mx-auto flex flex-col items-center justify-center p-2 shadow-xl shadow-gold/20">
                        <i class="fa-solid fa-crown text-2xl text-gold mb-1"></i>
                        <span class="text-xs font-bold text-champagne truncate max-w-full"><?php echo htmlspecialchars($treeData['name']); ?></span>
                        <span class="text-[10px] text-gold font-mono"><?php echo htmlspecialchars($treeData['member_id']); ?></span>
                    </div>
                </div>

                <!-- Connector Line -->
                <div class="w-1/2 mx-auto border-t-2 border-gold/40 h-6 relative -mt-4 mb-4">
                    <div class="absolute -top-6 left-1/2 w-0.5 h-6 bg-gold/40 -translate-x-1/2"></div>
                    <div class="absolute top-0 left-0 w-0.5 h-4 bg-gold/40"></div>
                    <div class="absolute top-0 left-1/2 w-0.5 h-4 bg-gold/40 -translate-x-1/2"></div>
                    <div class="absolute top-0 right-0 w-0.5 h-4 bg-gold/40"></div>
                </div>

                <!-- Level 1 Children (Slots 1, 2, 3) -->
                <div class="grid grid-cols-3 gap-6">
                    <?php for ($pos = 1; $pos <= 3; $pos++): ?>
                        <?php $child = $treeData['children'][$pos] ?? null; ?>
                        <div class="flex flex-col items-center">
                            <?php if ($child): ?>
                                <a href="/admin/matrix_tree.php?member_id=<?php echo $child['member_id']; ?>" class="w-24 h-24 rounded-xl bg-obsidian border border-gold/50 flex flex-col items-center justify-center p-2 hover:border-gold hover:bg-gold/10 transition group">
                                    <i class="fa-solid fa-user text-xl text-emerald-400 mb-1 group-hover:scale-110 transition"></i>
                                    <span class="text-[11px] font-bold text-champagne truncate w-full text-center"><?php echo htmlspecialchars($child['name']); ?></span>
                                    <span class="text-[9px] text-gold/70 font-mono"><?php echo htmlspecialchars($child['member_id']); ?></span>
                                </a>
                                <span class="text-[10px] text-emerald-400 mt-1 font-semibold">Slot <?php echo $pos; ?> Active</span>

                                <!-- Level 2 Sub-slots -->
                                <div class="w-full border-t border-gold/30 h-3 relative mt-3 mb-2">
                                    <div class="absolute -top-3 left-1/2 w-px h-3 bg-gold/30"></div>
                                </div>
                                <div class="grid grid-cols-3 gap-1 w-full">
                                    <?php for ($subPos = 1; $subPos <= 3; $subPos++): ?>
                                        <?php $subChild = $child['children'][$subPos] ?? null; ?>
                                        <div class="text-center">
                                            <?php if ($subChild): ?>
                                                <a href="/admin/matrix_tree.php?member_id=<?php echo $subChild['member_id']; ?>" class="w-12 h-12 rounded-lg bg-gold/10 border border-gold/40 mx-auto flex flex-col items-center justify-center p-0.5 hover:border-gold transition block" title="Drill into <?php echo htmlspecialchars($subChild['member_id']); ?>">
                                                    <i class="fa-solid fa-user text-xs text-gold"></i>
                                                    <span class="text-[8px] text-champagne font-mono truncate w-full text-center"><?php echo htmlspecialchars($subChild['member_id']); ?></span>
                                                </a>
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-lg border border-dashed border-gold/20 mx-auto flex items-center justify-center text-[9px] text-gold/40">
                                                    Empty
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-xl border border-dashed border-gold/30 bg-obsidian/40 flex flex-col items-center justify-center text-gold/40">
                                    <i class="fa-solid fa-user-plus text-xl mb-1"></i>
                                    <span class="text-[10px]">Open Slot <?php echo $pos; ?></span>
                                </div>
                                <span class="text-[10px] text-gold/40 mt-1">Available for Spillover</span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
