<?php
// superadmin/replace_prefix.php
$pageTitle = "Member ID Prefix Migration Tool";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'replace_prefix') {
    $oldPrefix = strtoupper(trim($_POST['old_prefix'] ?? ''));
    $newPrefix = strtoupper(trim($_POST['new_prefix'] ?? ''));

    if (empty($oldPrefix) || empty($newPrefix)) {
        $error = "Please provide both old and new prefixes.";
    } elseif ($oldPrefix === $newPrefix) {
        $error = "Old prefix and new prefix cannot be identical.";
    } elseif (!preg_match('/^[A-Z0-9]+$/', $oldPrefix) || !preg_match('/^[A-Z0-9]+$/', $newPrefix)) {
        $error = "Prefixes must contain only alphanumeric uppercase characters.";
    } else {
        try {
            $pdo->beginTransaction();

            $oldLen = strlen($oldPrefix);

            // Update members.member_id, sponsor_id, placement_parent_id
            $stmtM = $pdo->prepare("
                UPDATE members
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtM->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);
            $countMemberId = $stmtM->rowCount();

            $stmtSp = $pdo->prepare("
                UPDATE members
                SET sponsor_id = CONCAT(?, SUBSTR(sponsor_id, ?))
                WHERE sponsor_id LIKE ?
            ");
            $stmtSp->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);
            $countSponsorId = $stmtSp->rowCount();

            $stmtPl = $pdo->prepare("
                UPDATE members
                SET placement_parent_id = CONCAT(?, SUBSTR(placement_parent_id, ?))
                WHERE placement_parent_id LIKE ?
            ");
            $stmtPl->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update wallets.member_id
            $stmtW = $pdo->prepare("
                UPDATE wallets
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtW->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update transactions.member_id
            $stmtTx = $pdo->prepare("
                UPDATE transactions
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtTx->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update withdrawals.member_id
            $stmtWith = $pdo->prepare("
                UPDATE withdrawals
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtWith->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update deposits.user_id
            $stmtDep = $pdo->prepare("
                UPDATE deposits
                SET user_id = CONCAT(?, SUBSTR(user_id, ?))
                WHERE user_id LIKE ?
            ");
            $stmtDep->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update member_rebirths.member_id
            $stmtMr = $pdo->prepare("
                UPDATE member_rebirths
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtMr->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update queued_rebirths.member_id
            $stmtQr = $pdo->prepare("
                UPDATE queued_rebirths
                SET member_id = CONCAT(?, SUBSTR(member_id, ?))
                WHERE member_id LIKE ?
            ");
            $stmtQr->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            // Update epins.used_by_member_id & assigned_to
            $stmtEp1 = $pdo->prepare("
                UPDATE epins
                SET used_by_member_id = CONCAT(?, SUBSTR(used_by_member_id, ?))
                WHERE used_by_member_id LIKE ?
            ");
            $stmtEp1->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            $stmtEp2 = $pdo->prepare("
                UPDATE epins
                SET assigned_to = CONCAT(?, SUBSTR(assigned_to, ?))
                WHERE assigned_to LIKE ?
            ");
            $stmtEp2->execute([$newPrefix, $oldLen + 1, $oldPrefix . '%']);

            $pdo->commit();
            $msg = "Prefix successfully updated from '{$oldPrefix}' to '{$newPrefix}' across {$countMemberId} Member IDs and {$countSponsorId} Sponsor references!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update prefix: " . $e->getMessage();
        }
    }
}

// Fetch preview counts for prefixes
$stmtPrefixes = $pdo->query("
    SELECT SUBSTR(member_id, 1, 3) as prefix, COUNT(*) as count
    FROM members
    GROUP BY SUBSTR(member_id, 1, 3)
");
$prefixCounts = $stmtPrefixes->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">Member ID Prefix Migration Tool</h1>
            <p class="text-xs text-ice/70 mt-1">Batch update Member IDs, Sponsor IDs, Placement Parents, and linked financial ledger tables from an old prefix (e.g. EMP) to a new prefix (e.g. RGI).</p>
        </div>
        <a href="/superadmin/members.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-users"></i> Member Directory
        </a>
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

    <!-- Existing Prefix Statistics -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 space-y-4">
        <h3 class="text-sm font-bold text-neon-cyan uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-chart-pie"></i> Existing Member ID Prefixes in Database
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach ($prefixCounts as $pc): ?>
                <div class="p-4 rounded-2xl bg-neon-cyan/10 border border-neon-cyan/20 text-center">
                    <span class="text-xs text-ice/70 font-mono block">Prefix: <strong class="text-neon-cyan"><?php echo htmlspecialchars($pc['prefix']); ?></strong></span>
                    <span class="text-2xl font-extrabold text-neon-cyan font-mono mt-1 block"><?php echo number_format($pc['count']); ?></span>
                    <span class="text-[10px] text-ice/50">Members</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Migration Form -->
    <div class="glass-card p-8 rounded-3xl border border-neon-cyan/30 max-w-2xl mx-auto space-y-6">
        <div class="border-b border-neon-cyan/20 pb-4">
            <h2 class="text-lg font-bold text-neon-cyan flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square"></i> Replace Member ID & Sponsor Prefix
            </h2>
            <p class="text-xs text-ice/70 mt-1">
                Enter the target old prefix to be replaced (e.g. <code>EMP</code>) and the new prefix (e.g. <code>RGI</code>). All downlines, tree placements, wallets, transactions, and rebirth references will be safely updated simultaneously.
            </p>
        </div>

        <form action="/superadmin/replace_prefix.php" method="POST" onsubmit="return confirm('ARE YOU SURE? This will update all matching Member IDs, Sponsor IDs, and matrix references in the database.');" class="space-y-4">
            <input type="hidden" name="action" value="replace_prefix">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-ice uppercase mb-2">Old Prefix (To Replace)</label>
                    <input type="text" name="old_prefix" placeholder="e.g. EMP" required uppercase class="w-full bg-slate-900/80 border border-neon-cyan/30 rounded-xl px-4 py-3 text-sm text-ice font-mono focus:outline-none focus:border-neon-cyan uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-emerald-400 uppercase mb-2">New Target Prefix</label>
                    <input type="text" name="new_prefix" placeholder="e.g. RGI" required uppercase class="w-full bg-slate-900/80 border border-emerald-500/40 rounded-xl px-4 py-3 text-sm text-emerald-300 font-mono focus:outline-none focus:border-emerald-400 uppercase">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="gold-button w-full py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-gold/20">
                    <i class="fa-solid fa-rotate mr-1"></i> Execute Prefix Replacement across Database
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
