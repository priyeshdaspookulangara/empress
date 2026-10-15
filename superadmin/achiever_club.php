<?php
// superadmin/achiever_club.php
$pageTitle = "Achiever Club CMS - Direct Referral Ranks";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$error = '';

// Handle Form Submission to update Achiever Club ranks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ranks'])) {
    try {
        $pdo->beginTransaction();

        $ranks = $_POST['ranks'] ?? [];
        $stmtUp = $pdo->prepare("
            UPDATE achiever_club
            SET rank_name = ?,
                required_direct_joinings = ?,
                reward_amount = ?,
                is_royalty_eligible = ?,
                royalty_percentage = ?
            WHERE rank_level = ?
        ");

        foreach ($ranks as $lvl => $data) {
            $lvlInt = (int)$lvl;
            if ($lvlInt < 1 || $lvlInt > 10) continue;

            $rankName = trim($data['rank_name'] ?? '');
            if (empty($rankName)) {
                $rankName = "Rank Level " . $lvlInt;
            }

            $joinings = max(0, (int)($data['required_direct_joinings'] ?? 0));
            $reward = max(0.00, (float)($data['reward_amount'] ?? 0));
            $isRoyalty = !empty($data['is_royalty_eligible']) ? 1 : 0;
            $royaltyPct = $isRoyalty ? max(0.00, min(100.00, (float)($data['royalty_percentage'] ?? 0))) : 0.00;

            $stmtUp->execute([
                $rankName,
                $joinings,
                $reward,
                $isRoyalty,
                $royaltyPct,
                $lvlInt
            ]);
        }

        $pdo->commit();
        $msg = "Achiever Club ranks (Levels 1 to 10) updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update ranks: " . $e->getMessage();
    }
}

// Fetch 10 Ranks
$stmt = $pdo->query("SELECT * FROM achiever_club ORDER BY rank_level ASC");
$ranksList = $stmt->fetchAll();

// If fewer than 10 ranks exist, ensure array has 10 entries
$ranksByLevel = [];
foreach ($ranksList as $r) {
    $ranksByLevel[$r['rank_level']] = $r;
}

for ($i = 1; $i <= 10; $i++) {
    if (!isset($ranksByLevel[$i])) {
        $ranksByLevel[$i] = [
            'rank_level' => $i,
            'rank_name' => "Rank Level " . $i,
            'required_direct_joinings' => $i * 10,
            'reward_amount' => $i * 100.00,
            'is_royalty_eligible' => ($i >= 3) ? 1 : 0,
            'royalty_percentage' => ($i >= 3) ? ($i * 0.5) : 0.00
        ];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-neon-cyan bg-neon-cyan/10 px-3 py-1 rounded-full border border-neon-cyan/30">CMS Console</span>
                <span class="text-xs text-ice/60">Reyon Global Impact</span>
            </div>
            <h1 class="text-2xl font-extrabold neon-gradient-text mt-2">Achiever Club Direct Referral Ranks</h1>
            <p class="text-xs text-ice/70 mt-1">Configure the 10-rank direct referral achiever club matrix, direct joinings criteria, rewards, and company royalty percentages.</p>
        </div>
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

    <!-- CMS Input Table Form -->
    <form action="/superadmin/achiever_club.php" method="POST" class="space-y-6">
        <input type="hidden" name="update_ranks" value="1">

        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-neon-cyan flex items-center gap-2">
                    <i class="fa-solid fa-trophy"></i> 10-Level Achiever Club Settings
                </h3>
                <span class="text-xs text-ice/60 font-mono">Rank Levels 1 to 10</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                            <th class="p-3 w-16">Rank</th>
                            <th class="p-3">Rank Name</th>
                            <th class="p-3">Direct Joinings</th>
                            <th class="p-3">Reward Amount ($ USD)</th>
                            <th class="p-3">Royalty Eligible</th>
                            <th class="p-3">Royalty % (Total Company Income)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neon-cyan/10 text-ice">
                        <?php for ($lvl = 1; $lvl <= 10; $lvl++): $r = $ranksByLevel[$lvl]; ?>
                            <tr class="hover:bg-neon-cyan/5">
                                <td class="p-3 font-mono font-bold text-neon-cyan text-center bg-neon-cyan/5 rounded-l-xl">
                                    #<?php echo $lvl; ?>
                                </td>

                                <td class="p-3">
                                    <input type="text" name="ranks[<?php echo $lvl; ?>][rank_name]" value="<?php echo htmlspecialchars($r['rank_name']); ?>" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-2 text-xs text-ice font-semibold focus:outline-none focus:border-neon-cyan">
                                </td>

                                <td class="p-3">
                                    <div class="relative">
                                        <input type="number" min="0" name="ranks[<?php echo $lvl; ?>][required_direct_joinings]" value="<?php echo (int)$r['required_direct_joinings']; ?>" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl pl-3 pr-8 py-2 text-xs text-ice font-mono font-bold focus:outline-none focus:border-neon-cyan">
                                        <span class="absolute right-2 top-2 text-[10px] text-ice/50 font-sans">Joins</span>
                                    </div>
                                </td>

                                <td class="p-3">
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-emerald-400 font-bold">$</span>
                                        <input type="number" step="0.01" min="0" name="ranks[<?php echo $lvl; ?>][reward_amount]" value="<?php echo number_format((float)$r['reward_amount'], 2, '.', ''); ?>" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl pl-7 pr-3 py-2 text-xs text-emerald-300 font-mono font-bold focus:outline-none focus:border-neon-cyan">
                                    </div>
                                </td>

                                <td class="p-3 text-center">
                                    <select name="ranks[<?php echo $lvl; ?>][is_royalty_eligible]" onchange="toggleRoyaltyInput(this, <?php echo $lvl; ?>)" class="bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-2 text-xs text-ice font-bold focus:outline-none focus:border-neon-cyan">
                                        <option value="1" <?php echo $r['is_royalty_eligible'] ? 'selected' : ''; ?>>Yes (Eligible)</option>
                                        <option value="0" <?php echo !$r['is_royalty_eligible'] ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </td>

                                <td class="p-3">
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" max="100" id="royalty_pct_<?php echo $lvl; ?>" name="ranks[<?php echo $lvl; ?>][royalty_percentage]" value="<?php echo number_format((float)$r['royalty_percentage'], 2, '.', ''); ?>" <?php echo !$r['is_royalty_eligible'] ? 'disabled' : ''; ?> class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl pl-3 pr-7 py-2 text-xs text-gold font-mono font-bold focus:outline-none focus:border-neon-cyan disabled:opacity-30 disabled:cursor-not-allowed">
                                        <span class="absolute right-3 top-2 text-gold font-bold">%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="neon-button px-8 py-3.5 rounded-2xl text-xs font-bold shadow-lg shadow-neon-cyan/20 flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Save Achiever Club Ranks CMS Settings
            </button>
        </div>
    </form>
</div>

<script>
function toggleRoyaltyInput(selectElem, lvl) {
    const inputElem = document.getElementById('royalty_pct_' + lvl);
    if (selectElem.value === '1') {
        inputElem.disabled = false;
        inputElem.classList.remove('opacity-30');
    } else {
        inputElem.disabled = true;
        inputElem.value = '0.00';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
