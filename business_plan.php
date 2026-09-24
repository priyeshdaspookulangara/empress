<?php
$pageTitle = "Business Plan - 6-Level Compensation Structure";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-10">
    <!-- Header Banner -->
    <div class="glass-card p-8 md:p-12 rounded-3xl border border-gold/30 text-center">
        <h1 class="text-3xl md:text-5xl font-extrabold gold-gradient-text">Empress Two Way 3.0 Business Model</h1>
        <p class="text-champagne/80 text-sm md:text-base mt-3 max-w-2xl mx-auto">
            A revolutionary, single-phase direct selling platform with automated 3-matrix forced spillover, 50:50 smart wallet allocation (50% Customer Wallet / 50% Company Portion: 60% Burfee Cart & 40% Charity), and guaranteed 6-level payout potential.
        </p>
    </div>

    <!-- Smart Allocation & Spillover Explanation -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="glass-card p-6 rounded-2xl border border-gold/20">
            <h3 class="text-xl font-bold text-gold mb-3 flex items-center gap-2">
                <i class="fa-solid fa-sitemap"></i> Global BFS Auto-Spillover Matrix
            </h3>
            <p class="text-xs text-champagne/80 leading-relaxed mb-3">
                Our engine uses strict company-wide <span class="text-gold font-semibold">Breadth-First Search (BFS)</span> auto-spillover logic. Starting from Root (<code class="bg-gold/10 px-1 py-0.5 rounded text-gold">EMP100000</code>), slots are filled left-to-right across each level before moving deeper.
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
                Every commission generated is split 50% to Customer Wallet and 50% to Company (which is split 60% Burfee Cart / 40% Charity):
            </p>
            <div class="space-y-2">
                <div class="bg-gold/10 p-3 rounded-xl border border-gold/30 flex justify-between items-center text-xs">
                    <span class="font-bold text-gold">50% Customer Wallet</span>
                    <span class="text-emerald-400 font-bold">Eligible for Payout Withdrawal</span>
                </div>
                <div class="bg-gold/10 p-3 rounded-xl border border-gold/30 flex justify-between items-center text-xs">
                    <span class="font-bold text-gold">Company 50% Portion</span>
                    <span class="text-champagne font-bold">60% Burfee Cart ($A \times 30\%$) / 40% Charity ($A \times 20\%$)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed 6-Level Compensation Table -->
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30">
        <h2 class="text-2xl font-bold text-gold mb-4 text-center">Handwritten Schedule Matrix Levels</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gold/15 border-b border-gold/30 text-gold uppercase text-xs">
                        <th class="p-4">Level</th>
                        <th class="p-4">Node Capacity</th>
                        <th class="p-4">Payout Per Node</th>
                        <th class="p-4">Level Total</th>
                        <th class="p-4">50% Customer Wallet</th>
                        <th class="p-4">30% Burfee Cart (60% of Co)</th>
                        <th class="p-4">20% Charity (40% of Co)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 1</td>
                        <td class="p-4">3 Nodes</td>
                        <td class="p-4">$10 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$30 USD</td>
                        <td class="p-4">$15 USD</td>
                        <td class="p-4 text-champagne/70">$9 USD</td>
                        <td class="p-4 text-champagne/70">$6 USD</td>
                    </tr>
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 2</td>
                        <td class="p-4">9 Nodes</td>
                        <td class="p-4">$20 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$180 USD</td>
                        <td class="p-4">$90 USD</td>
                        <td class="p-4 text-champagne/70">$54 USD</td>
                        <td class="p-4 text-champagne/70">$36 USD</td>
                    </tr>
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 3</td>
                        <td class="p-4">27 Nodes</td>
                        <td class="p-4">$40 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$1,080 USD (+ 10 Rebirths)</td>
                        <td class="p-4">$540 USD</td>
                        <td class="p-4 text-champagne/70">$324 USD</td>
                        <td class="p-4 text-champagne/70">$216 USD</td>
                    </tr>
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 4</td>
                        <td class="p-4">81 Nodes</td>
                        <td class="p-4">$60 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$4,860 USD (+ 20 Rebirths)</td>
                        <td class="p-4">$2,430 USD</td>
                        <td class="p-4 text-champagne/70">$1,458 USD</td>
                        <td class="p-4 text-champagne/70">$972 USD</td>
                    </tr>
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 5</td>
                        <td class="p-4">243 Nodes</td>
                        <td class="p-4">$80 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$19,440 USD (+ 70 Rebirths)</td>
                        <td class="p-4">$9,720 USD</td>
                        <td class="p-4 text-champagne/70">$5,832 USD</td>
                        <td class="p-4 text-champagne/70">$3,888 USD</td>
                    </tr>
                    <tr>
                        <td class="p-4 font-bold text-gold">Level 6</td>
                        <td class="p-4">729 Nodes</td>
                        <td class="p-4">$100 USD</td>
                        <td class="p-4 font-bold text-emerald-400">$72,900 USD (+ 100 Rebirths)</td>
                        <td class="p-4">$36,450 USD</td>
                        <td class="p-4 text-champagne/70">$21,870 USD</td>
                        <td class="p-4 text-champagne/70">$14,580 USD</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gold/20 text-gold font-bold text-base">
                        <td class="p-4" colspan="3">Total Potential (1,092 Nodes)</td>
                        <td class="p-4 text-emerald-300">$98,490 USD</td>
                        <td class="p-4 text-emerald-300">$49,245 USD</td>
                        <td class="p-4 text-champagne">$29,547 USD</td>
                        <td class="p-4 text-champagne">$19,698 USD</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Company Inflow Split Rule -->
    <div class="glass-card p-6 rounded-2xl border border-gold/20 text-center">
        <h4 class="text-lg font-bold text-gold mb-2">Company Revenue Retention & Charity Split</h4>
        <p class="text-xs text-champagne/80 max-w-xl mx-auto">
            Out of company retains/inflow (50%), 20% goes directly to Empress Foundation Charity Initiatives and 30% is retained by the company for operational expansion and platform maintenance.
        </p>
    </div>

    <div class="text-center pb-8">
        <a href="/register.php" class="gold-button px-10 py-4 rounded-xl text-lg font-bold shadow-xl shadow-gold/20 inline-flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Activate Your Position Now
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
