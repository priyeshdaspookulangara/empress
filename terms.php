<?php
$pageTitle = "Terms & Conditions - USDT (BEP-20) Platform Policies";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8 py-4">
    <!-- Header -->
    <div class="glass-card p-8 rounded-3xl border border-gold/40 text-center relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-gold/10 rounded-full blur-3xl pointer-events-none"></div>
        <span class="text-xs uppercase font-mono tracking-widest text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/30">Legal & Compliance Policy</span>
        <h1 class="text-3xl md:text-4xl font-extrabold gold-gradient-text mt-3">Terms & Conditions</h1>
        <p class="text-xs md:text-sm text-champagne/80 max-w-2xl mx-auto mt-2 leading-relaxed">
            Please read these Terms & Conditions carefully. They govern all deposits, platform participation, 50:50 wallet allocations, and peer-to-peer (P2P) transfers on the Empress Two Way 3.0 Web3 platform.
        </p>
        <div class="text-[11px] text-gold/60 font-mono mt-4">Last Updated: <?php echo date('F d, Y'); ?> • USDT (BEP-20) Standard</div>
    </div>

    <!-- Terms Clauses Container -->
    <div class="space-y-6 text-xs text-champagne/90 leading-relaxed">

        <!-- 1. Payment Gateway & Transaction Methods -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/20 space-y-3">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-lg">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-gold uppercase tracking-wider">1. Digital Asset & Network Specification</h2>
                    <span class="text-[10px] text-emerald-400 font-mono font-bold">BNB Smart Chain (BEP-20) Exclusive Standard</span>
                </div>
            </div>

            <p>
                All account activations, package subscriptions, matrix commission credits, platform fund deposits, and withdrawal payouts within Empress Two Way 3.0 are conducted <strong>exclusively in Tether USD (USDT) utilizing the BNB Smart Chain (BEP-20) network</strong>.
            </p>

            <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 font-medium">
                <i class="fa-solid fa-triangle-exclamation mr-1.5 text-red-400"></i>
                <strong>User Network Responsibility Warning:</strong> Users are solely responsible for ensuring that all incoming and outgoing transfers use the <strong>BNB Smart Chain (BEP-20)</strong> network. Initiating transactions or sending assets via unapproved networks (including but not limited to Tron TRC-20, Ethereum ERC-20, Polygon, or Solana) or transferring to incorrect wallet addresses will result in <strong>permanent, unrecoverable loss of funds</strong>. Empress Two Way 3.0 assumes zero liability for user network mismatch errors.
            </div>
        </div>

        <!-- 2. Processing Times & Confirmations -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/20 space-y-3">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-lg">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-gold uppercase tracking-wider">2. Blockchain Processing Times & Confirmations</h2>
                    <span class="text-[10px] text-champagne/60 font-mono">Distributed Ledger Verification Rules</span>
                </div>
            </div>

            <p>
                Unlike traditional banking systems, transaction execution times depend on decentralized blockchain node validations and BNB Smart Chain network traffic congestion:
            </p>

            <ul class="list-disc pl-5 space-y-2 text-champagne/80">
                <li><strong>Deposit Verification:</strong> Fund deposit requests submitted with a valid Transaction Hash (TxID starting with <code>0x</code>) are validated against block explorer ledgers. Standard verification takes 10 to 30 minutes upon receiving required block confirmations.</li>
                <li><strong>Withdrawal Payouts:</strong> Approved payout requests from the Customer Wallet (50% withdrawable share) are processed directly to the member's verified BEP-20 crypto wallet address within 1 to 24 hours.</li>
                <li><strong>Delayed / Missing TxIDs:</strong> Submitting an invalid, mismatched, or duplicate TxID will delay verification. Users must retain their transaction hash records from BscScan or their web3 wallet (MetaMask, Trust Wallet, Binance) for verification audits.</li>
            </ul>
        </div>

        <!-- 3. Network Fees (Gas Fees) -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/20 space-y-3">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-lg">
                    <i class="fa-solid fa-gas-pump"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-gold uppercase tracking-wider">3. Blockchain Gas Fees & Network Tariffs</h2>
                    <span class="text-[10px] text-gold/80 font-mono">BNB Gas Fee Responsibility</span>
                </div>
            </div>

            <p>
                Executing smart contract actions or transferring USDT on the BNB Smart Chain requires decentralized network gas fees paid in BNB (Binance Coin):
            </p>

            <ul class="list-disc pl-5 space-y-2 text-champagne/80">
                <li><strong>User Responsibility:</strong> Users are responsible for holding sufficient BNB balance in their self-custodial Web3 wallets (e.g., Trust Wallet, MetaMask) to cover outgoing transfer gas fees when depositing or performing P2P transactions.</li>
                <li><strong>Withdrawal Processing Deduction:</strong> Platform withdrawal payouts may have standard blockchain gas transaction fees deducted from the final disbursed amount to cover network broadcasting costs.</li>
            </ul>
        </div>

        <!-- 4. Irreversibility, Refunds & P2P Guidelines -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/20 space-y-3">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-lg">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-gold uppercase tracking-wider">4. Irreversibility & P2P Dispute Resolution</h2>
                    <span class="text-[10px] text-champagne/60 font-mono">Finality of Blockchain Transfers</span>
                </div>
            </div>

            <p>
                By participating in Empress Two Way 3.0, you explicitly acknowledge and agree to the following blockchain ledger rules:
            </p>

            <ul class="list-disc pl-5 space-y-2 text-champagne/80">
                <li><strong>Immutable Finality:</strong> All cryptocurrency transactions, once broadcasted and confirmed on the BNB Smart Chain blockchain, are final, immutable, and strictly non-refundable. No chargebacks or order cancellations exist in decentralized protocols.</li>
                <li><strong>Peer-to-Peer (P2P) Transfers:</strong> Members managing P2P transfers are advised to double-check receiving BEP-20 addresses and QR codes configured in the member profile before broadcasting funds.</li>
                <li><strong>Manual Verification Workflow:</strong> In case of a transaction discrepancy, members must submit a support ticket providing the exact sender wallet, receiver wallet, transferred amount, and on-chain BscScan TxID for administrative audit.</li>
            </ul>
        </div>

        <!-- 5. 50:50 Smart Wallet Allocation Terms -->
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/20 space-y-3">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-lg">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-gold uppercase tracking-wider">5. 50:50 Smart Wallet Allocation Rules</h2>
                    <span class="text-[10px] text-emerald-400 font-mono font-bold">Automated 6-Level Compensation Structure</span>
                </div>
            </div>

            <p>
                Every matrix commission and helping contribution earned through the 6-level fixed tree is automatically partitioned as follows:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-2 text-center">
                <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                    <span class="text-[10px] font-bold uppercase text-emerald-400 block">Customer Wallet</span>
                    <span class="text-lg font-extrabold text-emerald-300">50% Share</span>
                    <span class="text-[10px] text-champagne/60 block mt-1">Withdrawable upon KYC approval</span>
                </div>
                <div class="p-3 bg-gold/10 border border-gold/30 rounded-xl">
                    <span class="text-[10px] font-bold uppercase text-gold block">Burfee Cart Reserve</span>
                    <span class="text-lg font-extrabold text-gold">30% Share</span>
                    <span class="text-[10px] text-champagne/60 block mt-1">Utility & ePIN product reserve</span>
                </div>
                <div class="p-3 bg-champagne/10 border border-champagne/30 rounded-xl">
                    <span class="text-[10px] font-bold uppercase text-champagne block">Charity Fund</span>
                    <span class="text-lg font-extrabold text-champagne">20% Share</span>
                    <span class="text-[10px] text-champagne/60 block mt-1">Empress global charity allocation</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Actions -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs">
        <span class="text-champagne/70">Have questions regarding our crypto payment terms? Check out our FAQ or contact support.</span>
        <div class="flex items-center gap-3">
            <a href="/faq.php" class="gold-button px-5 py-2.5 rounded-xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-question"></i> Read FAQ
            </a>
            <a href="/customer/dashboard.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-5 py-2.5 rounded-xl font-bold">
                Dashboard <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
