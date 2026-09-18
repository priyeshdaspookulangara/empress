<?php
$pageTitle = "My Wallet & Payout Withdrawals";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();

$error = '';
$success = '';

// Fetch Member details & KYC status
$stmtM = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmtM->execute([$memberId]);
$member = $stmtM->fetch();

// Fetch Wallet details
$stmtW = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmtW->execute([$memberId]);
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

// Handle Withdrawal Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);

    // Strict Rule Validation
    if ($member['kyc_status'] !== 'Approved') {
        $error = "Withdrawal blocked: Your KYC status must be 'Approved' by admin. Current status: " . strtoupper($member['kyc_status']);
    } elseif ($amount < 500.00) {
        $error = "Withdrawal blocked: Minimum withdrawal amount is ₹500.";
    } elseif ($amount > $wallet['user_wallet_60']) {
        $error = "Withdrawal blocked: Insufficient balance in your User Wallet (60%). Available: ₹" . number_format($wallet['user_wallet_60'], 2);
    } else {
        $pdo->beginTransaction();
        try {
            // Deduct amount from user_wallet_60
            $stmtDeduct = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 - ? WHERE member_id = ?");
            $stmtDeduct->execute([$amount, $memberId]);

            // Create withdrawal request
            $stmtWithdraw = $pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES (?, ?, 'Pending')");
            $stmtWithdraw->execute([$memberId, $amount]);

            // Create transaction log
            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Withdrawal_Request', ?, 'User_Wallet', 'Debit', ?)
            ");
            $stmtTx->execute([$memberId, $amount, "Withdrawal request of ₹" . number_format($amount, 2) . " submitted for bank transfer."]);

            $pdo->commit();
            $success = "Withdrawal request of ₹" . number_format($amount, 2) . " submitted successfully!";

            // Refresh wallet
            $stmtW->execute([$memberId]);
            $wallet = $stmtW->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to submit withdrawal request: " . $e->getMessage();
        }
    }
}

// Fetch Withdrawal History
$stmtWList = $pdo->prepare("SELECT * FROM withdrawals WHERE member_id = ? ORDER BY id DESC");
$stmtWList->execute([$memberId]);
$withdrawals = $stmtWList->fetchAll();

