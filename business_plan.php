<?php
$pageTitle = "Business Plan - 7-Level Compensation Structure";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-10">
    <!-- Header Banner -->
    <div class="glass-card p-8 md:p-12 rounded-3xl border border-gold/30 text-center">
        <h1 class="text-3xl md:text-5xl font-extrabold gold-gradient-text">Reyon Global Impact Business Model</h1>
        <p class="text-champagne/80 text-sm md:text-base mt-3 max-w-2xl mx-auto">
            A revolutionary, single-phase direct selling platform with automated 3-matrix forced spillover, 50:50 smart wallet allocation (50% Customer Wallet / 50% Company Portion: 60% Rayon Cart & 40% Charity), and guaranteed 7-level payout potential.
        </p>
    </div>

    <!-- Smart Allocation & Spillover Explanation -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="glass-card p-6 rounded-2xl border border-gold/20">
            <h3 class="text-xl font-bold text-gold mb-3 flex items-center gap-2">
                <i class="fa-solid fa-sitemap"></i> Global BFS Auto-Spillover Matrix
            </h3>
            <p class="text-xs text-champagne/80 leading-relaxed mb-3">
                Our engine uses strict company-wide <span class="text-gold font-semibold">Breadth-First Search (BFS)</span> auto-spillover logic. Starting from Root (<code class="bg-gold/10 px-1 py-0.5 rounded text-gold">RGI100000</code>), slots are filled left-to-right across each level before moving deeper.
            </p>
            <ul class="text-xs text-champagne/70 space-y-2 list-disc list-inside">
                <li>Maximum 3 direct children per member node.</li>
                <li>Automatic team creation through global spillover.</li>
                <li>No direct placement dead-ends; guaranteed tree growth.</li>
            </ul>
        </div>

        <div class="glass-card p-6 rounded-2xl border border-gold/20">
            <h3 class="text-xl font-bold text-gold mb-3 flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced"></i> 50:50 Smart Wallet Allocation
            </h3>
            <p class="text-xs text-champagne/80 leading-relaxed mb-3">
                Every commission generated is split 50% to Customer Wallet and 50% to Company (which is split 60% Rayon Cart / 40% Charity):
            </p>
            <div class="space-y-2">
                <div class="bg-gold/10 p-3 rounded-xl border border-gold/30 flex justify-between items-center text-xs">
                    <span class="font-bold text-gold">50% Customer Wallet</span>
                    <span class="text-emerald-400 font-bold">Eligible for Payout Withdrawal</span>
                </div>
                <div class="bg-gold/10 p-3 rounded-xl border border-gold/30 flex justify-between items-center text-xs">
                    <span class="font-bold text-gold">Company 50% Portion</span>
                    <span class="text-champagne font-bold">60% Rayon Cart (30% of total) / 40% Charity (20% of total)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed 7-Level Compensation Table -->
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30">
        <h2 class="text-2xl font-bold text-gold mb-4 text-center">Master 7-Level Compensation & Deductions Schedule</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/15 border-b border-gold/30 text-gold uppercase">
                        <th class="p-3">Level</th>
                        <th class="p-3">Capacity</th>
                        <th class="p-3">Gross Potential</th>
                        <th class="p-3">Helping Fund</th>
                        <th class="p-3">Net 50:50 Pool</th>
                        <th class="p-3">Rebirth Expense</th>
                        <th class="p-3 text-emerald-400">Net Member Cash (User Wallet)</th>
                        <th class="p-3">Rayon Cart (30%)</th>
                        <th class="p-3">Charity Fund (20%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 1</td>
                        <td class="p-3">3 Nodes</td>
                        <td class="p-3">$15.00</td>
                        <td class="p-3 text-amber-400">$10.00</td>
                        <td class="p-3">$5.00</td>
                        <td class="p-3">$0.00</td>
                        <td class="p-3 font-bold text-emerald-400">$2.50</td>
                        <td class="p-3">$1.50</td>
                        <td class="p-3">$1.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 2</td>
                        <td class="p-3">9 Nodes</td>
                        <td class="p-3">$90.00</td>
                        <td class="p-3 text-amber-400">$20.00</td>
                        <td class="p-3">$70.00</td>
                        <td class="p-3">$0.00</td>
                        <td class="p-3 font-bold text-emerald-400">$35.00</td>
                        <td class="p-3">$21.00</td>
                        <td class="p-3">$14.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 3</td>
                        <td class="p-3">27 Nodes</td>
                        <td class="p-3">$540.00</td>
                        <td class="p-3 text-amber-400">$120.00</td>
                        <td class="p-3">$420.00</td>
                        <td class="p-3 text-purple-400">$100.00 (10 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">$110.00</td>
                        <td class="p-3">$126.00</td>
                        <td class="p-3">$84.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 4</td>
                        <td class="p-3">81 Nodes</td>
                        <td class="p-3">$2,430.00</td>
                        <td class="p-3 text-amber-400">$60.00</td>
                        <td class="p-3">$2,430.00</td>
                        <td class="p-3 text-purple-400">$200.00 (20 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">$955.00</td>
                        <td class="p-3">$729.00</td>
                        <td class="p-3">$486.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 5</td>
                        <td class="p-3">243 Nodes</td>
                        <td class="p-3">$9,720.00</td>
                        <td class="p-3 text-amber-400">$0.00</td>
                        <td class="p-3">$9,720.00</td>
                        <td class="p-3 text-purple-400">$700.00 (70 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">$4,160.00</td>
                        <td class="p-3">$2,916.00</td>
                        <td class="p-3">$1,944.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 6</td>
                        <td class="p-3">729 Nodes</td>
                        <td class="p-3">$36,450.00</td>
                        <td class="p-3 text-amber-400">$0.00</td>
                        <td class="p-3">$36,450.00</td>
                        <td class="p-3 text-purple-400">$1,000.00 (100 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">$17,225.00</td>
                        <td class="p-3">$10,935.00</td>
                        <td class="p-3">$7,290.00</td>
                    </tr>
                    <tr class="bg-gold/10">
                        <td class="p-3 font-bold text-gold">Level 7</td>
                        <td class="p-3">2,187 Nodes</td>
                        <td class="p-3">$131,220.00</td>
                        <td class="p-3 text-amber-400">$0.00</td>
                        <td class="p-3">$131,220.00</td>
                        <td class="p-3 text-purple-400">$65,610.00 (50% Customer Wallet -> 6,561 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">$0.00</td>
                        <td class="p-3">$39,366.00</td>
                        <td class="p-3">$26,244.00</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gold/20 text-gold font-bold">
                        <td class="p-3" colspan="2">Total Potential (3,279 Nodes)</td>
                        <td class="p-3">$180,465.00</td>
                        <td class="p-3 text-amber-300">$210.00</td>
                        <td class="p-3">$180,315.00</td>
                        <td class="p-3 text-purple-300">$67,610.00 (6,761 Rebirths)</td>
                        <td class="p-3 text-emerald-300">$22,487.50</td>
                        <td class="p-3 text-champagne">$54,094.50</td>
                        <td class="p-3 text-champagne">$36,063.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 10-Level Achiever Club Compensation Matrix Section -->
    <?php
    $pdoBP = getDBConnection();
    try {
        $stmtBP = $pdoBP->query("SELECT * FROM achiever_club ORDER BY rank_level ASC");
        $achieverRanks = $stmtBP->fetchAll();
    } catch (Exception $e) {
        $achieverRanks = [];
    }

    if (empty($achieverRanks)) {
        $achieverRanks = [
            ['rank_level' => 1, 'rank_name' => 'Star Achiever', 'required_direct_joinings' => 10, 'reward_amount' => 50.00, 'is_royalty_eligible' => 0, 'royalty_percentage' => 0.00],
            ['rank_level' => 2, 'rank_name' => 'Bronze Achiever', 'required_direct_joinings' => 25, 'reward_amount' => 150.00, 'is_royalty_eligible' => 0, 'royalty_percentage' => 0.00],
            ['rank_level' => 3, 'rank_name' => 'Silver Achiever', 'required_direct_joinings' => 50, 'reward_amount' => 350.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 1.00],
            ['rank_level' => 4, 'rank_name' => 'Gold Achiever', 'required_direct_joinings' => 100, 'reward_amount' => 800.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 1.50],
            ['rank_level' => 5, 'rank_name' => 'Platinum Achiever', 'required_direct_joinings' => 200, 'reward_amount' => 1800.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 2.00],
            ['rank_level' => 6, 'rank_name' => 'Diamond Achiever', 'required_direct_joinings' => 400, 'reward_amount' => 4000.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 2.50],
            ['rank_level' => 7, 'rank_name' => 'Crown Achiever', 'required_direct_joinings' => 800, 'reward_amount' => 9000.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 3.00],
            ['rank_level' => 8, 'rank_name' => 'Ambassador', 'required_direct_joinings' => 1500, 'reward_amount' => 20000.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 3.50],
            ['rank_level' => 9, 'rank_name' => 'Royal Ambassador', 'required_direct_joinings' => 3000, 'reward_amount' => 45000.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 4.00],
            ['rank_level' => 10, 'rank_name' => 'Global Legend', 'required_direct_joinings' => 5000, 'reward_amount' => 100000.00, 'is_royalty_eligible' => 1, 'royalty_percentage' => 5.00],
        ];
    }
    ?>

    <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30">
        <div class="text-center mb-6">
            <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/30">Direct Referral Leadership</span>
            <h2 class="text-2xl font-extrabold gold-gradient-text mt-3">10-Level Achiever Club Ranks & Royalty Rewards</h2>
            <p class="text-xs text-champagne/70 mt-1 max-w-2xl mx-auto">In addition to 3-matrix commissions, members earn massive one-time rank bonuses and lifetime company royalty income by sponsoring direct referrals.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/15 border-b border-gold/30 text-gold uppercase">
                        <th class="p-3 w-16 text-center">Rank</th>
                        <th class="p-3">Rank Name</th>
                        <th class="p-3">Required Direct Joins</th>
                        <th class="p-3 text-emerald-400">Rank Bonus ($ USD)</th>
                        <th class="p-3">Company Royalty Pool Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php foreach ($achieverRanks as $r): ?>
                        <tr class="hover:bg-gold/5">
                            <td class="p-3 font-mono font-bold text-gold text-center bg-gold/5 rounded-l-xl">
                                #<?php echo (int)$r['rank_level']; ?>
                            </td>
                            <td class="p-3 font-bold text-champagne">
                                <i class="fa-solid fa-trophy text-gold/70 mr-1.5"></i>
                                <?php echo htmlspecialchars($r['rank_name']); ?>
                            </td>
                            <td class="p-3 font-mono font-bold text-gold">
                                <?php echo (int)$r['required_direct_joinings']; ?> Direct Members
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-400">
                                $<?php echo number_format((float)$r['reward_amount'], 2); ?>
                            </td>
                            <td class="p-3 font-mono">
                                <?php if (!empty($r['is_royalty_eligible'])): ?>
                                    <span class="text-gold font-bold"><?php echo number_format((float)$r['royalty_percentage'], 2); ?>%</span>
                                    <span class="text-[10px] text-champagne/60">Company Turnover</span>
                                <?php else: ?>
                                    <span class="text-champagne/40">Not Eligible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Company Inflow Split Rule -->
    <div class="glass-card p-6 rounded-2xl border border-gold/20 text-center">
        <h4 class="text-lg font-bold text-gold mb-2">Company Revenue Retention & Charity Split</h4>
        <p class="text-xs text-champagne/80 max-w-xl mx-auto">
            Out of company retains/inflow (50%), 20% goes directly to Reyon Foundation Charity Initiatives and 30% is retained by the company for operational expansion and platform maintenance.
        </p>
    </div>

    <div class="text-center pb-8">
        <a href="/register.php" class="gold-button px-10 py-4 rounded-xl text-lg font-bold shadow-xl shadow-gold/20 inline-flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Activate Your Position Now
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
