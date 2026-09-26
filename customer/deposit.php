<?php
$pageTitle = "USDT (BEP-20) Fund Deposit";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

$msg = '';
$msgType = '';

// Handle Deposit Request Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
    $txHash = trim($_POST['tx_hash'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    $res = submitFundDeposit($pdo, $memberId, $txHash, $amount, 'BEP-20');
    if ($res['success']) {
        $msg = $res['message'];
        $msgType = 'success';
    } else {
        $msg = $res['message'];
        $msgType = 'error';
    }
}

// Fetch Deposit History
$userDeposits = getUserDeposits($pdo, $memberId);

$officialWalletAddress = "0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-mono tracking-widest text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/30">BNB Smart Chain (BEP-20)</span>
                <span class="text-xs text-champagne/60">Empress Two Way 3.0</span>
            </div>
            <h1 class="text-2xl font-extrabold gold-gradient-text mt-2">USDT Fund Deposit</h1>
            <p class="text-xs text-champagne/70 mt-1">Deposit USDT on BEP-20 network to fund your account and purchase matrix packages</p>
        </div>

        <a href="/customer/dashboard.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="p-4 rounded-2xl text-xs font-bold border flex items-center gap-3 <?php echo $msgType === 'success' ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-300' : 'bg-red-500/10 border-red-500/40 text-red-400'; ?>">
            <i class="fa-solid <?php echo $msgType === 'success' ? 'fa-circle-check text-xl' : 'fa-triangle-exclamation text-xl'; ?>"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- 1. Mandatory Network Warning Banner -->
    <div class="glass-card p-5 rounded-3xl border border-amber-500/50 bg-gradient-to-r from-amber-500/10 via-black to-red-500/10">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl shrink-0">
                <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-amber-300 uppercase tracking-wider">Important Network Security Warning</h3>
                <p class="text-xs text-champagne/90 leading-relaxed mt-1">
                    Send <strong class="text-gold">ONLY USDT</strong> via the <strong class="text-emerald-400">BNB Smart Chain (BEP-20)</strong> network to this deposit address.
                    Sending any other cryptocurrency, token, or using an unsupported network (such as TRC-20, ERC-20, or Polygon) will result in <strong>permanent loss of your assets</strong>.
                </p>
            </div>
        </div>
    </div>

    <!-- 2. QR Code & Official Address Display Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Official Deposit Address & QR Code Card -->
        <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col items-center text-center">
            <span class="text-[10px] uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/20 mb-4">Official Receiving Wallet</span>

            <!-- QR Code Image -->
            <div class="p-3 bg-white rounded-2xl border-2 border-gold/50 shadow-xl shadow-gold/10 mb-4">
                <img src="/assets/images/image_45cf5f.jpg" onerror="this.src='/image_45cf5f.jpg'" alt="USDT BEP-20 Deposit QR Code" class="w-48 h-48 object-contain">
            </div>

            <span class="text-xs text-champagne/60 font-medium">Scan QR Code or Copy Address</span>

            <!-- Address Display Input + Copy Button -->
            <div class="w-full mt-3 glass-card p-3 rounded-2xl border border-gold/20 flex items-center justify-between gap-2">
                <input type="text" readonly value="<?php echo $officialWalletAddress; ?>" id="walletAddressInput" class="bg-transparent text-emerald-400 font-mono text-xs focus:outline-none w-full truncate font-bold">
                <button onclick="copyWalletAddress()" class="gold-button px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap flex items-center gap-1">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
            </div>

            <span class="text-[10px] text-champagne/50 mt-2 font-mono">Network: BNB Smart Chain (BEP-20)</span>
        </div>

        <!-- 3. Transaction Verification Form -->
        <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-gold/10 text-gold flex items-center justify-center border border-gold/20">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h3 class="text-base font-bold text-gold">Submit Transaction Hash</h3>
                </div>
                <p class="text-xs text-champagne/70 mb-6">After sending USDT (BEP-20) to the wallet address, enter your Transaction Hash (TxID) and transferred amount below for fast manual verification.</p>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-champagne uppercase tracking-wider mb-1.5">Transferred Amount (USDT)</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 10.00" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-champagne text-xs font-mono focus:outline-none focus:border-gold">
                            <span class="absolute right-3 top-2.5 text-xs text-gold font-bold">USDT</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-champagne uppercase tracking-wider mb-1.5">Transaction Hash / TxID</label>
                        <input type="text" name="tx_hash" required placeholder="e.g. 0x1234567890abcdef..." class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-emerald-400 font-mono text-xs focus:outline-none focus:border-gold">
                        <p class="text-[10px] text-champagne/50 mt-1">Must be a valid hex string starting with <span class="font-mono text-gold">0x</span></p>
                    </div>

                    <button type="submit" name="submit_deposit" class="w-full gold-button py-3 rounded-xl text-xs font-bold shadow-lg shadow-gold/20 flex items-center justify-center gap-2 mt-4">
                        <i class="fa-solid fa-paper-plane"></i> Submit Deposit Verification
                    </button>
                </form>
            </div>

            <div class="mt-4 pt-3 border-t border-gold/10 text-[11px] text-champagne/60 flex items-center gap-2">
                <i class="fa-solid fa-clock text-gold"></i>
                <span>Deposits are processed and credited within 10–30 minutes upon blockchain confirmation.</span>
            </div>
        </div>
    </div>

    <!-- Deposit History Log Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-base font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-check"></i> Deposit Verification History
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">ID</th>
                        <th class="p-3">Transaction Hash (TxID)</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Network</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($userDeposits)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-champagne/50">No deposit verification requests submitted yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userDeposits as $dep): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold">#<?php echo $dep['id']; ?></td>
                                <td class="p-3 font-mono text-emerald-400 truncate max-w-xs" title="<?php echo htmlspecialchars($dep['tx_hash']); ?>">
                                    <?php echo htmlspecialchars($dep['tx_hash']); ?>
                                </td>
                                <td class="p-3 font-mono font-bold text-champagne">$<?php echo number_format($dep['amount'], 2); ?> USDT</td>
                                <td class="p-3 font-mono text-gold/80"><?php echo htmlspecialchars($dep['network']); ?></td>
                                <td class="p-3">
                                    <?php if ($dep['status'] === 'Approved'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">Approved</span>
                                    <?php elseif ($dep['status'] === 'Rejected'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-red-500/20 text-red-400 border border-red-500/40">Rejected</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-400 border border-amber-500/40">Pending Verification</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $dep['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyWalletAddress() {
    var input = document.getElementById("walletAddressInput");
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    alert("BNB Smart Chain (BEP-20) address copied to clipboard: " + input.value);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
