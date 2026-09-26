<?php
$pageTitle = "Frequently Asked Questions (FAQ) - USDT (BEP-20) & Platform Guide";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8 py-4">
    <!-- Header -->
    <div class="glass-card p-8 rounded-3xl border border-gold/40 text-center relative overflow-hidden">
        <div class="absolute -left-10 -top-10 w-48 h-48 bg-gold/10 rounded-full blur-3xl pointer-events-none"></div>
        <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3.5 py-1.5 rounded-full border border-gold/30">Knowledge Base & Platform Guide</span>
        <h1 class="text-3xl md:text-4xl font-extrabold gold-gradient-text mt-3">Frequently Asked Questions</h1>
        <p class="text-xs md:text-sm text-champagne/80 max-w-2xl mx-auto mt-2 leading-relaxed">
            Everything you need to know about USDT (BEP-20) fund deposits, 50:50 wallet splits, 6-level matrix compensation, gas fees, and P2P wallet settings.
        </p>

        <!-- Search Box -->
        <div class="max-w-md mx-auto mt-6 relative">
            <input type="text" id="faqSearch" placeholder="Search FAQ topics (e.g., BEP-20, deposit, gas fee, KYC)..." onkeyup="filterFaq()" class="w-full bg-obsidian/90 border border-gold/40 rounded-2xl pl-10 pr-4 py-3 text-xs text-champagne placeholder-champagne/40 focus:outline-none focus:border-gold shadow-xl">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-gold text-sm"></i>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex flex-wrap justify-center gap-2 text-xs font-bold">
        <button onclick="filterCategory('all')" class="faq-tab active bg-gold text-obsidian px-4 py-2 rounded-xl transition">All Questions</button>
        <button onclick="filterCategory('crypto')" class="faq-tab bg-gold/10 border border-gold/20 hover:bg-gold/20 text-gold px-4 py-2 rounded-xl transition">USDT & Network (BEP-20)</button>
        <button onclick="filterCategory('deposits')" class="faq-tab bg-gold/10 border border-gold/20 hover:bg-gold/20 text-gold px-4 py-2 rounded-xl transition">Fund Deposits & Verification</button>
        <button onclick="filterCategory('matrix')" class="faq-tab bg-gold/10 border border-gold/20 hover:bg-gold/20 text-gold px-4 py-2 rounded-xl transition">Matrix Tree & Rebirths</button>
        <button onclick="filterCategory('withdrawals')" class="faq-tab bg-gold/10 border border-gold/20 hover:bg-gold/20 text-gold px-4 py-2 rounded-xl transition">Payouts & P2P Settings</button>
    </div>

    <!-- FAQ Accordion List -->
    <div class="space-y-4" id="faqAccordion">

        <!-- Q1: USDT Network Selection -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="crypto">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-network-wired"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">Which blockchain network is supported for USDT deposits and withdrawals?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    All deposits, transactions, and payouts on Empress Two Way 3.0 operate <strong>exclusively on Tether USD (USDT) via the BNB Smart Chain (BEP-20) network</strong>.
                </p>
                <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-300 font-medium">
                    <i class="fa-solid fa-triangle-exclamation mr-1 text-red-400"></i>
                    Do NOT send funds via TRC-20 (Tron), ERC-20 (Ethereum), Polygon, or Solana. Sending crypto via unsupported networks will result in permanent asset loss.
                </div>
            </div>
        </div>

        <!-- Q2: How to Deposit USDT -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="deposits">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold border border-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">How do I submit a USDT (BEP-20) Fund Deposit verification?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <ol class="list-decimal pl-5 space-y-1 text-champagne/90">
                    <li>Navigate to the <a href="/customer/deposit.php" class="text-emerald-400 font-bold underline">USDT Deposit</a> section in your member dashboard.</li>
                    <li>Scan the official QR code or copy the receiving address: <code class="text-gold font-bold">0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5</code>.</li>
                    <li>Transfer USDT using your Web3 crypto wallet (MetaMask, Trust Wallet, or Binance) choosing the **BNB Smart Chain (BEP-20)** network.</li>
                    <li>Copy your on-chain Transaction Hash / TxID (starts with <code>0x</code>) from your wallet transaction receipt or BscScan.</li>
                    <li>Paste your TxID and exact USDT amount into the Transaction Verification Form and submit.</li>
                </ol>
            </div>
        </div>

        <!-- Q3: Deposit Confirmation Time -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="deposits">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold border border-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">How long does it take for a deposit to be verified and credited?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    Deposits are typically verified and credited within <strong>10 to 30 minutes</strong> following blockchain node confirmation on the BNB Smart Chain ledger. During periods of high network congestion, verification may take slightly longer.
                </p>
            </div>
        </div>

        <!-- Q4: Gas Fees -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="crypto">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-gas-pump"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">What are Gas Fees and who pays for them?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    Gas fees are small network processing fees charged by the BNB Smart Chain blockchain nodes to execute smart contracts and transfer digital assets. Gas fees are paid in **BNB (Binance Coin)**.
                </p>
                <p>
                    When initiating outgoing transfers from your personal crypto wallet (such as Trust Wallet or MetaMask), you must maintain a small BNB balance (typically $0.10–$0.50 USD worth) in your wallet to execute the transaction.
                </p>
            </div>
        </div>

        <!-- Q5: 50:50 Smart Wallet Allocation -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="withdrawals">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold border border-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">How does the 50:50 Smart Wallet commission split work?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    Every matrix commission and helping contribution earned through the 6-level fixed tree is automatically split:
                </p>
                <ul class="list-disc pl-5 space-y-1 text-champagne/90">
                    <li><strong class="text-emerald-400">50% Customer Wallet:</strong> Directly eligible for payout withdrawal upon KYC approval.</li>
                    <li><strong class="text-gold">30% Burfee Cart Wallet:</strong> Utility and product reserve for ePIN activations.</li>
                    <li><strong class="text-champagne">20% Empress Charity Fund:</strong> Dedicated allocation for global charity causes.</li>
                </ul>
            </div>
        </div>

        <!-- Q6: P2P Wallet Settings & QR Code -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="withdrawals">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold border border-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">How do I configure my P2P Wallet Settings and upload my receiving QR code?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    Go to your <a href="/customer/profile.php" class="text-gold font-bold underline">Profile & Wallet Console</a>. In the **P2P Crypto Wallet Settings** section:
                </p>
                <ol class="list-decimal pl-5 space-y-1 text-champagne/90">
                    <li>Enter your standard 42-character BNB Smart Chain (BEP-20) address starting with <code class="text-gold">0x</code>.</li>
                    <li>Upload an image file (`JPG`, `PNG`, or `WEBP`) of your personal receiving QR code.</li>
                    <li>Click **Update P2P Wallet Settings** to save. You will see a live preview of your uploaded QR code.</li>
                </ol>
            </div>
        </div>

        <!-- Q7: Rebirth Positions Engine -->
        <div class="faq-item glass-card rounded-2xl border border-gold/20 overflow-hidden" data-category="matrix">
            <button onclick="toggleFaq(this)" class="w-full p-5 text-left flex justify-between items-center gap-4 hover:bg-gold/5 transition focus:outline-none">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold border border-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-rotate"></i>
                    </div>
                    <span class="text-sm font-bold text-champagne">What are Rebirth Positions and how are they granted?</span>
                </div>
                <i class="fa-solid fa-chevron-down text-gold text-xs transition duration-300 transform"></i>
            </button>
            <div class="faq-answer hidden px-5 pb-5 pt-2 text-xs text-champagne/80 border-t border-gold/10 space-y-2">
                <p>
                    Rebirth Positions are automated child matrix nodes generated in the company-wide 3-matrix tree when you complete matrix levels:
                </p>
                <ul class="list-disc pl-5 space-y-1 text-champagne/90">
                    <li><strong>Level 3 Completion (27 nodes):</strong> Unlocks +10 Rebirths.</li>
                    <li><strong>Level 4 Completion (81 nodes):</strong> Unlocks +20 Rebirths.</li>
                    <li><strong>Level 5 Completion (243 nodes):</strong> Unlocks +70 Rebirths.</li>
                    <li><strong>Level 6 Completion (729 nodes):</strong> Unlocks +100 Rebirths.</li>
                </ul>
                <p class="mt-1">
                    All earnings generated by your rebirth child positions are aggregated and displayed directly inside your primary account dashboard and <a href="/customer/rebirths.php" class="text-gold font-bold underline">Rebirths Portal</a>.
                </p>
            </div>
        </div>

    </div>

    <!-- Bottom Contact Notice -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-center sm:text-left">
        <div>
            <span class="font-bold text-gold text-sm">Need Further Assistance?</span>
            <p class="text-champagne/70 mt-0.5">Our compliance and support team is available 24/7 to inspect blockchain transactions and resolve P2P queries.</p>
        </div>
        <a href="/terms.php" class="gold-button px-5 py-2.5 rounded-xl font-bold whitespace-nowrap flex items-center gap-2">
            <i class="fa-solid fa-file-contract"></i> View Terms & Conditions
        </a>
    </div>
</div>

<script>
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const icon = btn.querySelector('.fa-chevron-down');

    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        answer.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

function filterCategory(cat) {
    document.querySelectorAll('.faq-tab').forEach(t => {
        t.classList.remove('bg-gold', 'text-obsidian');
        t.classList.add('bg-gold/10', 'text-gold');
    });
    event.target.classList.remove('bg-gold/10', 'text-gold');
    event.target.classList.add('bg-gold', 'text-obsidian');

    const items = document.querySelectorAll('.faq-item');
    items.forEach(item => {
        if (cat === 'all' || item.dataset.category === cat) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterFaq() {
    const query = document.getElementById('faqSearch').value.toLowerCase();
    const items = document.querySelectorAll('.faq-item');

    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
