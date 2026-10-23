<?php
// customer/achiever_club.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Achiever Club - Direct Referral Ranks";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

$rankData = getMemberAchieverRank($pdo, $memberId);
$directCount = $rankData['direct_count'];
$currentRank = $rankData['current_rank'];
$nextRank = $rankData['next_rank'];
$ranks = $rankData['ranks'];
$progressPct = $rankData['progress_percentage'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/30">Member Portal</span>
                <span class="text-xs text-champagne/60">Reyon Global Impact</span>
            </div>
            <h1 class="text-2xl font-extrabold gold-gradient-text mt-2">Achiever Club Direct Referral Ranks</h1>
            <p class="text-xs text-champagne/70 mt-1">Sponsor direct referrals to unlock prestigious Achiever Club ranks, cash rewards, and company royalty income!</p>
        </div>

        <div class="glass-card px-5 py-3 rounded-2xl border border-gold/20 flex items-center gap-4">
            <div class="text-right">
                <span class="text-[10px] text-champagne/60 uppercase font-mono block">Your Direct Referrals</span>
                <span class="text-2xl font-black text-gold font-mono"><?php echo $directCount; ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gold/10 border border-gold/30 text-gold flex items-center justify-center text-lg">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Current Rank Status & Progress Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Current Rank Card -->
        <div class="glass-card p-6 rounded-3xl border border-gold/30 bg-gold/5 flex flex-col justify-between">
            <div>
                <span class="text-[10px] uppercase font-mono text-gold tracking-widest block mb-1">Current Achieved Rank</span>
                <h3 class="text-xl font-black gold-gradient-text">
                    <?php echo $currentRank ? htmlspecialchars($currentRank['rank_name']) : 'Active Member'; ?>
                </h3>
                <p class="text-xs text-champagne/70 mt-1">
                    <?php if ($currentRank): ?>
                        Requires <?php echo (int)$currentRank['required_direct_joinings']; ?> direct joinings
                    <?php else: ?>
                        Sponsor 10 direct referrals to unlock Star Achiever rank!
                    <?php endif; ?>
                </p>
            </div>

            <div class="mt-4 pt-4 border-t border-gold/20 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-champagne/60 block font-mono uppercase">Rank Reward</span>
                    <span class="text-lg font-bold text-emerald-400 font-mono">
                        $<?php echo $currentRank ? number_format((float)$currentRank['reward_amount'], 2) : '0.00'; ?>
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-champagne/60 block font-mono uppercase">Company Royalty</span>
                    <span class="text-lg font-bold text-gold font-mono">
                        <?php echo ($currentRank && $currentRank['is_royalty_eligible']) ? number_format((float)$currentRank['royalty_percentage'], 2) . '%' : 'N/A'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Next Rank Target Card -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between col-span-1 md:col-span-2">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[10px] uppercase font-mono text-gold tracking-widest block">Next Rank Target</span>
                    <?php if ($nextRank): ?>
                        <span class="text-xs font-mono font-bold text-champagne">
                            <?php echo $directCount; ?> / <?php echo (int)$nextRank['required_direct_joinings']; ?> Joins
                        </span>
                    <?php else: ?>
                        <span class="text-xs font-mono font-bold text-emerald-400">MAX RANK ACHIEVED!</span>
                    <?php endif; ?>
                </div>

                <?php if ($nextRank): ?>
                    <h3 class="text-xl font-bold text-champagne">
                        <?php echo htmlspecialchars($nextRank['rank_name']); ?>
                    </h3>
                    <p class="text-xs text-champagne/70 mt-1">
                        Sponsor <?php echo max(0, (int)$nextRank['required_direct_joinings'] - $directCount); ?> more direct referrals to reach <?php echo htmlspecialchars($nextRank['rank_name']); ?>.
                    </p>

                    <!-- Progress Bar -->
                    <div class="w-full bg-obsidian rounded-full h-3 border border-gold/30 p-0.5 mt-4">
                        <div class="bg-gradient-to-r from-gold-600 via-gold to-champagne h-2 rounded-full transition-all duration-500" style="width: <?php echo $progressPct; ?>%;"></div>
                    </div>
                <?php else: ?>
                    <h3 class="text-xl font-bold text-gold">Top Global Legend Achiever</h3>
                    <p class="text-xs text-champagne/70 mt-1">You have successfully unlocked the highest Achiever Club rank in Reyon Global Impact!</p>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-4 border-t border-gold/10 flex items-center justify-between text-xs">
                <?php if ($nextRank): ?>
                    <span class="text-champagne/70">Next Rank Reward: <strong class="text-emerald-400">$<?php echo number_format((float)$nextRank['reward_amount'], 2); ?></strong></span>
                    <span class="text-champagne/70">Royalty Share: <strong class="text-gold"><?php echo $nextRank['is_royalty_eligible'] ? number_format((float)$nextRank['royalty_percentage'], 2) . '%' : '0.00%'; ?></strong></span>
                <?php else: ?>
                    <span class="text-emerald-400 font-bold">Eligible for 5.00% Company Royalty Pool</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 10-Rank Achiever Matrix Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gold flex items-center gap-2">
                <i class="fa-solid fa-trophy"></i> 10-Level Achiever Club Compensation Matrix
            </h3>
            <span class="text-xs text-champagne/60 font-mono">Reyon Direct Referral Program</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3 w-16">Level</th>
                        <th class="p-3">Rank Title</th>
                        <th class="p-3">Direct Joinings</th>
                        <th class="p-3">Reward Amount ($ USD)</th>
                        <th class="p-3">Company Royalty Pool</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php foreach ($ranks as $r):
                        $reqJoins = (int)$r['required_direct_joinings'];
                        $isUnlocked = ($directCount >= $reqJoins);
                        $isCurrent = ($currentRank && $currentRank['rank_level'] == $r['rank_level']);
                    ?>
                        <tr class="hover:bg-gold/5 <?php echo $isCurrent ? 'bg-gold/10 border-l-4 border-l-gold' : ''; ?>">
                            <td class="p-3 font-mono font-bold text-gold text-center bg-gold/5 rounded-l-xl">
                                #<?php echo $r['rank_level']; ?>
                            </td>

                            <td class="p-3 font-bold text-champagne">
                                <?php echo htmlspecialchars($r['rank_name']); ?>
                                <?php if ($isCurrent): ?>
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-mono bg-gold text-obsidian uppercase font-extrabold">Current</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 font-mono font-bold text-gold">
                                <?php echo $reqJoins; ?> Joins
                            </td>

                            <td class="p-3 font-mono font-bold text-emerald-400">
                                $<?php echo number_format((float)$r['reward_amount'], 2); ?>
                            </td>

                            <td class="p-3 font-mono">
                                <?php if ($r['is_royalty_eligible']): ?>
                                    <span class="text-gold font-bold"><?php echo number_format((float)$r['royalty_percentage'], 2); ?>%</span>
                                    <span class="text-[10px] text-champagne/50">Company Share</span>
                                <?php else: ?>
                                    <span class="text-champagne/40">Not Eligible</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 text-center">
                                <?php if ($isUnlocked): ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Unlocked
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-obsidian text-champagne/40 border border-gold/10 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-lock"></i> Locked
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
