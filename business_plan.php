<?php
$pageTitle = "Business Plan - 6-Level Compensation Structure";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-10">
    <!-- Header Banner -->
    <div class="glass-card p-8 md:p-12 rounded-3xl border border-gold/30 text-center">
        <h1 class="text-3xl md:text-5xl font-extrabold gold-gradient-text">Empress Two Way 4.0 Business Model</h1>
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
        <h2 class="text-2xl font-bold text-gold mb-4 text-center">Master 6-Level Compensation & Deductions Schedule</h2>

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
                        <th class="p-3">Burfee Cart (30%)</th>
                        <th class="p-3">Charity Fund (20%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 1</td>
                        <td class="p-3">3 Nodes</td>
                        <td class="p-3">₹1,500 ($15.00)</td>
                        <td class="p-3 text-amber-400">₹1,000 ($10.00)</td>
                        <td class="p-3">₹500 ($5.00)</td>
                        <td class="p-3">₹0 ($0.00)</td>
                        <td class="p-3 font-bold text-emerald-400">₹250 ($2.50)</td>
                        <td class="p-3">₹150 ($1.50)</td>
                        <td class="p-3">₹100 ($1.00)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 2</td>
                        <td class="p-3">9 Nodes</td>
                        <td class="p-3">₹9,000 ($90.00)</td>
                        <td class="p-3 text-amber-400">₹2,000 ($20.00)</td>
                        <td class="p-3">₹7,000 ($70.00)</td>
                        <td class="p-3">₹0 ($0.00)</td>
                        <td class="p-3 font-bold text-emerald-400">₹3,500 ($35.00)</td>
                        <td class="p-3">₹2,100 ($21.00)</td>
                        <td class="p-3">₹1,400 ($14.00)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 3</td>
                        <td class="p-3">27 Nodes</td>
                        <td class="p-3">₹54,000 ($540.00)</td>
                        <td class="p-3 text-amber-400">₹12,000 ($120.00)</td>
                        <td class="p-3">₹42,000 ($420.00)</td>
                        <td class="p-3 text-purple-400">₹10,000 ($100.00 - 10 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">₹11,000 ($110.00)</td>
                        <td class="p-3">₹12,600 ($126.00)</td>
                        <td class="p-3">₹8,400 ($84.00)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 4</td>
                        <td class="p-3">81 Nodes</td>
                        <td class="p-3">₹2,43,000 ($2,430.00)</td>
                        <td class="p-3 text-amber-400">₹0 ($0.00)</td>
                        <td class="p-3">₹2,43,000 ($2,430.00)</td>
                        <td class="p-3 text-purple-400">₹20,000 ($200.00 - 20 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">₹1,01,500 ($1,015.00)</td>
                        <td class="p-3">₹72,900 ($729.00)</td>
                        <td class="p-3">₹48,600 ($486.00)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 5</td>
                        <td class="p-3">243 Nodes</td>
                        <td class="p-3">₹9,72,000 ($9,720.00)</td>
                        <td class="p-3 text-amber-400">₹0 ($0.00)</td>
                        <td class="p-3">₹9,72,000 ($9,720.00)</td>
                        <td class="p-3 text-purple-400">₹70,000 ($700.00 - 70 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">₹4,16,000 ($4,160.00)</td>
                        <td class="p-3">₹2,91,600 ($2,916.00)</td>
                        <td class="p-3">₹1,94,400 ($1,944.00)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-bold text-gold">Level 6</td>
                        <td class="p-3">729 Nodes</td>
                        <td class="p-3">₹36,45,000 ($36,450.00)</td>
                        <td class="p-3 text-amber-400">₹0 ($0.00)</td>
                        <td class="p-3">₹36,45,000 ($36,450.00)</td>
                        <td class="p-3 text-purple-400">₹1,00,000 ($1,000.00 - 100 Rebirths)</td>
                        <td class="p-3 font-bold text-emerald-400">₹17,22,500 ($17,225.00)</td>
                        <td class="p-3">₹10,93,500 ($10,935.00)</td>
                        <td class="p-3">₹7,29,000 ($7,290.00)</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gold/20 text-gold font-bold">
                        <td class="p-3" colspan="2">Total Potential (1,092 Nodes)</td>
                        <td class="p-3">₹49,24,500 ($49,245.00)</td>
                        <td class="p-3 text-amber-300">₹15,000 ($150.00)</td>
                        <td class="p-3">₹49,09,500 ($49,095.00)</td>
                        <td class="p-3 text-purple-300">₹2,00,000 ($2,000.00 - 200 Rebirths)</td>
                        <td class="p-3 text-emerald-300">₹22,54,750 ($22,547.50)</td>
                        <td class="p-3 text-champagne">₹14,72,850 ($14,728.50)</td>
                        <td class="p-3 text-champagne">₹9,81,900 ($9,819.00)</td>
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