// Fetch Full Transaction Logs
$stmtTxList = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY id DESC");
$stmtTxList->execute([$memberId]);
$allTx = $stmtTxList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Wallet & Financial Console</h1>
            <p class="text-xs text-champagne/70 mt-1">Request bank payouts and view complete earning ledger</p>
        </div>
        <div>
            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-xl border <?php
                echo match($member['kyc_status']) {
                    'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                    default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                };
            ?>">
                <i class="fa-solid fa-shield"></i> KYC: <?php echo htmlspecialchars($member['kyc_status']); ?>
            </span>
        </div>
    </div>

    <!-- Smart Wallet Breakdown Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-3xl border border-gold/30">
            <span class="text-xs font-bold text-gold/70 uppercase">Total Matrix Balance</span>
            <div class="text-3xl font-extrabold gold-gradient-text mt-2">₹<?php echo number_format($wallet['balance'], 2); ?></div>
            <p class="text-[10px] text-champagne/50 mt-1">Cumulative income from level payouts</p>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-500/5">
            <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-emerald-400 uppercase">User Wallet (60%)</span>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-mono">Withdrawal Eligible</span>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400 mt-2">₹<?php echo number_format($wallet['user_wallet_60'], 2); ?></div>
            <p class="text-[10px] text-champagne/50 mt-1">Available for direct bank withdrawal (Min: ₹500)</p>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-gold/20">
            <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-gold uppercase">Company Reserve (40%)</span>
                <span class="text-[10px] bg-gold/10 text-gold px-2 py-0.5 rounded font-mono">Utility Reserve</span>
            </div>
            <div class="text-3xl font-extrabold text-gold mt-2">₹<?php echo number_format($wallet['company_wallet_40'], 2); ?></div>
            <p class="text-[10px] text-champagne/50 mt-1">Allocated to company reserve & charity fund</p>
        </div>
    </div>

    <!-- Withdrawal Form & Rules -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30 md:col-span-2">
            <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-money-bill-transfer"></i> Submit Payout Withdrawal Request
            </h3>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-base"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-base text-emerald-400"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($member['kyc_status'] !== 'Approved'): ?>
                <div class="bg-amber-500/10 border border-amber-500/40 p-4 rounded-xl text-xs text-amber-300 mb-6">
                    <i class="fa-solid fa-lock mr-1"></i> Withdrawal Form Locked: You must have an 'Approved' KYC status to request funds.
                    <a href="/customer/profile.php" class="text-gold underline font-bold ml-2">Complete KYC Now</a>
                </div>
            <?php endif; ?>

            <form action="/customer/wallet.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Withdrawal Amount (₹) *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gold font-bold text-sm">₹</span>
                        <input type="number" step="0.01" min="500" max="<?php echo $wallet['user_wallet_60']; ?>" name="amount" required placeholder="500.00" <?php echo ($member['kyc_status'] !== 'Approved' || $wallet['user_wallet_60'] < 500) ? 'disabled' : ''; ?> class="w-full bg-obsidian/80 border border-gold/30 rounded-xl pl-8 pr-4 py-3 text-sm text-champagne font-mono focus:outline-none focus:border-gold disabled:opacity-50">
                    </div>
                    <span class="text-[10px] text-champagne/50">Minimum ₹500. Maximum available: ₹<?php echo number_format($wallet['user_wallet_60'], 2); ?></span>
                </div>

                <div class="bg-obsidian/60 p-4 rounded-xl border border-gold/15 text-xs text-champagne/80 space-y-1">
                    <div class="text-gold font-bold text-[11px] uppercase tracking-wider mb-1">Destination Bank Account:</div>
                    <div>Bank: <strong class="text-champagne"><?php echo htmlspecialchars($member['bank_name'] ?: 'Not Provided'); ?></strong></div>
                    <div>A/C Number: <strong class="text-champagne font-mono"><?php echo htmlspecialchars($member['bank_account_number'] ?: 'Not Provided'); ?></strong></div>
                    <div>IFSC: <strong class="text-champagne font-mono"><?php echo htmlspecialchars($member['ifsc_code'] ?: 'Not Provided'); ?></strong></div>
                </div>

                <div>
                    <button type="submit" <?php echo ($member['kyc_status'] !== 'Approved' || $wallet['user_wallet_60'] < 500) ? 'disabled' : ''; ?> class="gold-button w-full py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-gold/20 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i> Submit Withdrawal Request
                    </button>
                </div>
            </form>
        </div>

        <!-- Withdrawal Guidelines Card -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20 flex flex-col justify-between">
            <div>
                <h4 class="text-sm font-bold text-gold uppercase tracking-wider mb-3">Withdrawal Rules</h4>
                <ul class="text-xs text-champagne/80 space-y-3">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-gold mt-0.5"></i>
                        <span>Withdrawals are paid exclusively from <strong>User Wallet (60%)</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-gold mt-0.5"></i>
                        <span>Minimum withdrawal threshold is strictly <strong>₹500</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-gold mt-0.5"></i>
                        <span>Admin requires mandatory <strong>Approved KYC</strong> status before processing.</span>
                    </li>
                </ul>
            </div>
            <div class="mt-6 pt-4 border-t border-gold/20 text-[10px] text-champagne/50 text-center">
                Payout processing cycle: 24 - 48 business hours
            </div>
        </div>
    </div>

    <!-- Withdrawal Requests History Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-check"></i> Withdrawal Requests History
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Req ID</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Request Date</th>
                        <th class="p-3">Processed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-champagne/50">No withdrawal requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold/80">#WD-<?php echo $w['id']; ?></td>
                                <td class="p-3 font-bold text-emerald-400">₹<?php echo number_format($w['amount'], 2); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                        echo match($w['status']) {
                                            'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                                            'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                                            default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                                        };
                                    ?>">
                                        <?php echo $w['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-champagne/70"><?php echo $w['request_date']; ?></td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $w['processed_date'] ?: 'Pending'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Complete Transaction Audit Statement -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice-dollar"></i> Earning & Transaction Statement Ledger
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Wallet</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($allTx)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-champagne/50">No transactions recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allTx as $tx): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold/70">#TX-<?php echo $tx['id']; ?></td>
                                <td class="p-3 font-semibold text-gold"><?php echo str_replace('_', ' ', $tx['type']); ?></td>
                                <td class="p-3 font-bold <?php echo $tx['status'] === 'Credit' ? 'text-emerald-400' : 'text-red-400'; ?>">
                                    <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                </td>
                                <td class="p-3 font-mono text-champagne/70"><?php echo $tx['wallet_type']; ?></td>
                                <td class="p-3 font-semibold"><?php echo $tx['status']; ?></td>
                                <td class="p-3 text-champagne/80"><?php echo htmlspecialchars($tx['description']); ?></td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $tx['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
