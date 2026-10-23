<?php
$pageTitle = "Rearrange BFS Autofill Matrix Placement";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();

$actionResult = null;
$errorMsg = '';

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'rearrange') {
        $confirm = isset($_POST['confirm_reset']) ? true : false;
        if (!$confirm) {
            $errorMsg = "Please check the confirmation box before executing BFS matrix placement rearrangement.";
        } else {
            $actionResult = rearrangeBFSMatrixPlacement($pdo);
        }
    }
}

// Perform current audit
$audit = auditBFSMatrixPlacement($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Rearrange BFS Autofill Placement (Spillover)</h1>
            <p class="text-xs text-champagne/70 mt-1">
                Inspect 3-Matrix tree alignment, verify financial commission integrity, and rearrange global BFS auto-spillover placement.
            </p>
        </div>
        <a href="/admin/matrix_tree.php" class="pill text-xs font-bold border-gold/40 hover:border-gold">
            <i class="fa-solid fa-sitemap mr-1"></i> View Matrix Tree
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-2xl text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-400 text-sm"></i>
            <span><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($actionResult): ?>
        <div class="glass-card p-6 rounded-3xl border <?php echo $actionResult['success'] ? 'border-emerald-500/40 bg-emerald-950/10' : 'border-red-500/40 bg-red-950/10'; ?> space-y-3">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?php echo $actionResult['success'] ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-red-400'; ?> text-xl"></i>
                <h3 class="text-base font-bold <?php echo $actionResult['success'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                    <?php echo $actionResult['success'] ? 'BFS Matrix Placement Successfully Rearranged!' : 'Rearrangement Failed'; ?>
                </h3>
            </div>
            <p class="text-xs text-champagne/80 font-medium">
                <?php echo htmlspecialchars($actionResult['message']); ?>
            </p>
            <?php if ($actionResult['success']): ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs font-mono">
                    <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                        <span class="text-[10px] text-champagne/60 uppercase block">Cleared Matrix Amounts</span>
                        <span class="text-gold font-bold text-sm">$<?php echo number_format($actionResult['cleared_matrix_income'], 2); ?> USD</span>
                    </div>
                    <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                        <span class="text-[10px] text-champagne/60 uppercase block">Re-Placed Accounts</span>
                        <span class="text-emerald-400 font-bold text-sm"><?php echo $actionResult['replaced_count']; ?> Members</span>
                    </div>
                    <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                        <span class="text-[10px] text-champagne/60 uppercase block">Post-Audit Status</span>
                        <span class="<?php echo $actionResult['post_audit']['is_clean'] ? 'text-emerald-400' : 'text-amber-400'; ?> font-bold text-sm">
                            <?php echo $actionResult['post_audit']['is_clean'] ? '100% CLEAN (OK)' : 'MISTAKES REMAINING'; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Step 1: Matrix Audit & Integrity Inspection -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20 space-y-5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gold/20 pb-3 gap-3">
            <h3 class="text-base font-bold text-gold flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check"></i> Step 1: Matrix Placement Integrity Check
            </h3>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider font-mono <?php echo $audit['is_clean'] ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-400 border border-amber-500/40'; ?>">
                Status: <?php echo $audit['is_clean'] ? 'OK (Everything Correct)' : 'MISTAKES / DISCREPANCIES DETECTED (' . $audit['issues_count'] . ')'; ?>
            </span>
        </div>

        <!-- Audit Summary Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono">
            <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                <span class="text-[10px] text-champagne/60 block">Total Active Members</span>
                <span class="text-champagne font-bold text-sm"><?php echo $audit['stats']['total_active_members']; ?></span>
            </div>
            <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                <span class="text-[10px] text-champagne/60 block">Non-Root Accounts</span>
                <span class="text-gold font-bold text-sm"><?php echo $audit['stats']['total_non_root_members']; ?></span>
            </div>
            <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                <span class="text-[10px] text-champagne/60 block">Rebirth Position Nodes</span>
                <span class="text-emerald-400 font-bold text-sm"><?php echo $audit['stats']['total_rebirth_nodes']; ?></span>
            </div>
            <div class="bg-obsidian/60 p-3 rounded-xl border border-gold/20">
                <span class="text-[10px] text-champagne/60 block">Matrix Commissions Due</span>
                <span class="text-gold font-bold text-sm">$<?php echo number_format($audit['stats']['total_matrix_commissions_distributed'], 2); ?></span>
            </div>
        </div>

        <!-- Issues & Discrepancies List -->
        <?php if (!$audit['is_clean']): ?>
            <div class="bg-amber-500/10 border border-amber-500/30 p-4 rounded-2xl space-y-2">
                <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i> Identified Mistakes & Tree Discrepancies (<?php echo $audit['issues_count']; ?>)
                </h4>
                <ul class="list-disc list-inside text-xs text-champagne/90 space-y-1 font-mono">
                    <?php foreach ($audit['issues'] as $issue): ?>
                        <li><?php echo htmlspecialchars($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 p-4 rounded-2xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                <span class="text-xs font-semibold text-emerald-300">
                    Everything is right! No orphan nodes, capacity overflows, or placement position collisions were found in the current 3-Matrix tree.
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Step 2: Confirmation & Reset Rearrangement -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 space-y-5 bg-gradient-to-b from-obsidian/80 to-gold/5">
        <h3 class="text-base font-bold text-gold border-b border-gold/20 pb-3 flex items-center gap-2">
            <i class="fa-solid fa-rotate"></i> Step 2: Rearrange & Reset BFS Placement
        </h3>

        <div class="bg-gold/10 border border-gold/30 p-4 rounded-2xl space-y-2 text-xs text-champagne/90 leading-relaxed">
            <p class="font-bold text-gold flex items-center gap-2">
                <i class="fa-solid fa-circle-info"></i> Notice Regarding Resetting Placement:
            </p>
            <ul class="list-disc list-inside space-y-1.5 pl-1">
                <li>Resetting placement re-assigns <code class="text-gold font-mono">placement_parent_id</code> and <code class="text-gold font-mono">matrix_position</code> (1, 2, 3) for all active non-root member accounts in strict level-by-level BFS auto-spillover sequence starting from Root <code class="text-gold font-mono">RGI100000</code>.</li>
                <li><strong>All amounts due to 3-Matrix placement are cleared before resetting</strong> (matrix income entries, rebirth rewards, and matrix commission wallet totals are cleared and re-calculated).</li>
                <li>Matrix commissions for all re-placed accounts are re-distributed in sequence according to the updated BFS placement order, ensuring complete financial ledger alignment.</li>
            </ul>
        </div>

        <form method="POST" onsubmit="return confirm('ARE YOU SURE? This will clear all 3-matrix placement amounts due, re-assign placement parent IDs in strict BFS order, and re-distribute matrix commissions. Proceed?');" class="space-y-4">
            <input type="hidden" name="action" value="rearrange">

            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-obsidian/80 border border-gold/30">
                <input type="checkbox" name="confirm_reset" id="confirm_reset" value="1" class="mt-1 accent-gold w-4 h-4 cursor-pointer">
                <label for="confirm_reset" class="text-xs text-champagne font-semibold cursor-pointer">
                    I confirm that I want to clear all amounts due to 3-matrix placement, re-assign Placement Parent IDs in BFS spillover order, and re-distribute matrix commissions.
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="gold-button px-6 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-play"></i> Rearrange & Reset BFS Matrix Placement
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
