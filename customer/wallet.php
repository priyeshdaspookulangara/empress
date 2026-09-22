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

    if ($member['kyc_status'] !== 'Approved') {
        $error = "Withdrawal blocked: Your KYC status must be 'Approved' by admin. Current status: " . strtoupper($member['kyc_status']);
    } elseif ($amount < 10.00) {
        $error = "Withdrawal blocked: Minimum withdrawal amount is $10.00 USD.";
    } elseif ($amount > $wallet['user_wallet_60']) {
        $error = "Withdrawal blocked: Insufficient balance in your User Wallet (60%). Available: $" . number_format($wallet['user_wallet_60'], 2);
    } elseif (empty($member['crypto_wallet_address'])) {
        $error = "Withdrawal blocked: Please save your USD Crypto Wallet Address on your profile page.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmtDeduct = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 - ? WHERE member_id = ?");
            $stmtDeduct->execute([$amount, $memberId]);

            $stmtWithdraw = $pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES (?, ?, 'Pending')");
            $stmtWithdraw->execute([$memberId, $amount]);

            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Withdrawal_Request', ?, 'User_Wallet', 'Debit', ?)
            ");
            $stmtTx->execute([$memberId, $amount, "Withdrawal request of $" . number_format($amount, 2) . " USD submitted for Crypto transfer."]);

            $pdo->commit();
            $success = "Withdrawal request of $" . number_format($amount, 2) . " USD submitted successfully!";

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
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">USD Wallet & Payout Console</h1>
            <p class="text-xs text-ice/70 mt-1">Request USD Crypto withdrawals and view complete earning ledger</p>
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
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30">
            <span class="text-xs font-bold text-neon-cyan/70 uppercase">Total Matrix Balance</span>
            <div class="text-3xl font-extrabold neon-gradient-text mt-2">$<?php echo number_format($wallet['balance'], 2); ?> <span class="text-xs text-neon-cyan">USD</span></div>
            <p class="text-[10px] text-ice/50 mt-1">Cumulative income from level payouts</p>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-500/5">
            <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-emerald-400 uppercase">User Wallet (60%)</span>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-mono">Withdrawal Eligible</span>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400 mt-2">$<?php echo number_format($wallet['user_wallet_60'], 2); ?> <span class="text-xs">USD</span></div>
            <p class="text-[10px] text-ice/50 mt-1">Available for direct Crypto withdrawal (Min: $10 USD)</p>
        </div>

        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
            <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-neon-cyan uppercase">Company Reserve (40%)</span>
                <span class="text-[10px] bg-neon-cyan/10 text-neon-cyan px-2 py-0.5 rounded font-mono">Utility Reserve</span>
            </div>
            <div class="text-3xl font-extrabold text-neon-cyan mt-2">$<?php echo number_format($wallet['company_wallet_40'], 2); ?> <span class="text-xs">USD</span></div>
            <p class="text-[10px] text-ice/50 mt-1">Allocated to company reserve & charity fund</p>
        </div>
    </div>

    <!-- Withdrawal Form & Rules -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 md:p-8 rounded-3xl border border-neon-cyan/30 md:col-span-2">
            <h3 class="text-lg font-bold text-neon-cyan mb-4 flex items-center gap-2">
                <i class="fa-solid fa-money-bill-transfer"></i> Submit Crypto Payout Request (USD)
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
                    <a href="/customer/profile.php" class="text-neon-cyan underline font-bold ml-2">Complete KYC & Save Wallet Address</a>
                </div>
            <?php endif; ?>

            <form action="/customer/wallet.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">Withdrawal Amount ($ USD) *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-neon-cyan font-bold text-sm">$</span>
                        <input type="number" step="0.01" min="10" max="<?php echo $wallet['user_wallet_60']; ?>" name="amount" required placeholder="10.00" <?php echo ($member['kyc_status'] !== 'Approved' || $wallet['user_wallet_60'] < 10) ? 'disabled' : ''; ?> class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl pl-8 pr-4 py-3 text-sm text-ice font-mono focus:outline-none focus:border-neon-cyan disabled:opacity-50">
                    </div>
                    <span class="text-[10px] text-ice/50">Minimum $10 USD. Maximum available: $<?php echo number_format($wallet['user_wallet_60'], 2); ?> USD</span>
                </div>

                <div class="bg-navy/60 p-4 rounded-xl border border-neon-cyan/15 text-xs text-ice/80 space-y-1">
                    <div class="text-neon-cyan font-bold text-[11px] uppercase tracking-wider mb-1">Destination Crypto Wallet:</div>
                    <div>Network: <strong class="text-ice"><?php echo htmlspecialchars($member['wallet_network'] ?: 'USDT (TRC20)'); ?></strong></div>
                    <div>Address: <strong class="text-neon-cyan font-mono text-[11px] break-all"><?php echo htmlspecialchars($member['crypto_wallet_address'] ?: 'Not Saved on Profile'); ?></strong></div>
                </div>

                <div>
                    <button type="submit" <?php echo ($member['kyc_status'] !== 'Approved' || $wallet['user_wallet_60'] < 10) ? 'disabled' : ''; ?> class="neon-button w-full py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-neon-cyan/20 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i> Submit USD Withdrawal Request
                    </button>
                </div>
            </form>
        </div>

        <!-- Withdrawal Guidelines Card -->
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 flex flex-col justify-between">
            <div>
                <h4 class="text-sm font-bold text-neon-cyan uppercase tracking-wider mb-3">Withdrawal Rules</h4>
                <ul class="text-xs text-ice/80 space-y-3">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-neon-cyan mt-0.5"></i>
                        <span>Withdrawals are paid in USD via <strong>Crypto Wallet (USDT)</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-neon-cyan mt-0.5"></i>
                        <span>Minimum withdrawal threshold is strictly <strong>$10.00 USD</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-neon-cyan mt-0.5"></i>
                        <span>Admin requires mandatory <strong>Approved KYC</strong> status before processing.</span>
                    </li>
                </ul>
            </div>
            <div class="mt-6 pt-4 border-t border-neon-cyan/20 text-[10px] text-ice/50 text-center">
                Payout processing cycle: 24 - 48 business hours
            </div>
        </div>
    </div>

    <!-- Withdrawal Requests History Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <h3 class="text-lg font-bold text-neon-cyan mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-check"></i> Withdrawal Requests History
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Req ID</th>
                        <th class="p-3">Amount ($ USD)</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Request Date</th>
                        <th class="p-3">Processed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-ice/50">No withdrawal requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan/80">#WD-<?php echo $w['id']; ?></td>
                                <td class="p-3 font-bold text-emerald-400">$<?php echo number_format($w['amount'], 2); ?></td>
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
                                <td class="p-3 font-mono text-ice/70"><?php echo $w['request_date']; ?></td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $w['processed_date'] ?: 'Pending'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Complete Transaction Audit Statement -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <h3 class="text-lg font-bold text-neon-cyan mb-4 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice-dollar"></i> Earning & Transaction Statement Ledger
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Tx ID</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount ($ USD)</th>
                        <th class="p-3">Wallet</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($allTx)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-ice/50">No transactions recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allTx as $tx): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan/70">#TX-<?php echo $tx['id']; ?></td>
                                <td class="p-3 font-semibold text-neon-cyan"><?php echo str_replace('_', ' ', $tx['type']); ?></td>
                                <td class="p-3 font-bold <?php echo $tx['status'] === 'Credit' ? 'text-emerald-400' : 'text-red-400'; ?>">
                                    <?php echo $tx['status'] === 'Credit' ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 2); ?>
                                </td>
                                <td class="p-3 font-mono text-ice/70"><?php echo $tx['wallet_type']; ?></td>
                                <td class="p-3 font-semibold"><?php echo $tx['status']; ?></td>
                                <td class="p-3 text-ice/80"><?php echo htmlspecialchars($tx['description']); ?></td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $tx['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
